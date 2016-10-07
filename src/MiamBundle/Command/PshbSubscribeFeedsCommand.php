<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PshbSubscribeFeedsCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:pshb:subscribe:feeds')
            ->setDescription('Subscribe feeds using PubSubHubBub')
            ->addArgument('feeds', InputArgument::OPTIONAL, "Which feeds will you subscribe ?")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $time_start = time();

        $em = $this->getContainer()->get('doctrine')->getEntityManager();

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

        $countFeeds = 0;
        $countValidFeeds = 0;

        $output->writeln('Subscribing...');

        foreach($feeds as $feed) {
            $subscribe = $this->getContainer()->get('pubsubhubbub')->subscribe($feed->getUrl());

            if($subscribe) {
                $output->write('+');

                $countValidFeeds++;
            } else {
                $output->write('X');
            }

            $countFeeds++;
        }

        $duration = time() - $time_start;

        $output->writeln('');
        $output->writeln('Subscribed feeds: '.$countValidFeeds.'/'.$countFeeds.' - Duration: '.$duration.'s');
    }
}