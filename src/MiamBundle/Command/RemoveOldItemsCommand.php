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
            ->addOption('min', null, InputOption::VALUE_REQUIRED, "Minimum number of items kept per feed")
            ->addOption('max', null, InputOption::VALUE_REQUIRED, "Maximum number of items kept per feed")
            ->addOption('time', null, InputOption::VALUE_REQUIRED, "Time before removal in days (Default: 100)")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $em = $this->getContainer()->get('doctrine')->getManager();

        $minItemsPerFeed = null;
        if($input->getOption('min') !== null) {
            $min = intval($input->getOption('min'));
            if($min > 0) {
                $minItemsPerFeed = $min;
            }
        }

        $maxItemsPerFeed = null;
        if($input->getOption('max') !== null) {
            $max = intval($input->getOption('max'));
            if($max >= 0) {
                $maxItemsPerFeed = $max;
            }
        }
        
        $daysBeforeRemoval = $input->getOption('time') ? intval($input->getOption('time')) : 100;
        $removalBefore = new \DateTime("- ".$daysBeforeRemoval." days");
        
        $countItemsRemoved = 0;
        $itemsPerBatch = 1000;
        
        $feeds = $em->getRepository("MiamBundle:Feed")->findAll();
        foreach($feeds as $feed) {
            $countFeedItemsRemoved = 0;
            
            if($minItemsPerFeed !== null) {
                $offset = max($minItemsPerFeed, $feed->getCountLastParsedItems());
            } else {
                $offset = $feed->getCountLastParsedItems();
            }
            
            $iItem = $offset;
            do {
                $batchItems = $em->getRepository("MiamBundle:Item")
                    ->createQueryBuilder("i")
                    ->where("i.feed = :feed")->setParameter('feed', $feed)
                    ->orderBy('i.dateCreated', 'DESC')
                    ->addOrderBy('i.id', 'DESC')
                    ->setFirstResult($offset)
                    ->setMaxResults($itemsPerBatch)
                    ->getQuery()->getResult();

                $countBatchItems = count($batchItems);
                if($countBatchItems > 0) {
                    $countBatchItemsRemoved = 0;

                    foreach($batchItems as $item) {
                        $iItem++;

                        if($item->getDateCreated() < $removalBefore || ($maxItemsPerFeed !== null && $iItem > $maxItemsPerFeed)) {
                            $em->remove($item);

                            $countBatchItemsRemoved++;
                            $countFeedItemsRemoved++;
                            $countItemsRemoved++;
                        }
                    }

                    if($countBatchItemsRemoved > 0) {
                        $em->flush();
                    }
                    
                    $offset += $itemsPerBatch - $countBatchItemsRemoved;
                }

                $em->clear();
            } while($countBatchItems == $itemsPerBatch);

            if($countFeedItemsRemoved > 0) {
                $output->writeln($countFeedItemsRemoved." item(s) removed for feed ".$feed->getId()." - ".$feed->getUrl());
            }
        }
        
        if($countItemsRemoved > 0) {
            $output->writeln("Total items removed: ".$countItemsRemoved);

            $this->getContainer()->get('feed_manager')->updateItemCounts();
        } else {
            $output->writeln("No item was removed");
        }
    }
}