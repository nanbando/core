<?php

namespace Nanbando\Core;

use Emgag\Flysystem\Hash\HashPlugin;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;
use Nanbando\Core\Database\DatabaseFactory;
use Nanbando\Core\Events\BackupEvent;
use Nanbando\Core\Events\Events;
use Nanbando\Core\Events\PostBackupEvent;
use Nanbando\Core\Events\PreBackupEvent;
use Nanbando\Core\Events\PreRestoreEvent;
use Nanbando\Core\Events\RestoreEvent;
use Nanbando\Core\Flysystem\PrefixAdapter;
use Nanbando\Core\Flysystem\ReadonlyAdapter;
use Nanbando\Core\Storage\StorageInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Core service.
 */
class Nanbando
{
    /**
     * @var array
     */
    private $backup;

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
     * @param array $backup
     * @param StorageInterface $storage
     * @param DatabaseFactory $databaseFactory
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        array $backup,
        StorageInterface $storage,
        DatabaseFactory $databaseFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->backup = $backup;
        $this->storage = $storage;
        $this->databaseFactory = $databaseFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Backup process.
     *
     * @param string $label
     * @param string $message
     *
     * @return int
     */
    public function backup($label = '', $message = '')
    {
        $destination = $this->storage->start();

        $source = new Filesystem(new ReadonlyAdapter(new Local(realpath('.'))));
        $source->addPlugin(new ListFiles());
        $source->addPlugin(new HashPlugin());

        $systemDatabase = $this->databaseFactory->create();
        $systemDatabase->set('label', $label);
        $systemDatabase->set('message', $message);
        $systemDatabase->set('started', (new \DateTime())->format(\DateTime::RFC3339));

        $event = new PreBackupEvent($this->backup, $systemDatabase, $source, $destination);
        $this->eventDispatcher->dispatch(Events::PRE_BACKUP_EVENT, $event);
        if ($event->isCanceled()) {
            $this->storage->cancel($destination);

            return BackupStatus::STATE_FAILED;
        }

        $status = BackupStatus::STATE_SUCCESS;
        $backupConfig = $event->getBackup();
        foreach ($backupConfig as $backupName => $backup) {
            $backupDestination = new Filesystem(new PrefixAdapter('backup/' . $backupName, $destination->getAdapter()));
            $backupDestination->addPlugin(new ListFiles());
            $backupDestination->addPlugin(new HashPlugin());

            $database = $this->databaseFactory->create();

            $event = new BackupEvent(
                $systemDatabase, $database, $source, $backupDestination, $backupName, $backup
            );
            $this->eventDispatcher->dispatch(Events::BACKUP_EVENT, $event);
            if ($event->isCanceled()) {
                $this->storage->cancel($destination);

                return BackupStatus::STATE_FAILED;
            }

            if ($event->getStatus() === BackupStatus::STATE_FAILED) {
                $status = BackupStatus::STATE_PARTIALLY;
            }

            $encodedData = json_encode($database->getAll(), JSON_PRETTY_PRINT);
            $destination->put(sprintf('database/backup/%s.json', $event->getName()), $encodedData);
        }

        $systemDatabase->set('finished', (new \DateTime())->format(\DateTime::RFC3339));
        $systemDatabase->set('state', $status);

        $encodedSystemData = json_encode($systemDatabase->getAll(), JSON_PRETTY_PRINT);
        $destination->put('database/system.json', $encodedSystemData);

        $name = $this->storage->close($destination);

        $this->eventDispatcher->dispatch(Events::POST_BACKUP_EVENT, new PostBackupEvent($name, $status));

        echo(memory_get_peak_usage() / (1024 * 1024));

        return $status;
    }

    /**
     * Restore process.
     *
     * @param string $name
     */
    public function restore($name)
    {
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
