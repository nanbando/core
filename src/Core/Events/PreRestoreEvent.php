<?php

namespace Nanbando\Core\Events;

use League\Flysystem\Filesystem;
use Nanbando\Core\Database\ReadonlyDatabase;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event args for pre-restore.
 */
class PreRestoreEvent extends Event
{
    use CancelTrait;

    /**
     * @var ReadonlyDatabase
     */
    private $systemDatabase;

    /**
     * @var Filesystem
     */
    private $source;

    /**
     * @var Filesystem
     */
    private $destination;

    /**
     * @var array
     */
    private $backup;

    /**
     * @param array $backup
     * @param ReadonlyDatabase $systemDatabase
     * @param Filesystem $source
     * @param Filesystem $destination
     */
    public function __construct(array $backup, ReadonlyDatabase $systemDatabase, Filesystem $source, Filesystem $destination)
    {
        $this->backup = $backup;
        $this->systemDatabase = $systemDatabase;
        $this->source = $source;
        $this->destination = $destination;
    }

    /**
     * Returns systemDatabase.
     *
     * @return ReadonlyDatabase
     */
    public function getSystemDatabase()
    {
        return $this->systemDatabase;
    }

    /**
     * Returns source.
     *
     * @return Filesystem
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Returns destination.
     *
     * @return Filesystem
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Returns backup.
     *
     * @return array
     */
    public function getBackup()
    {
        return $this->backup;
    }

    /**
     * Set backup.
     *
     * @param array $backup
     *
     * @return $this
     */
    public function setBackup(array $backup)
    {
        $this->backup = $backup;

        return $this;
    }
}
