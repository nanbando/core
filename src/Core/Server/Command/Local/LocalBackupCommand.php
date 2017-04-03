<?php

namespace Nanbando\Core\Server\Command\Local;

use Emgag\Flysystem\Hash\HashPlugin;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;
use Nanbando\Core\BackupStatus;
use Nanbando\Core\Database\DatabaseFactory;
use Nanbando\Core\Events\BackupEvent;
use Nanbando\Core\Events\Events;
use Nanbando\Core\Events\PostBackupEvent;
use Nanbando\Core\Events\PreBackupEvent;
use Nanbando\Core\Flysystem\PrefixAdapter;
use Nanbando\Core\Flysystem\ReadonlyAdapter;
use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Storage\StorageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Create a new local backup-archive.
 */
class LocalBackupCommand implements CommandInterface
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
    public function interact(InputInterface $input, OutputInterface $output)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options = [])
    {
        $label = array_key_exists('label', $options) ? $options['label'] : '';
        $message = array_key_exists('message', $options) ? $options['message'] : '';
        $process = array_key_exists('process', $options) ? $options['process'] : null;

        $destination = $this->storage->start();

        $source = new Filesystem(new ReadonlyAdapter(new Local(realpath('.'))));
        $source->addPlugin(new ListFiles());
        $source->addPlugin(new HashPlugin());

        $systemDatabase = $this->databaseFactory->create();
        $systemDatabase->set('label', $label);
        $systemDatabase->set('message', $message);
        $systemDatabase->set('started', (new \DateTime())->format(\DateTime::RFC3339));

        if ($process) {
            $systemDatabase->set('process', implode(',', $process));
        }

        $event = new PreBackupEvent($this->backup, $systemDatabase, $source, $destination);
        $this->eventDispatcher->dispatch(Events::PRE_BACKUP_EVENT, $event);
        if ($event->isCanceled()) {
            $this->storage->cancel($destination);

            return BackupStatus::STATE_FAILED;
        }

        $status = BackupStatus::STATE_SUCCESS;
        $backupConfig = $event->getBackup();
        foreach ($backupConfig as $backupName => $backup) {
            if (0 !== count($process)
                && 0 !== count($backup['process'])
                && 0 === count(array_intersect($backup['process'], $process))
            ) {
                continue;
            }

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

        return $status;
    }
}
