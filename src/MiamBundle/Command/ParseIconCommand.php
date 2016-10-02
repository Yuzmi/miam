<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseIconCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:parse:icon')
            ->setDescription("Parse a feed icon")
            ->addArgument('feed', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $feedArgument = $input->getArgument('feed');

        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        
        $feed = null;
        if(filter_var($feedArgument, FILTER_VALIDATE_URL) !== false) {
            $feed = $this->getContainer()->get('feed_manager')->findFeedForUrl($feedArgument);
        } else {
            $feed = $em->getRepository('MiamBundle:Feed')->find(intval($feedArgument));
        }

        if($feed) {
            $output->write('Parsing icon... ');

            $result = $this->getContainer()->get('data_parsing')->parseIcon($feed);
            if($result['success']) {
                $output->writeln('Success.');
            } else {
                $output->writeln('Failure.');
            }
        } else {
            $output->writeln('Feed unknown...');
        }
    }
}