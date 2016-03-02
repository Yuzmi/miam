<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseAllCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:parse:all')
            ->setDescription('Récupère tous les flux')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Fetching and parsing...');

        error_reporting(0);

        $time_begin = time();
        $this->getContainer()->get('data_parsing')->parseAll(array('verbose' => true));
        $duration = time() - $time_begin;

        $output->writeln('');
        $output->writeln('End - Duration: '.$duration.'s');
    }
}