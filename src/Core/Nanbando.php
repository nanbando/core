<?php

namespace Nanbando\Core;

use Cocur\Slugify\Slugify;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nanbando\Core\Database\Database;
use Nanbando\Core\Database\ReadonlyDatabase;
use Nanbando\Core\Flysystem\PrefixAdapter;
use Nanbando\Core\Plugin\PluginRegistry;
use Nanbando\Core\Temporary\TemporaryFileManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\NoSuchOptionException;
use Symfony\Component\OptionsResolver\Exception\OptionDefinitionException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Nanbando
{
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
     * @var Filesystem
     */
    private $localFilesystem;

    /**
     * @var TemporaryFileManager
     */
    private $temporaryFileManager;

    /**
     * @var Slugify
     */
    private $slugify;

    /**
     * @param string $name
     * @param array $backup
     * @param OutputInterface $output
     * @param PluginRegistry $pluginRegistry
     * @param Filesystem $localFilesystem
     * @param TemporaryFileManager $temporaryFileManager
     * @param Slugify $slugify
     */
    public function __construct(
        $name,
        array $backup,
        OutputInterface $output,
        PluginRegistry $pluginRegistry,
        Filesystem $localFilesystem,
        TemporaryFileManager $temporaryFileManager,
        Slugify $slugify
    ) {
        $this->name = $name;
        $this->backup = $backup;
        $this->output = $output;
        $this->pluginRegistry = $pluginRegistry;
        $this->localFilesystem = $localFilesystem;
        $this->temporaryFileManager = $temporaryFileManager;
        $this->slugify = $slugify;
    }

    /**
     * Backup process.
     *
     * @param string $label
     * @param string $message
     *
     * @throws \Exception
     * @throws AccessException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     * @throws NoSuchOptionException
     * @throws OptionDefinitionException
     * @throws UndefinedOptionsException
     */
    public function backup($label = '', $message = '')
    {
        $tempFilename = $this->temporaryFileManager->getFilename();
        $destinationAdapter = new ZipArchiveAdapter($tempFilename);
        $destination = new Filesystem($destinationAdapter);

        // TODO readonly
        $source = new Filesystem(new Local(realpath('.')));
        $source->addPlugin(new ListFiles());

        $systemDatabase = new Database();
        $systemDatabase->set('label', $label);
        $systemDatabase->set('message', $message);
        $systemDatabase->set('started', (new \DateTime())->format(\DateTime::RFC3339));

        $this->output->writeln(sprintf('Backup "%s" started:', $this->name));
        $this->output->writeln(sprintf(' * label:   %s', $systemDatabase->get('label')));
        $this->output->writeln(sprintf(' * message: %s', $systemDatabase->get('message')));
        $this->output->writeln(sprintf(' * started: %s', $systemDatabase->get('started')));
        $this->output->writeln('');

        foreach ($this->backup as $name => $backup) {
            $this->output->writeln('- ' . $name . ' (' . $backup['plugin'] . '):');
            $plugin = $this->pluginRegistry->getPlugin($backup['plugin']);

            $optionsResolver = new OptionsResolver();
            $plugin->configureOptionsResolver($optionsResolver);
            $parameter = $optionsResolver->resolve($backup['parameter']);

            $backupDestination = new Filesystem(new PrefixAdapter('/backup/' . $name, $destination->getAdapter()));
            $backupDestination->addPlugin(new ListFiles());

            $database = new Database();
            $database->set('started', (new \DateTime())->format(\DateTime::RFC3339));
            $plugin->backup($source, $backupDestination, $database, $parameter);
            $database->set('finished', (new \DateTime())->format(\DateTime::RFC3339));
            $encodedData = json_encode($database->getAll(), JSON_PRETTY_PRINT);
            $destination->put(sprintf('/database/backup/%s.json', $name), $encodedData);

            $this->output->writeln('');
        }

        $systemDatabase->set('finished', (new \DateTime())->format(\DateTime::RFC3339));

        $encodedSystemData = json_encode($systemDatabase->getAll(), JSON_PRETTY_PRINT);
        $destination->put('/database/system.json', $encodedSystemData);

        // close zip file
        $destinationAdapter->getArchive()->close();

        $destinationPath = sprintf(
            '/%s/%s%s.zip',
            $this->name,
            date('H-i-s-Y-m-d'),
            (!empty($label) ? ('_' . $this->slugify->slugify($label)) : '')
        );
        $this->localFilesystem->putStream($destinationPath, fopen($tempFilename, 'r'));

        $this->output->writeln('');
        $this->output->writeln('Backup finished');
    }

    /**
     * Restore process.
     *
     * @param string $filename
     *
     * @throws \Exception
     * @throws InvalidOptionsException
     * @throws FileNotFoundException
     * @throws AccessException
     * @throws MissingOptionsException
     * @throws NoSuchOptionException
     * @throws OptionDefinitionException
     * @throws UndefinedOptionsException
     */
    public function restore($filename)
    {
        // TODO readonly
        $source = new Filesystem(new ZipArchiveAdapter($filename));

        $destination = new Filesystem(new Local(realpath('.'), LOCK_EX, null));
        $destination->addPlugin(new ListFiles());

        $systemData = json_decode($source->read('/database/system.json'), true);
        $systemDatabase = new ReadonlyDatabase($systemData);

        $this->output->writeln(sprintf('Backup "%s" started will be restored:', $this->name));
        $this->output->writeln(sprintf(' * label:   %s', $systemDatabase->get('label')));
        $this->output->writeln(sprintf(' * message: %s', $systemDatabase->get('message')));
        $this->output->writeln(sprintf(' * started: %s', $systemDatabase->get('started')));
        $this->output->writeln('');

        foreach ($this->backup as $name => $backup) {
            $this->output->writeln('- ' . $name . ' (' . $backup['plugin'] . '):');
            $plugin = $this->pluginRegistry->getPlugin($backup['plugin']);

            $optionsResolver = new OptionsResolver();
            $plugin->configureOptionsResolver($optionsResolver);
            $parameter = $optionsResolver->resolve($backup['parameter']);

            $backupSource = new Filesystem(new PrefixAdapter('backup/' . $name, $source->getAdapter()));
            $backupSource->addPlugin(new ListFiles());

            $database = new ReadonlyDatabase(
                json_decode($source->read(sprintf('database/backup/%s.json', $name)), true)
            );
            $plugin->restore($backupSource, $destination, $database, $parameter);

            $this->output->writeln('');
        }

        $this->output->writeln('');
        $this->output->writeln('Restore finished');
    }
}
