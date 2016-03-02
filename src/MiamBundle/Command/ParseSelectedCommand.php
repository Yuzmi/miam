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
        $output->writeln('Fetching and parsing...');

        error_reporting(0);

        $time_begin = time();
        $this->getContainer()->get('data_parsing')->parseSelected(array('verbose' => true));
        $duration = time() - $time_begin;

        $output->writeln('');
        $output->writeln('End - Duration: '.$duration.'s');
    }
}