<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseSubscribedCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:parse:subscribed')
            ->setDescription('Parse all feeds with subscribers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $output->writeln('Fetching and parsing...');

        $time_begin = time();

        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        
        $feeds = $em->getRepository('MiamBundle:Feed')
            ->createQueryBuilder('f')
            ->innerJoin('f.subscriptions', 's')
            ->getQuery()->getResult();

        /*$feeds = $em->getRepository('MiamBundle:Feed')
            ->createQueryBuilder('f')
            ->select('f, COUNT(s.id)')
            ->leftJoin('f.subscriptions', 's')
            ->groupBy('f')
            ->having('COUNT(s.id) > 0')
            ->getQuery()->getResult();*/

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
        $output->writeln('End - Duration: '.$duration.'s');
    }
}