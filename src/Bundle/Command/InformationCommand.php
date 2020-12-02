<?php

namespace Nanbando\Bundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command prints information for an existing backup.
 */
class InformationCommand extends BaseServerCommand
{
    protected static $defaultName = 'information';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Fetches backup archives from remote storage.')
            ->addArgument('file', InputArgument::OPTIONAL, 'Defines which file should be used to display information.')
            ->addOption('latest', null, InputOption::VALUE_NONE, 'Uses latest file.')
            ->addOption('server', 's', InputOption::VALUE_REQUIRED, 'Where should the command be called.', 'local')
            ->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> displays information for given backup archive.

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
        return ['file' => $input->getArgument('file')];
    }
}
