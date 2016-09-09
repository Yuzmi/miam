<?php

namespace MiamBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckRequirementsCommand extends ContainerAwareCommand {
	protected function configure() {
        $this
            ->setName('miam:requirements:check')
            ->setDescription("Check requirements")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        error_reporting(0);

        $php_version = phpversion();
        if(function_exists('version_compare') && version_compare($php_version, '5.5.9', '>=')) {
            $output->writeln("<info>PHP ".$php_version." installed</info>");
        } else {
            $output->writeln("<error>PHP ".$php_version." installed - PHP 5.5.9 or newer required</error>");
        }

        $extensions = array('curl', 'iconv', 'imagick', 'json', 'mbstring', 'pcre', 'PDO', 'tidy', 'xml', 'xmlreader', 'zlib');
        $loaded_extensions = get_loaded_extensions();
        
        $missing = false;
        foreach($extensions as $ext) {
            if(!in_array($ext, $loaded_extensions)) {
                $output->writeln("<error>".$ext." extension is not loaded</error>");
                $missing = true;
            }
        }

        if(!$missing) {
            $output->writeln("<info>All required extensions are loaded</info>");
        }
    }
}