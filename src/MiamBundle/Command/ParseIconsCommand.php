<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseIconsCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:parse:icons')
            ->setDescription('Parse icons')
            ->addArgument('feed', InputArgument::OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $time_start = time();

        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $feeds = array();
        $uniqueFeed = false;

        $arg = $input->getArgument('feed');
        if($arg == 'all' || is_null($arg)) {
            $feeds = $em->getRepository('MiamBundle:Feed')->findAll();
        } elseif(filter_var($arg, FILTER_VALIDATE_URL) !== false) {
            $feed = $this->getContainer()->get('feed_manager')->findFeedForUrl($arg);
            if($feed) {
                $feeds[] = $feed;
                $uniqueFeed = true;
            } else {
                $output->writeln('Feed unknown');
                return;
            }
        } elseif(intval($arg) > 0) {
            $feed = $em->getRepository('MiamBundle:Feed')->find(intval($arg));
            if($feed) {
                $feeds[] = $feed;
                $uniqueFeed = true;
            } else {
                $output->writeln('ID unknown');
                return;
            }
        } else {
            $output->writeln('WTF do you mean ?');
            return;
        }

        $output->writeln('Parsing... ');

        $count = 0;
        foreach($feeds as $f) {
            $feed = $em->getRepository('MiamBundle:Feed')->find($f->getId());
            if(!$feed) {
                continue;
            }

            $result = $this->getContainer()->get('data_parsing')->parseIcon($feed);

            if(!$uniqueFeed) {
                if($result['success']) {
                    $output->write('O');
                } else {
                    $output->write('x');
                }
            }

            $count++;

            if($count%50 == 0) {
                $em->clear();
            }
        }

        if(!$uniqueFeed) {
            $output->writeln('');
        }

        $duration = time() - $time_start;
        $output->writeln('Done. Duration: '.$duration.'s');
    }
}
