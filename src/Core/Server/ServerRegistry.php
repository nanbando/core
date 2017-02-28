<?php

namespace Nanbando\Core\Server;

use Nanbando\Core\Server\Command\CommandInterface;

/**
 * Registry for known commands.
 */
class ServerRegistry
{
    /**
     * @var CommandInterface[]
     */
    private $commands;

    /**
     * @var array
     */
    private $servers;

    /**
     * @param CommandInterface[] $commands
     * @param array $servers
     */
    public function __construct(array $commands, array $servers)
    {
        $this->commands = $commands;
        $this->servers = $servers;
    }

    /**
     * Returns command.
     *
     * @param string $serverName
     * @param string $commandName
     *
     * @return CommandInterface
     *
     * @throws \Exception
     */
    public function getCommand($serverName, $commandName)
    {
        $index = $serverName . '::' . $commandName;
        if (!array_key_exists($index, $this->commands)) {
            if (!in_array($serverName, $this->servers)) {
                throw new MissingServerException($serverName);
            }

            throw new MissingCommandException($serverName, $commandName);
        }

        return $this->commands[$index];
    }
}
