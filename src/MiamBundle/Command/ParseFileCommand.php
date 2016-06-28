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
            ->setDescription('Parse a file')
            ->addArgument('filename', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $output->writeln('Parsing...');

        $filename = $input->getArgument('filename');

        $this->getContainer()->get('data_parsing')->parseFile($filename);

        $output->writeln('');
        $output->writeln('End');
    }
}