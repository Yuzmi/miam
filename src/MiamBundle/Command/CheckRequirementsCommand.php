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
            ->setName('miam:requirements')
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

        $missing = false;

        $extensions = array(
            'ctype' => 'ctype',
            'curl' => 'cURL',
            'dom' => 'DOM',
            'iconv' => 'Iconv',
            'json' => 'JSON',
            'imagick' => 'Imagick',
            'libxml' => 'libxml',
            'mbstring' => 'mbstring',
            'pcre' => 'PCRE',
            'PDO' => 'PDO',
            'session' => 'session',
            'SimpleXML' => 'SimpleXML',
            'tidy' => 'Tidy',
            'tokenizer' => 'Tokenizer',
            'xml' => 'XML',
            'zlib' => 'Zlib'
        );

        foreach($extensions as $key => $ext) {
            if(extension_loaded($key)) {
                $output->writeln("<info>".$ext." extension is loaded</info>");
            } else {
                $output->writeln("<error>".$ext." extension is not loaded</error>");
                $missing = true;
            }
        }

        if($missing) {
            $output->writeln("<bg=red;fg=white;options=bold>One or more required extensions are not loaded</>");
            $output->writeln("<bg=red;fg=white;options=bold>You should install any missing extension or it may crash later</>");
        } else {
            $output->writeln("<bg=green;fg=white;options=bold>All required extensions are loaded</>");
        }
    }
}