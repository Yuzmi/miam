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

        $arg = $input->getArgument('feeds');
        if($arg == 'catalog') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findCatalog();
        } elseif($arg == 'subscribed') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findSubscribed();
        } elseif($arg == 'used') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findUsed();
        } elseif($arg == 'unused') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findUnused();
        } else {
            $feeds = $em->getRepository('MiamBundle:Feed')->findAll();
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