<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddAdminCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:admin:add')
            ->setDescription("Add an admin")
            ->addArgument('user', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $userArgument = $input->getArgument('user');

        $em = $this->getContainer()->get('doctrine')->getManager();

        $user = $em->getRepository('MiamBundle:User')->findOneByUsername($userArgument);
        if(!$user) {
            $output->writeln('User not found.');
        } elseif($user->getIsAdmin()) {
            $output->writeln($user->getUsername().' is already admin.');
        } else {
            $user->setIsAdmin(true);
            $em->persist($user);
            $em->flush();

            $output->writeln($user->getUsername().' is now admin.');
        }
    }
}