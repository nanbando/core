<?php

namespace Nanbando\Core\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event args for post-backup.
 */
class PostBackupEvent extends Event
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $status;

    /**
     * @param string $name
     * @param string $status
     */
    public function __construct($name, $status)
    {
        $this->name = $name;
        $this->status = $status;
    }

    /**
     * Returns name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}
