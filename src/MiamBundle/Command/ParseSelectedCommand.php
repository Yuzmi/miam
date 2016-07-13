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
            ->setDescription('Parse a selected collection of feeds')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $output->writeln('Fetching and parsing...');

        $time_begin = time();

        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        
        $feeds = $em->getRepository('MiamBundle:Feed')
            ->createQueryBuilder("f")
            ->select("f, COUNT(s.id) AS countSubs")
            ->leftJoin("f.subscriptions", "s")
            ->groupBy("f")
            ->getQuery()->getResult();

        $nb = 0;
        foreach($feeds as $f) {
            $feed = $em->getRepository('MiamBundle:Feed')->find($f[0]->getId());
            
            if($feed && ($feed->getIsCatalog() || $f["countSubs"] > 0)) {
                $this->getContainer()->get('data_parsing')->parseFeed($feed, array('verbose' => true));

                $nb++;
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