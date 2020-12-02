<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Server\ServerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Base class for command which use server-registry.
 */
abstract class BaseServerCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->getCommand($input)->interact($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getCommand($input)->execute($this->getCommandOptions($input));

        return 1;
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
        $serverRegistry = $this->container->get('nanbando.server_registry');

        return $serverRegistry->getCommand($this->getServerName($input), $this->getName());
    }
}
