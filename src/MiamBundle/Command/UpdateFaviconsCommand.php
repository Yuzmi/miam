<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateFaviconsCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:update:favicons')
            ->setDescription('Update favicons')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $output->write('Update favicons... ');

        $this->getContainer()->get('data_parsing')->updateFavicons();

        $output->writeln('Done.');
    }
}