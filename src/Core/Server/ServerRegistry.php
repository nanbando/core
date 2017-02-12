<?php

namespace Nanbando\Core\Server;

use Nanbando\Core\Server\Command\CommandInterface;

class ServerRegistry
{
    /**
     * @var CommandInterface[]
     */
    private $servers;

    /**
     * @param CommandInterface[] $servers
     */
    public function __construct(array $servers)
    {
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
        if (!array_key_exists($index, $this->servers)) {
            throw new \Exception();
        }

        return $this->servers[$index];
    }
}
