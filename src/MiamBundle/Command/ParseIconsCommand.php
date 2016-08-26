<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseIconsCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:parse:icons')
            ->setDescription('Parse icons')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $output->writeln('Parsing icons... ');
        $time_begin = time();

        $feeds = $em->getRepository('MiamBundle:Feed')->findAll();

        $nb = 0;
        foreach($feeds as $f) {
            $feed = $em->getRepository('MiamBundle:Feed')->find($f->getId());
            if(!$feed) {
                continue;
            }

            $result = $this->getContainer()->get('data_parsing')->parseIcon($feed);
            if($result['success']) {
                $output->write('O');
            } else {
                $output->write('x');
            }

            $nb++;

            if($nb%50 == 0) {
                $em->clear();
            }
        }

        $duration = time() - $time_begin;

        $output->writeln('');
        $output->writeln('Done. Duration: '.$duration.'s');
    }
}
