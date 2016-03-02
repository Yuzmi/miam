<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateFeedsCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:feeds:update')
            ->setDescription('Met à jour les informations des flux')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->write('Update... ');

        error_reporting(0);

        $this->getContainer()->get('feed_manager')->updateFeeds();

        $output->writeln('Done.');
    }
}