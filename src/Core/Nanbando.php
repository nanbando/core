<?php

namespace Nanbando\Core;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;
use Nanbando\Core\Database\DatabaseFactory;
use Nanbando\Core\Environment\EnvironmentInterface;
use Nanbando\Core\Flysystem\PrefixAdapter;
use Nanbando\Core\Flysystem\ReadonlyAdapter;
use Nanbando\Core\Plugin\PluginRegistry;
use Nanbando\Core\Storage\StorageInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Core service.
 */
class Nanbando
{
    /**
     * State indicates successful backup.
     */
    const STATE_SUCCESS = 1;

    /**
     * State indicates failed backup.
     */
    const STATE_FAILED = 2;

    /**
     * State indicates partially-finished backup.
     */
    const STATE_PARTIALLY = 3;

    const STATE_MESSAGES = [
        self::STATE_SUCCESS => '<info>successfully</info>',
        self::STATE_FAILED => '<error>failed</error>',
        self::STATE_PARTIALLY => '<comment>partially</comment>',
    ];

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $backup;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var PluginRegistry
     */
    private $pluginRegistry;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var DatabaseFactory
     */
    private $databaseFactory;

    /**
     * @var EnvironmentInterface
     */
    private $environment;

    /**
     * @param string $name
     * @param array $backup
     * @param OutputInterface $output
     * @param PluginRegistry $pluginRegistry
     * @param StorageInterface $storage
     * @param DatabaseFactory $databaseFactory
     * @param EnvironmentInterface $environment
     */
    public function __construct(
        $name,
        array $backup,
        OutputInterface $output,
        PluginRegistry $pluginRegistry,
        StorageInterface $storage,
        DatabaseFactory $databaseFactory,
        EnvironmentInterface $environment
    ) {
        $this->name = $name;
        $this->backup = $backup;
        $this->output = $output;
        $this->pluginRegistry = $pluginRegistry;
        $this->storage = $storage;
        $this->databaseFactory = $databaseFactory;
        $this->environment = $environment;
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

        $systemDatabase = $this->databaseFactory->create();
        $systemDatabase->set('label', $label);
        $systemDatabase->set('message', $message);
        $systemDatabase->set('started', (new \DateTime())->format(\DateTime::RFC3339));

        $this->output->writeln(sprintf('Backup "%s" started:', $this->name));
        $this->output->writeln(sprintf(' * label:   %s', $systemDatabase->get('label')));
        $this->output->writeln(sprintf(' * message: %s', $systemDatabase->get('message')));
        $this->output->writeln(sprintf(' * started: %s', $systemDatabase->get('started')));
        $this->output->writeln('');

        $state = self::STATE_SUCCESS;
        foreach ($this->backup as $backupName => $backup) {
            $this->output->writeln('- ' . $backupName . ' (' . $backup['plugin'] . '):');
            $plugin = $this->pluginRegistry->getPlugin($backup['plugin']);

            $optionsResolver = new OptionsResolver();
            $plugin->configureOptionsResolver($optionsResolver);
            $parameter = $optionsResolver->resolve($backup['parameter']);

            $backupDestination = new Filesystem(new PrefixAdapter('backup/' . $backupName, $destination->getAdapter()));
            $backupDestination->addPlugin(new ListFiles());

            $database = $this->databaseFactory->create();
            $database->set('started', (new \DateTime())->format(\DateTime::RFC3339));

            try {
                $plugin->backup($source, $backupDestination, $database, $parameter);
                $database->set('state', self::STATE_SUCCESS);
            } catch (\Exception $exception) {
                $database->set('state', self::STATE_FAILED);
                $database->set('exception', $this->serializeException($exception));
                $state = self::STATE_PARTIALLY;

                if (!$this->environment->continueFailedBackup($exception)) {
                    return self::STATE_FAILED;
                }
            }

            $database->set('finished', (new \DateTime())->format(\DateTime::RFC3339));
            $encodedData = json_encode($database->getAll(), JSON_PRETTY_PRINT);
            $destination->put(sprintf('database/backup/%s.json', $backupName), $encodedData);

            $this->output->writeln('');
        }

        $systemDatabase->set('finished', (new \DateTime())->format(\DateTime::RFC3339));
        $systemDatabase->set('state', $state);

        $encodedSystemData = json_encode($systemDatabase->getAll(), JSON_PRETTY_PRINT);
        $destination->put('database/system.json', $encodedSystemData);

        $name = $this->storage->close($destination);

        $this->output->writeln('');
        $this->output->writeln(
            sprintf('Backup "%s" finished %s', $name, self::STATE_MESSAGES[$state])
        );

        return $state;
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

        $systemData = json_decode($source->read('database/system.json'), true);
        $systemDatabase = $this->databaseFactory->createReadonly($systemData);

        $this->output->writeln(sprintf('Backup "%s" started will be restored:', $this->name));
        $this->output->writeln(sprintf(' * label:   %s', $systemDatabase->get('label')));
        $this->output->writeln(sprintf(' * message: %s', $systemDatabase->get('message')));
        $this->output->writeln(sprintf(' * started: %s', $systemDatabase->get('started')));
        $this->output->writeln('');

        if ($systemDatabase->getWithDefault('state', self::STATE_SUCCESS) === self::STATE_PARTIALLY
            && !$this->environment->restorePartiallyBackup()
        ) {
            return;
        }

        foreach ($this->backup as $backupName => $backup) {
            $this->output->writeln('- ' . $backupName . ' (' . $backup['plugin'] . '):');
            $plugin = $this->pluginRegistry->getPlugin($backup['plugin']);

            $optionsResolver = new OptionsResolver();
            $plugin->configureOptionsResolver($optionsResolver);
            $parameter = $optionsResolver->resolve($backup['parameter']);

            $backupSource = new Filesystem(new PrefixAdapter('backup/' . $backupName, $source->getAdapter()));
            $backupSource->addPlugin(new ListFiles());

            $database = $this->databaseFactory->createReadonly(
                json_decode($source->read(sprintf('database/backup/%s.json', $backupName)), true)
            );

            if ($database->getWithDefault('state', self::STATE_SUCCESS) === self::STATE_FAILED) {
                $this->output->writeln('  <info>Bypassed</info>');

                continue;
            }

            try {
                $plugin->restore($backupSource, $destination, $database, $parameter);
            } catch (\Exception $exception) {
                if (!$this->environment->continueFailedRestore($exception)) {
                    return;
                }
            }

            $this->output->writeln('');
        }

        $this->output->writeln('');
        $this->output->writeln('Restore finished');
    }

    /**
     * Serializes exception.
     *
     * @param \Exception $exception
     *
     * @return array
     */
    private function serializeException(\Exception $exception)
    {
        return [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTrace(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'previous' => null !== $exception->getPrevious() ? $this->serializeException($exception->getPrevious()) : null,
        ];
    }
}
