<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseFileCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:parse:file')
            ->setDescription('Traite un fichier donné')
            ->addArgument('filename', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Parsing...');

        error_reporting(0);

        $filename = $input->getArgument('filename');

        $this->getContainer()->get('data_parsing')->parseFile($filename);

        $output->writeln('');
        $output->writeln('End');
    }
}