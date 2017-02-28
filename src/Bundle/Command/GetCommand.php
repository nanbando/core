<?php

namespace Nanbando\Bundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Get specified backup from another server.
 */
class GetCommand extends BaseServerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('get')
            ->addArgument('source-server', InputArgument::REQUIRED, 'Source of backup which should be loaded.')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of loading backup.')
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
