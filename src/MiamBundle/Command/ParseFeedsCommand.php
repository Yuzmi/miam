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
        $feeds = array();
        $uniqueFeed = false;

        $arg = $input->getArgument('feeds');
        if($arg == 'all' || is_null($arg)) {
            $feeds = $em->getRepository('MiamBundle:Feed')->findAll();
        } elseif($arg == 'catalog') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findCatalog();
        } elseif($arg == 'subscribed') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findSubscribed();
        } elseif($arg == 'used') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findUsed();
        } elseif($arg == 'unused') {
            $feeds = $em->getRepository('MiamBundle:Feed')->findUnused();
        } elseif(filter_var($arg, FILTER_VALIDATE_URL) !== false) {
            $feed = $this->getContainer()->get('feed_manager')->findFeedForUrl($arg);
            if($feed) {
                $feeds[] = $feed;
                $uniqueFeed = true;
            } else {
                $output->writeln('Feed unknown');
                return;
            }
        } elseif(intval($arg) > 0) {
            $feed = $em->getRepository('MiamBundle:Feed')->find(intval($arg));
            if($feed) {
                $feeds[] = $feed;
                $uniqueFeed = true;
            } else {
                $output->writeln('ID unknown');
                return;
            }
        } else {
            $output->writeln('WTF do you mean ?');
            return;
        }

        $countFeeds = count($feeds);
        $countParsedFeeds = 0;
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

        $duration = time() - $time_start;

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