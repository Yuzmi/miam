<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseFilesCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:parse:files')
            ->setDescription('Traite tous les fichiers stockés')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Parsing...');

        error_reporting(0);

        $time_begin = time();
        $this->getContainer()->get('data_parsing')->parseFiles(array('verbose' => true));
        $duration = time() - $time_begin;

        $output->writeln('');
        $output->writeln('End - Duration: '.$duration.'s');
    }
}