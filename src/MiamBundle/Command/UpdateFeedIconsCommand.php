<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateFeedIconsCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:update:feeds:icons')
            ->setDescription('Met à jour les icônes des flux')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $output->write('Update feed icons... ');

        $this->getContainer()->get('data_parsing')->updateFeedIcons();

        $output->writeln('Done.');
    }
}