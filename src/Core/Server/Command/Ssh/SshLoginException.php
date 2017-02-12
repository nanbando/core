<?php

namespace Nanbando\Core\Server\Command\Ssh;

/**
 * Indicates ssh login exception.
 */
class SshLoginException extends \Exception
{
    /**
     * @var string
     */
    private $serverName;

    /**
     * @param string $host
     */
    public function __construct($host)
    {
        parent::__construct(sprintf('Cannot login to server "%s"', $host));

        $this->serverName = $host;
    }

    /**
     * Returns host.
     *
     * @return string
     */
    public function getServerName()
    {
        return $this->serverName;
    }
}
