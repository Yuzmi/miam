<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateFaviconCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:update:favicon')
            ->setDescription("Update a feed's favicon")
            ->addArgument('feed', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $feedArgument = $input->getArgument('feed');

        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        
        $feed = null;
        if(filter_var($feedArgument, FILTER_VALIDATE_URL) !== false) {
            $feed = $em->getRepository('MiamBundle:Feed')->findOneByUrl($feedArgument);
        } else {
            $feed = $em->getRepository('MiamBundle:Feed')->find(intval($feedArgument));
        }

        if($feed) {
            $output->write('Update favicon... ');

            $this->getContainer()->get('data_parsing')->updateFavicon($feed);

            $output->writeln('Done.');
        } else {
            $output->writeln('Feed unknown...');
        }
    }
}