<?php

namespace Sysgear\Symfony\Test;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Finder\Finder;

class Util
{
    /**
     * Execute symfony command.
     * 
     * @param \Symfony\Bundle\FrameworkBundle\Console\Application $application
     * @param string $argv
     */
    public static function executeCommand($app, $argv)
    {
        $argv_first = explode(' ', $argv, 2);
        $command = $app->findCommand($argv_first[0]);
        $input = new StringInput($argv);
        $output = new NullOutput();
        $command->run($input, $output);
    }

    /**
     * Return kernel directory.
     * 
     * @return string
     */
    public static function getKernelDir()
    {
        $dir = getcwd();
        if (!isset($_SERVER['argv']) || false === strpos($_SERVER['argv'][0], 'phpunit')) {
            throw new \RuntimeException('Can not find phpunit in ARGV to discover relative kernel path.');
        }

        // find the --configuration flag from PHPUnit
        $cli = implode(' ', $_SERVER['argv']);
        if (preg_match('/\-\-configuration[= ]+([^ ]+)/', $cli, $matches)) {
            $dir = $dir.'/'.$matches[1];
        } elseif (preg_match('/\-c +([^ ]+)/', $cli, $matches)) {
            $dir = $dir.'/'.$matches[1];
        } else {
            throw new \RuntimeException('Unable to guess the Kernel directory.');
        }

        if (!is_dir($dir)) {
            $dir = dirname($dir);
        }
        
        return $dir;
    }
}