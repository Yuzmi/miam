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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $output->writeln('Fetching and parsing...');

        $time_begin = time();

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
        } else {
            $feeds = $em->getRepository('MiamBundle:Feed')->findAll();
        }

        $totalNewItems = 0;

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

                    $totalNewItems += $result['countNewItems'];
                } else {
                    $output->write('-');
                }
            } else {
                $output->write('x');
            }

            $nb++;

            if($nb%20 == 0) {
                $em->clear();
            }
        }

        $duration = time() - $time_begin;

        $output->writeln('');
        $output->writeln('New item(s): '.$totalNewItems.' - Duration: '.$duration.'s');
    }
}