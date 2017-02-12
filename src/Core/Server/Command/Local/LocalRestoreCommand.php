<?php

namespace Nanbando\Core\Server\Command\Local;

use Emgag\Flysystem\Hash\HashPlugin;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;
use Nanbando\Core\Database\DatabaseFactory;
use Nanbando\Core\Events\Events;
use Nanbando\Core\Events\PreRestoreEvent;
use Nanbando\Core\Events\RestoreEvent;
use Nanbando\Core\Flysystem\PrefixAdapter;
use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Storage\StorageInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Restore a backup-archive.
 */
class LocalRestoreCommand implements CommandInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var DatabaseFactory
     */
    private $databaseFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $backup;

    /**
     * @param StorageInterface $storage
     * @param DatabaseFactory $databaseFactory
     * @param EventDispatcherInterface $eventDispatcher
     * @param array $backup
     */
    public function __construct(
        StorageInterface $storage,
        DatabaseFactory $databaseFactory,
        EventDispatcherInterface $eventDispatcher,
        array $backup
    ) {
        $this->storage = $storage;
        $this->databaseFactory = $databaseFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->backup = $backup;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options)
    {
        $name = $options['name'];

        $source = $this->storage->open($name);

        $destination = new Filesystem(new Local(realpath('.'), LOCK_EX, null));
        $destination->addPlugin(new ListFiles());
        $destination->addPlugin(new HashPlugin());

        $systemData = json_decode($source->read('database/system.json'), true);
        $systemDatabase = $this->databaseFactory->createReadonly($systemData);

        $event = new PreRestoreEvent($this->backup, $systemDatabase, $source, $destination);
        $this->eventDispatcher->dispatch(Events::PRE_RESTORE_EVENT, $event);
        if ($event->isCanceled()) {
            return;
        }

        $backupConfig = $event->getBackup();
        foreach ($backupConfig as $backupName => $backup) {
            $backupSource = new Filesystem(new PrefixAdapter('backup/' . $backupName, $source->getAdapter()));
            $backupSource->addPlugin(new ListFiles());
            $backupSource->addPlugin(new HashPlugin());

            $data = json_decode($source->read(sprintf('database/backup/%s.json', $backupName)), true);
            $database = $this->databaseFactory->createReadonly($data);

            $event = new RestoreEvent(
                $systemDatabase, $database, $backupSource, $destination, $backupName, $backup
            );
            $this->eventDispatcher->dispatch(Events::RESTORE_EVENT, $event);
        }

        $this->eventDispatcher->dispatch(Events::POST_RESTORE_EVENT, new Event());
    }
}
