<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Server\ServerRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for command which use server-registry.
 */
abstract class BaseServerCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->getCommand($input)->interact();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getCommand($input)->execute($this->getCommandOptions($input));
    }

    /**
     * Returns server-name.
     *
     * @param InputInterface $input
     *
     * @return string
     */
    abstract protected function getServerName(InputInterface $input);

    /**
     * Returns command options.
     *
     * @param InputInterface $input
     *
     * @return array
     */
    abstract protected function getCommandOptions(InputInterface $input);

    /**
     * Returns command.
     *
     * @param InputInterface $input
     *
     * @return CommandInterface
     */
    protected function getCommand(InputInterface $input)
    {
        /** @var ServerRegistry $serverRegistry */
        $serverRegistry = $this->getContainer()->get('nanbando.server_registry');

        return $serverRegistry->getCommand($this->getServerName($input), $this->getName());
    }
}
