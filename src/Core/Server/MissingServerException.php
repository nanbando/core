<?php

namespace Nanbando\Core\Server;

/**
 * Indicates missing server.
 */
class MissingServerException extends \Exception
{
    /**
     * @var string
     */
    private $serverName;

    /**
     * @param string $serverName
     */
    public function __construct($serverName)
    {
        parent::__construct(sprintf('Server "%s" not found', $serverName));

        $this->serverName = $serverName;
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
}
