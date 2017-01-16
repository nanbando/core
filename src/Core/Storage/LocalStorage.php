<?php

namespace Nanbando\Core\Storage;

use Cocur\Slugify\SlugifyInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nanbando\Core\Flysystem\ReadonlyAdapter;
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
    private $name;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var TemporaryFilesystemInterface
     */
    private $temporaryFileSystem;

    /**
     * @var Filesystem
     */
    private $localFilesystem;

    /**
     * @var string
     */
    private $localDirectory;

    /**
     * @var Filesystem
     */
    private $remoteFilesystem;

    /**
     * @var SlugifyInterface
     */
    private $slugify;

    /**
     * @var SymfonyFilesystem
     */
    private $filesystem;

    /**
     * @param string $name
     * @param $environment
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
        $adapter = new ZipArchiveAdapter($filename);
        $filesystem = new Filesystem($adapter);
        $filesystem->addPlugin(new ListFiles());

        return $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function cancel(Filesystem $filesystem)
    {
        /** @var \ZipArchive $archive */
        $archive = $filesystem->getAdapter()->getArchive();

        $this->filesystem->remove($archive->filename);
    }

    /**
     * {@inheritdoc}
     */
    public function close(Filesystem $filesystem, $label = null)
    {
        /** @var ZipArchiveAdapter $adapter */
        $adapter = $filesystem->getAdapter();
        $filename = $adapter->getArchive()->filename;

        // close zip file
        $adapter->getArchive()->close();

        $path = sprintf(
            '%s/%s%s%s.zip',
            $this->name,
            date(self::FILE_NAME_PATTERN),
            (!empty($this->environment) ? ('_' . $this->slugify->slugify($this->environment)) : ''),
            (!empty($label) ? ('_' . $this->slugify->slugify($label)) : '')
        );
        $this->localFilesystem->putStream($path, fopen($filename, 'r'));

        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * {@inheritdoc}
     */
    public function open($name)
    {
        $fileName = $name;
        if (!is_file($name)) {
            $stream = $this->localFilesystem->readStream(sprintf('%s/%s.zip', $this->name, $name));
            $fileName = $this->temporaryFileSystem->createTemporaryFile();
            file_put_contents($fileName, $stream);
        }

        $filesystem = new Filesystem(new ReadonlyAdapter(new ZipArchiveAdapter($fileName)));
        $filesystem->addPlugin(new ListFiles());

        return $filesystem;
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
     *
     * @deprecated This function contains deprecated parts
     */
    protected function parseDateFromFilename($filename)
    {
        if ($date = \DateTime::createFromFormat(self::FILE_NAME_PATTERN, explode('_', $filename)[0])) {
            return $date;
        }

        /**
         * @deprecated handle BC break of PR #62. will be remove in 1.0-RC1.
         */
        return \DateTime::createFromFormat('H-i-s-Y-m-d', explode('_', $filename)[0]);
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
