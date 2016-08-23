<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseUsedCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:parse:used')
            ->setDescription('Parse used feeds')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $output->writeln('Fetching and parsing...');

        $time_begin = time();

        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        
        $feeds = $em->getRepository('MiamBundle:Feed')
            ->createQueryBuilder("f")
            ->select("f, COUNT(s.id)")
            ->leftJoin("f.subscriptions", "s")
            ->groupBy("f")
            ->having("f.isCatalog = TRUE OR COUNT(s.id) > 0")
            ->getQuery()->getResult();

        $nb = 0;
        foreach($feeds as $f) {
            $feed = $em->getRepository('MiamBundle:Feed')->find($f[0]->getId());
            if(!$feed) {
                continue;
            }
            
            $this->getContainer()->get('data_parsing')->parseFeed($feed, array('verbose' => true));

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