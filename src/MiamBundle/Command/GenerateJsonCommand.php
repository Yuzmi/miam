<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateJsonCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:generate:json')
            ->setDescription('Génère le fichier JSON des flux à récupérer')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $output->write('Generation... ');

        $this->getContainer()->get('data_parsing')->generateJson();

        $output->writeln('Done.');
    }
}