<?php

namespace Nanbando\Core\Storage;

use Cocur\Slugify\SlugifyInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nanbando\Core\Flysystem\ReadonlyAdapter;
use Nanbando\Core\Flysystem\ZipAdapter;
use Neutron\TemporaryFilesystem\TemporaryFilesystemInterface;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * Implement the storage interface for local usage.
 */
class LocalStorage implements StorageInterface
{
    const FILE_NAME_PATTERN = 'Y-m-d-H-i-s';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var TemporaryFilesystemInterface
     */
    protected $temporaryFileSystem;

    /**
     * @var Filesystem
     */
    protected $localFilesystem;

    /**
     * @var string
     */
    protected $localDirectory;

    /**
     * @var Filesystem
     */
    protected $remoteFilesystem;

    /**
     * @var SlugifyInterface
     */
    protected $slugify;

    /**
     * @var SymfonyFilesystem
     */
    protected $filesystem;

    /**
     * @param string $name
     * @param string $environment
     * @param TemporaryFilesystemInterface $temporaryFileSystem
     * @param SlugifyInterface $slugify
     * @param SymfonyFilesystem $filesystem
     * @param Filesystem $localFilesystem
     * @param string $localDirectory
     * @param Filesystem $remoteFilesystem
     */
    public function __construct(
        $name,
        $environment,
        TemporaryFilesystemInterface $temporaryFileSystem,
        SlugifyInterface $slugify,
        SymfonyFilesystem $filesystem,
        Filesystem $localFilesystem,
        $localDirectory,
        Filesystem $remoteFilesystem = null
    ) {
        $this->name = $name;
        $this->environment = $environment;
        $this->temporaryFileSystem = $temporaryFileSystem;
        $this->slugify = $slugify;
        $this->filesystem = $filesystem;
        $this->localFilesystem = $localFilesystem;
        $this->localDirectory = $localDirectory;
        $this->remoteFilesystem = $remoteFilesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        $filename = $this->temporaryFileSystem->createTemporaryFile();
        $filesystem = new Filesystem($this->createBackupAdapter($filename));
        $filesystem->addPlugin(new ListFiles());

        return $filesystem;
    }

    protected function createBackupAdapter(string $filename): AdapterInterface
    {
        return new ZipAdapter($filename);
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(Filesystem $filesystem)
    {
        /** @var Local $adapter */
        $adapter = $filesystem->getAdapter();

        $this->filesystem->remove($adapter->getPathPrefix());
    }

    /**
     * {@inheritdoc}
     */
    public function close(Filesystem $filesystem, $label = null)
    {
        $fileName = sprintf(
            '%s%s%s',
            date(self::FILE_NAME_PATTERN),
            (!empty($this->environment) ? ('_' . $this->slugify->slugify($this->environment)) : ''),
            (!empty($label) ? ('_' . $this->slugify->slugify($label)) : '')
        );

        /** @var ZipAdapter $adapter */
        $adapter = $filesystem->getAdapter();
        $filename = $adapter->close();

        $path = sprintf('%s/%s/%s.zip', $this->localDirectory, $this->name, $fileName);
        if (!is_dir(dirname($path))) {
            $this->filesystem->mkdir(dirname($path));
        }

        $this->filesystem->rename($filename, $path);

        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * {@inheritdoc}
     */
    public function open($name)
    {
        $fileName = $name;
        if (!is_file($name)) {
            $fileName = $this->path($name);
        }

        $filesystem = new Filesystem($this->createRestoreAdapter($fileName));
        $filesystem->addPlugin(new ListFiles());

        return $filesystem;
    }

    protected function createRestoreAdapter(string $filename): AdapterInterface
    {
        return new ReadonlyAdapter(new ZipAdapter($filename));
    }

    /**
     * {@inheritdoc}
     */
    public function localListing()
    {
        return $this->listing($this->localFilesystem);
    }

    /**
     * {@inheritdoc}
     */
    public function remoteListing()
    {
        if (!$this->remoteFilesystem) {
            throw new RemoteStorageNotConfiguredException();
        }

        return $this->listing($this->remoteFilesystem);
    }

    /**
     * {@inheritdoc}
     */
    public function size($name)
    {
        return $this->localFilesystem->getSize($this->generatePath($name));
    }

    /**
     * {@inheritdoc}
     */
    public function path($name)
    {
        return sprintf('%s/%s', rtrim($this->localDirectory, '/'), ltrim($this->generatePath($name), '/'));
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($file)
    {
        if (!$this->remoteFilesystem) {
            throw new RemoteStorageNotConfiguredException();
        }

        $path = sprintf('%s/%s.zip', $this->name, $file);

        if (false === ($stream = $this->remoteFilesystem->readStream($path))) {
            return;
        }

        $this->localFilesystem->putStream($path, $stream);
    }

    /**
     * {@inheritdoc}
     */
    public function push($file)
    {
        if (!$this->remoteFilesystem) {
            throw new RemoteStorageNotConfiguredException();
        }

        $path = sprintf('%s/%s.zip', $this->name, $file);
        if (false === ($stream = $this->localFilesystem->readStream($path)) || $this->remoteFilesystem->has($path)) {
            return;
        }

        $this->remoteFilesystem->putStream($path, $stream);
    }

    /**
     * @param Filesystem $filesystem
     *
     * @return string[]
     */
    protected function listing(Filesystem $filesystem)
    {
        $result = array_filter(
            array_map(
                function ($item) {
                    return $item['filename'];
                },
                $filesystem->listFiles($this->name)
            )
        );

        usort(
            $result,
            function ($a, $b) {
                $aDate = $this->parseDateFromFilename($a);
                $bDate = $this->parseDateFromFilename($b);

                return $aDate->getTimestamp() - $bDate->getTimestamp();
            }
        );

        return $result;
    }

    /**
     * Parse date from given filename.
     *
     * @param string $filename
     *
     * @return \DateTime
     */
    protected function parseDateFromFilename($filename)
    {
        return \DateTime::createFromFormat(self::FILE_NAME_PATTERN, explode('_', $filename)[0]);
    }

    /**
     * Returns name for given backup.
     *
     * @param string $name
     *
     * @return string
     */
    protected function generatePath($name)
    {
        return sprintf('%s/%s.zip', $this->name, $name);
    }
}
