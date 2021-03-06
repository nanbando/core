<?php

namespace Nanbando\Bundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Get specified backup from another server.
 */
class GetCommand extends BaseServerCommand
{
    protected static $defaultName = 'get';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::$defaultName)
            ->addArgument('source-server', InputArgument::REQUIRED, 'Source of backup which should be loaded.')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of loading backup.')
            ->addOption('latest', null, InputOption::VALUE_NONE, 'Loads the latest file.')
            ->setDescription('Get specified backup from another server.')
            ->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> command load a backup archive 
from another server and store it locally.

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getServerName(InputInterface $input)
    {
        return $input->getArgument('source-server');
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandOptions(InputInterface $input)
    {
        return ['name' => $input->getArgument('name')];
    }
}
