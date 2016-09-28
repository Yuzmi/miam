<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ParseFeedsCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:parse:feeds')
            ->setDescription('Parse feeds')
            ->addArgument('feeds', InputArgument::OPTIONAL, "Which feeds will you parse ?")
            ->addOption('list-errors', null, InputOption::VALUE_NONE, "List errors at the end")
            ->addOption('no-cache', null, InputOption::VALUE_NONE, "Disable the cache")
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, "Set the timeout to fetch a feed (seconds)")
            ->addOption('ignore-invalid', null, Inputoption::VALUE_NONE, "Ignore invalid feeds")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $time_start = time();

        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $arg = $input->getArgument('feeds');
        if($arg == 'catalog') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findCatalog();
        } elseif($arg == 'subscribed') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findSubscribed();
        } elseif($arg == 'used') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findUsed();
        } elseif($arg == 'unused') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findUnused();
        } else {
            $feeds = $em->getRepository('MiamBundle:Feed')->findAll();
        }

        $countFeeds = 0;
        $countValidFeeds = 0;
        $countNewItems = 0;
        $errors = array();
        $options = array();

        if($input->getOption('no-cache')) {
            $options['cache'] = false;
        }

        if($input->getOption('timeout')) {
            $options['timeout'] = $input->getOption('timeout');
        }

        $output->writeln('Fetching and parsing...');

        $nb = 0;
        foreach($feeds as $f) {
            $feed = $em->getRepository('MiamBundle:Feed')->find($f->getId());
            if(!$feed) {
                continue;
            }

            $parse = true;
            if($input->getOption('ignore-invalid') && $feed->getErrorCount() > 0) {
                $parse = false;
            }

            if($parse) {
                $result = $this->getContainer()->get('data_parsing')->parseFeed($feed, $options);
                if($result['success']) {
                    if($result['countNewItems'] > 0) {
                        $output->write('+');

                        $countNewItems += $result['countNewItems'];
                    } else {
                        $output->write('-');
                    }

                    $countValidFeeds++;
                } else {
                    $output->write('x');

                    $errors[] = $feed->getId().' - '.$feed->getUrl().' - '.$result['error'];
                }

                $countFeeds++;

                $nb++;

                if($nb%20 == 0) {
                    $em->clear();
                }
            }
        }

        $duration = time() - $time_start;

        $output->writeln('');
        $output->writeln('Valid feeds: '.$countValidFeeds.'/'.$countFeeds.' - New item(s): '.$countNewItems.' - Duration: '.$duration.'s');

        if($input->getOption('list-errors')) {
            if(count($errors) > 0) {
                $output->writeln('Errors:');

                foreach($errors as $error) {
                    $output->writeln($error);
                }
            } else {
                $output->writeln('No error occured');
            }
        }
    }
}