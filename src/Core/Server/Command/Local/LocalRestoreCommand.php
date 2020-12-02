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
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

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
    public function interact(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('file')) {
            return;
        }

        $localFiles = $this->storage->localListing();

        if ($input->getOption('latest') && count($localFiles) > 0) {
            return $input->setArgument('file', end($localFiles));
        } elseif (count($localFiles) === 1) {
            return $input->setArgument('file', $localFiles[0]);
        }

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion('Which backup', $localFiles);
        $question->setErrorMessage('Backup %s is invalid.');
        $question->setAutocompleterValues([]);

        $input->setArgument('file', $helper->ask($input, $output, $question));
        $output->writeln('');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options = [])
    {
        $name = $options['name'];

        $source = $this->storage->open($name);

        $destination = new Filesystem(new Local(realpath('.'), LOCK_EX, null));
        $destination->addPlugin(new ListFiles());
        $destination->addPlugin(new HashPlugin());

        $systemData = json_decode($source->read('database/system.json'), true);
        $systemDatabase = $this->databaseFactory->createReadonly($systemData);

        $event = new PreRestoreEvent($this->backup, $systemDatabase, $source, $destination);
        $this->eventDispatcher->dispatch($event, Events::PRE_RESTORE_EVENT);
        if ($event->isCanceled()) {
            return;
        }

        $process = array_filter(explode(',', $systemDatabase->getWithDefault('process', '')));

        $backupConfig = $event->getBackup();
        foreach ($backupConfig as $backupName => $backup) {
            if (0 !== count($process)
                && 0 !== count($backup['process'])
                && 0 === count(array_intersect($backup['process'], $process))
            ) {
                continue;
            }

            $backupSource = new Filesystem(new PrefixAdapter('backup/' . $backupName, $source->getAdapter()));
            $backupSource->addPlugin(new ListFiles());
            $backupSource->addPlugin(new HashPlugin());

            $data = json_decode($source->read(sprintf('database/backup/%s.json', $backupName)), true);
            $database = $this->databaseFactory->createReadonly($data);

            $event = new RestoreEvent(
                $systemDatabase, $database, $backupSource, $destination, $backupName, $backup
            );
            $this->eventDispatcher->dispatch($event, Events::RESTORE_EVENT);
        }

        $this->eventDispatcher->dispatch(new Event(), Events::POST_RESTORE_EVENT);
    }
}
