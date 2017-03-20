<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveOldItemsCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:items:remove-old')
            ->setDescription('Remove old items')
            ->addOption('keep', 'k', InputOption::VALUE_REQUIRED, "Minimum number of items kept per feed (Default: 1000)")
            ->addOption('time', 't', InputOption::VALUE_REQUIRED, "Time before removal in days (Default: 365)")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $em = $this->getContainer()->get('doctrine')->getManager();

        $keptItemsPerFeed = $input->getOption('keep') ?: 1000;
        $daysBeforeRemoval = $input->getOption('time') ?: 365;
        
        $countItemsRemoved = 0;

        $feeds = $em->getRepository("MiamBundle:Feed")->findAll();
        foreach($feeds as $feed) {
            $items = $em->getRepository("MiamBundle:Item")
                ->createQueryBuilder("i")
                ->where("i.feed = :feed")->setParameter('feed', $feed)
                ->orderBy('i.dateCreated', 'DESC')
                ->addOrderBy('i.id', 'DESC')
                ->setFirstResult(max($keptItemsPerFeed, $feed->getCountLastParsedItems()))
                ->getQuery()->getResult();

            $countFeedItemsRemoved = 0;
            if(count($items) > 0) {
                $removalBefore = new \DateTime("- ".$daysBeforeRemoval." days");

                foreach($items as $i) {
                    if($i->getDateCreated < $removalBefore) {
                        $em->remove($i);

                        $countFeedItemsRemoved++;
                        $countItemsRemoved++;
                    }
                }

                if($countFeedItemsRemoved > 0) {
                    $em->flush();
                }
            }

            if($countFeedItemsRemoved > 0) {
                $output->writeln($countFeedItemsRemoved." item(s) removed for feed ".$feed->getId()." - ".$feed->getUrl());
            }

            $i = isset($i) ? $i+1 : 1;
            if($i%20) {
                $em->clear();
            }
        }
        
        if($countItemsRemoved > 0) {
            $output->writeln("Total items removed: ".$countItemsRemoved);
        } else {
            $output->writeln("No item was removed");
        }
    }
}