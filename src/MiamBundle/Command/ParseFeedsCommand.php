<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseFeedsCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:parse:feeds')
            ->setDescription('Parse feeds')
            ->addArgument('feeds', InputArgument::OPTIONAL)
            ->addOption('list-errors', InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $time_start = time();

        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $arg = $input->getArgument('feeds');
        if($arg == 'catalog') {
            $feeds = $em->getRepository('MiamBundle:Feed')
                ->createQueryBuilder('f')
                ->where('f.isCatalog = TRUE')
                ->getQuery()->getResult();
        } elseif($arg == 'subscribed') {
            $feeds = $em->getRepository('MiamBundle:Feed')
                ->createQueryBuilder('f')
                ->innerJoin('f.subscriptions', 's')
                ->getQuery()->getResult();

            /*
            $feeds = $em->getRepository('MiamBundle:Feed')
                ->createQueryBuilder('f')
                ->select('f, COUNT(s.id)')
                ->leftJoin('f.subscriptions', 's')
                ->groupBy('f')
                ->having('COUNT(s.id) > 0')
                ->getQuery()->getResult();
            */
        } elseif($arg == 'used') {
            $fs = $em->getRepository('MiamBundle:Feed')
                ->createQueryBuilder("f")
                ->select("f, COUNT(s.id)")
                ->leftJoin("f.subscriptions", "s")
                ->groupBy("f")
                ->having("f.isCatalog = TRUE OR COUNT(s.id) > 0")
                ->getQuery()->getResult();

            $feeds = array();
            foreach($fs as $f) {
                $feeds[] = $f[0];
            }
        } elseif($arg == 'unused') {
            $fs = $em->getRepository('MiamBundle:Feed')
                ->createQueryBuilder("f")
                ->select("f, COUNT(s.id)")
                ->leftJoin("f.subscriptions", "s")
                ->groupBy("f")
                ->having("f.isCatalog = FALSE AND COUNT(s.id) = 0")
                ->getQuery()->getResult();

            $feeds = array();
            foreach($fs as $f) {
                $feeds[] = $f[0];
            }
        } else {
            $feeds = $em->getRepository('MiamBundle:Feed')->findAll();
        }

        $countFeeds = count($feeds);
        $countValidFeeds = 0;
        $countNewItems = 0;
        $errors = array();

        $output->writeln('Fetching and parsing...');

        $nb = 0;
        foreach($feeds as $f) {
            $feed = $em->getRepository('MiamBundle:Feed')->find($f->getId());
            if(!$feed) {
                continue;
            }

            $result = $this->getContainer()->get('data_parsing')->parseFeed($feed);
            if($result['success']) {
                if($result['countNewItems'] > 0) {
                    $output->write('+');

                    $countNewItems += $result['countNewItems'];
                } else {
                    $output->write('-');
                }

                $countValidFeeds++;
            } else {
                $output->write('x');

                $errors[] = $feed->getId().' - '.$feed->getUrl().' - '.$result['error'];
            }

            $nb++;

            if($nb%20 == 0) {
                $em->clear();
            }
        }

        $duration = time() - $time_start;

        $output->writeln('');
        $output->writeln('Valid feeds: '.$countValidFeeds.'/'.$countFeeds.' - New item(s): '.$countNewItems.' - Duration: '.$duration.'s');

        if($input->getOption('list-errors')) {
            if(count($errors) > 0) {
                $output->writeln('Errors:');

                foreach($errors as $error) {
                    $output->writeln($error);
                }
            } else {
                $output->writeln('No error occured');
            }
        }
    }
}