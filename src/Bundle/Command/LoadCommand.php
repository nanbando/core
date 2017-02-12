<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Server\ServerRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Load given backup from another server.
 */
class LoadCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('load')
            ->addArgument('source-server', InputArgument::REQUIRED, 'Source of backup which should be loaded.')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of loading backup.')
            ->setDescription('Load given backup from another server.')
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ServerRegistry $serverRegistry */
        $serverRegistry = $this->getContainer()->get('nanbando.server_registry');
        $command = $serverRegistry->getCommand($input->getArgument('source-server'), 'load');

        // TODO server does not provide load command

        $command->execute(['name' => $input->getArgument('name')]);
    }
}
