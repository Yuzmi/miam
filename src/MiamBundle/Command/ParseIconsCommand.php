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
            ->addArgument('feeds', InputArgument::OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $time_start = time();

        $em = $this->getContainer()->get('doctrine')->getManager();

        $arg = trim($input->getArgument('feeds'));
        $feeds = $this->getContainer()->get('feed_manager')->getFeeds($arg);

        if(is_null($feeds)) {
            if(filter_var($arg, FILTER_VALIDATE_URL) !== false || intval($arg) > 0) {
                $output->writeln('Feed unknown');
            } else {
                $output->writeln('Wrong argument');
            }

            return;
        }

        $countFeeds = count($feeds);

        $uniqueFeed = false;
        if($countFeeds == 1) {
            $uniqueFeed = true;
        }

        if($countFeeds > 0) {
            $output->writeln('Parsing... ');

            $count = 0;
            $countSuccess = 0;
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
                if($result["success"]) {
                    $countSuccess++;
                }

                if($count%50 == 0) {
                    $em->clear();
                }
            }

            if(!$uniqueFeed) {
                $output->writeln('');
            }

            $duration = time() - $time_start;

            $output->writeln('Done. Icons: '.$countSuccess.'/'.$count.'. Duration: '.$duration.'s');
        } else {
            $output->writeln('Nothing happened');
        }
    }
}
