<?php

namespace Nanbando\Core\Storage;

use Cocur\Slugify\SlugifyInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListFiles;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nanbando\Core\Flysystem\ReadonlyAdapter;
use Neutron\TemporaryFilesystem\TemporaryFilesystemInterface;

class LocalStorage implements StorageInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var TemporaryFilesystemInterface
     */
    private $temporaryFileSystem;

    /**
     * @var Filesystem
     */
    private $localFilesystem;

    /**
     * @var Filesystem
     */
    private $remoteFilesystem;

    /**
     * @var SlugifyInterface
     */
    private $slugify;

    /**
     * @param string $name
     * @param TemporaryFilesystemInterface $temporaryFileSystem
     * @param SlugifyInterface $slugify
     * @param Filesystem $localFilesystem
     * @param Filesystem $remoteFilesystem
     */
    public function __construct(
        $name,
        TemporaryFilesystemInterface $temporaryFileSystem,
        SlugifyInterface $slugify,
        Filesystem $localFilesystem,
        Filesystem $remoteFilesystem = null
    ) {
        $this->name = $name;
        $this->temporaryFileSystem = $temporaryFileSystem;
        $this->localFilesystem = $localFilesystem;
        $this->remoteFilesystem = $remoteFilesystem;
        $this->slugify = $slugify;
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
    public function close(Filesystem $filesystem, $label = null)
    {
        /** @var ZipArchiveAdapter $adapter */
        $adapter = $filesystem->getAdapter();
        $filename = $adapter->getArchive()->filename;

        // close zip file
        $adapter->getArchive()->close();

        $path = sprintf(
            '%s/%s%s.zip',
            $this->name,
            date('H-i-s-Y-m-d'),
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
        $tempFileName = $this->temporaryFileSystem->createTemporaryFile();
        $stream = $this->localFilesystem->readStream(sprintf('%s/%s.zip', $this->name, $name));
        file_put_contents($tempFileName, $stream);

        $filesystem = new Filesystem(new ReadonlyAdapter(new ZipArchiveAdapter($tempFileName)));
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
    public function size(Filesystem $filesystem)
    {
        /** @var ReadonlyAdapter $firstAdapter */
        $firstAdapter = $filesystem->getAdapter();
        /** @var ZipArchiveAdapter $adapter */
        $adapter = $firstAdapter->getAdapter();

        return filesize($adapter->getArchive()->filename);
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

        if (false === ($stream = $this->localFilesystem->readStream($path))) {
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
        return array_filter(
            array_map(
                function ($item) {
                    return $item['filename'];
                },
                $filesystem->listFiles($this->name)
            )
        );
    }
}
