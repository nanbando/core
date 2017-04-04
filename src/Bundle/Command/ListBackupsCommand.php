<?php

namespace Nanbando\Bundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Lists available backups.
 */
class ListBackupsCommand extends BaseServerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('list:backups')
            ->addOption('server', 's', InputOption::VALUE_REQUIRED, 'Where should the command be called.', 'local')
            ->addOption('remote', 'r', InputOption::VALUE_NONE, 'Lists backups on remote storage.')
            ->setDescription('List all available backups.')
            ->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> command lists all available backups from local.

With the options <info>-s</info> or <info>-r</info> lists backups on servers or remote storage. 

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getServerName(InputInterface $input)
    {
        return $input->getOption('server');
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandOptions(InputInterface $input)
    {
        return ['remote' => $input->getOption('remote')];
    }
}
