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
            ->addOption('ignore-invalid', null, InputOption::VALUE_NONE, "Ignore invalid feeds")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $time_start = time();

        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $feeds = array();

        $arg = trim($input->getArgument('feeds'));
        $feeds = $this->getContainer()->get('feed_manager')->getFeeds($arg);

        if(is_null($feeds)) {
            if(filter_var($arg, FILTER_VALIDATE_URL) !== false || intval($arg) > 0) {
                $output->writeln('Feed unknown');
            } else {
                $output->writeln('Wrong argument');
            }

            return;
        }

        $countFeeds = count($feeds);
        $countParsedFeeds = 0;
        $countValidFeeds = 0;
        $countNewItems = 0;
        $errors = array();
        $options = array();

        $uniqueFeed = false;
        if($countFeeds == 1) {
            $uniqueFeed = true;
        }

        if($input->getOption('no-cache')) {
            $options['cache'] = false;
        }

        if($input->getOption('timeout')) {
            $options['timeout'] = $input->getOption('timeout');
        }

        if($countFeeds > 0) {
            $output->writeln('Fetching and parsing...');

            foreach($feeds as $f) {
                $feed = $em->getRepository('MiamBundle:Feed')->find($f->getId());
                if(!$feed) {
                    continue;
                }

                if($input->getOption('ignore-invalid') && $feed->getErrorCount() > 0) {
                    continue;
                }

                $result = $this->getContainer()->get('data_parsing')->parseFeed($feed, $options);
                
                if($result['success']) {
                    $countNewItems += $result['countNewItems'];

                    $countValidFeeds++;

                    if(!$uniqueFeed) {
                        if($result['countNewItems'] > 0) {
                            $output->write('+');
                        } else {
                            $output->write('-');
                        }
                    }
                } else {
                    $errors[] = $feed->getId().' - '.$feed->getUrl().' - '.$result['error'];

                    if(!$uniqueFeed) {
                        $output->write('x');
                    }
                }

                $countParsedFeeds++;

                if($countParsedFeeds%20 == 0) {
                    $em->clear();
                }
            }
        }

        if($countParsedFeeds > 0) {
            if($uniqueFeed) {
                if($countValidFeeds > 0) {
                    $output->write('Valid feed - ');
                } else {
                    $output->write('Invalid feed - ');
                }
            } else {
                $output->writeln('');
                $output->write('Valid feeds: '.$countValidFeeds.'/'.$countParsedFeeds.' - ');
            }

            if($countValidFeeds > 0) {
                $output->write('New item(s): '.$countNewItems.' - ');
            }

            $duration = time() - $time_start;
            $output->writeln('Duration: '.$duration.'s');

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
        } else {
            $output->writeln('Nothing happened');
        }
    }
}