<?php

namespace Nanbando\Bundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class RestoreCommand extends BaseServerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('restore')
            ->setDescription('Restore a backup archive.')
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'Defines which file should be restored (backup-name or absolute path to zip file).'
            )
            ->addOption('server', 's', InputOption::VALUE_REQUIRED, 'Where should the command be called.', 'local')
            ->addOption('latest', null, InputOption::VALUE_NONE, 'Loads the latest file.')
            ->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> restores a backup archive.

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
        return ['name' => $input->getArgument('file')];
    }
}
