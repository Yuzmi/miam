<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetFeedsCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:get:feeds')
            ->setDescription('Get feeds')
            ->addArgument('feeds', InputArgument::OPTIONAL, "Which feeds do you want ?")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);
        
        $arg = trim($input->getArgument('feeds'));
        $feeds = $this->getContainer()->get('feed_manager')->getFeeds($arg);
        
        foreach($feeds as $feed) {
            $output->writeln($feed->getId()." ".$feed->getUrl());
        }
    }
}