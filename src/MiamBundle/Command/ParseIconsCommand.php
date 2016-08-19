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

        $output->write('Parsing icons... ');

        $this->getContainer()->get('data_parsing')->parseIcons();

        $output->writeln('Done.');
    }
}