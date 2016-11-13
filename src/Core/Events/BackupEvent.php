<?php

namespace Nanbando\Core\Events;

use League\Flysystem\Filesystem;
use Nanbando\Core\Database\Database;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event args for backup event.
 */
class BackupEvent extends Event
{
    use CancelTrait;

    /**
     * @var Database
     */
    private $systemDatabase;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var Filesystem
     */
    private $source;

    /**
     * @var Filesystem
     */
    private $destination;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $options;

    /**
     * @var int
     */
    private $status;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @param Database $systemDatabase
     * @param Database $database
     * @param Filesystem $source
     * @param Filesystem $destination
     * @param string $name
     * @param array $parameter
     */
    public function __construct(
        Database $systemDatabase,
        Database $database,
        Filesystem $source,
        Filesystem $destination,
        $name,
        array $parameter
    ) {
        $this->systemDatabase = $systemDatabase;
        $this->database = $database;
        $this->source = $source;
        $this->destination = $destination;
        $this->name = $name;
        $this->options = $parameter;
    }

    /**
     * Returns systemDatabase.
     *
     * @return Database
     */
    public function getSystemDatabase()
    {
        return $this->systemDatabase;
    }

    /**
     * Returns database.
     *
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
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
     * Returns name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns parameter.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name, $default = null)
    {
        if (!array_key_exists($name, $this->options)) {
            return $default;
        }

        return $this->options[$name];
    }

    /**
     * Returns status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Returns exception.
     *
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Set exception.
     *
     * @param \Exception $exception
     *
     * @return $this
     */
    public function setException($exception)
    {
        $this->exception = $exception;

        return $this;
    }
}
