<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseSelectedCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:parse:selected')
            ->setDescription('Récupère une sélection de flux')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $output->writeln('Fetching and parsing...');

        $time_begin = time();

        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        
        $feeds = $em->getRepository('MiamBundle:Feed')->findAll();

        $nb = 0;
        foreach($feeds as $feed) {
            $feed = $em->getRepository('MiamBundle:Feed')->find($feed->getId());

            if($feed) {
                $now = new \DateTime("now");
                $oneHourAgo = new \DateTime("now - 1 hour");
                $oneDayAgo = new \DateTime("now - 1 day");
                $oneWeekAgo = new \DateTime("now - 1 week");
                $oneMonthAgo = new \DateTime("now - 1 month");

                $date = $feed->getDateNewItem() ?: $feed->getDateCreated();
                if(
                    $date > $oneWeekAgo
                    || ($date > $oneMonthAgo && $feed->getDateParsed() < $oneHourAgo)
                    || ($date < $oneMonthAgo && $feed->getDateParsed() < $oneDayAgo)
                ) {
                    $this->getContainer()->get('data_parsing')->parseFeed($feed, array('verbose' => true));

                    $nb++;
                }
            }

            if($nb%20 == 0) {
                $em->clear();
            }
        }

        $duration = time() - $time_begin;

        $output->writeln('');
        $output->writeln('End - Duration: '.$duration.'s');
    }
}