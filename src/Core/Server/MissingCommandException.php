<?php

namespace Nanbando\Core\Server;

/**
 * Indicates missing command.
 */
class MissingCommandException extends \Exception
{
    /**
     * @var string
     */
    private $serverName;

    /**
     * @var string
     */
    private $commandName;

    /**
     * @param string $serverName
     * @param string $commandName
     */
    public function __construct($serverName, $commandName)
    {
        parent::__construct(sprintf('Command "%s" for server "%s" not supported.', $commandName, $serverName));

        $this->serverName = $serverName;
        $this->commandName = $commandName;
    }

    /**
     * Returns serverName.
     *
     * @return string
     */
    public function getServerName()
    {
        return $this->serverName;
    }

    /**
     * Returns commandName.
     *
     * @return string
     */
    public function getCommandName()
    {
        return $this->commandName;
    }
}
