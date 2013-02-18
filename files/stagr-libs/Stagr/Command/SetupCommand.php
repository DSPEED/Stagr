<?php

/*
 * This file is part of the Stagr framework.
 *
 * (c) Gabriel Manricks <gmanricks@me.com>
 * (c) Ulrich Kautz <ulrich.kautz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stagr\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Shell;
use Stagr\Tools\Setup;

/**
 * Example command for testing purposes.
 */
class SetupCommand extends _Command
{


    protected function configure()
    {
        $this
            ->setName('setup')
            ->setDescription('Setup or update an App')
            ->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Setup::printLogo('Setup');

        // check root
        if (posix_geteuid() !== 0) {
            throw new \LogicException("Use 'sudo stagr'!");
        }

        // read app name
        $appCheck = function ($in) {
            $l = strlen($in);
            return $l > 1 && $l <= 16 && preg_match('/^(?:[a-z0-9]+\-?)+[a-z0-9]$/', $in);
        };
        $appName = $input->getArgument('name');
        if (!$appName || !$appCheck($appName)) {
            $appName = $this->readStdin($output, 'App Name> ', $appCheck, false, 'Invalid name, try again, use [a-z0-9-]');
        }

        $setup = new Setup($appName, $output, $this);
        $app = $this->getApplication()->getContainer();
        $setup->initEmailAndSsh();

        //Set Defaults
        $defaults = array(
                      'env' => array(),
                  'webcall' => false,
                 'timezone' => 'Europe/Berlin',
                'exec-time' => 300,
             'memory-limit' => '64M',
              'upload-size' => '128M',
                'post-size' => '128M',
               'short-tags' => 'ON',
         'output-buffering' => 4096,
                  'phalcon' => false,
                      'yaf' => false
        );

        $app->configParam($appName, $defaults);

        // setup all
        $output->writeln("\n\nSetup {$appName}\n----------");

        $output->writeln("\n# Webserver");
        $setup->setupWebserver();

        $output->writeln("\n# MySQL");
        $setup->setupMySQL();

        $output->writeln("\n# Git");
        $setup->setupGit();

        // print info
        $output->writeln("\n");
        $setup->printIpInfo();
        $output->writeln("");
        $setup->printGitInfo();
        $output->writeln("");
        $setup->printMySQLInfo();
        $output->writeln("\n");
    }
}
