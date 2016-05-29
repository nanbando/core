<?php

namespace Nanbando\Core\Temporary;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class TemporaryFileManager
{
    /**
     * @var array
     */
    private $files = [];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $tempFolder;

    /**
     * @var string
     */
    private $name;

    /**
     * @param Filesystem $filesystem
     * @param string     $name
     * @param string     $tempFolder
     */
    public function __construct(Filesystem $filesystem, $name, $tempFolder)
    {
        $this->filesystem = $filesystem;
        $this->name = $name;
        $this->tempFolder = $tempFolder;
    }

    /**
     * Returns temporary filename.
     *
     * @param string|null $prefix
     *
     * @return string
     */
    public function getFilename($prefix = null)
    {
        return $this->files[] = $this->filesystem->tempnam($this->tempFolder, $this->name . ($prefix ?: ''));
    }

    /**
     * Deletes all temporary files.
     *
     * @throws IOException
     */
    public function cleanup()
    {
        $this->filesystem->remove($this->files);
    }
}
