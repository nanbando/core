<?php

namespace Nanbando\Core\Storage;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * Provides functionality to zip folders.
 */
class Zipper
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $localDirectory;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param string $name
     * @param string $localDirectory
     * @param Filesystem $filesystem
     * @param OutputInterface $output
     */
    public function __construct($name, $localDirectory, Filesystem $filesystem, OutputInterface $output)
    {
        $this->name = $name;
        $this->localDirectory = $localDirectory;
        $this->output = $output;
        $this->filesystem = $filesystem;
    }

    /**
     * Creates a new zip from the given directory.
     *
     * @param string $directory
     * @param string $fileName
     *
     * @return string
     *
     * @throws IOException
     */
    public function zip($directory, $fileName)
    {
        $path = sprintf('%s/%s/%s.zip', $this->localDirectory, $this->name, $fileName);
        $this->filesystem->mkdir(dirname($path));

        // FIXME find better place for this message
        $this->output->writeln(PHP_EOL . 'Creating zip file (it may take a few minutes) ...');

        $zip = $this->openZip($path);

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($files as $filePath => $file) {
            $relative = '/' . Path::makeRelative($filePath, $directory);
            if (is_dir($filePath)) {
                $zip->addEmptyDir($relative);

                continue;
            }

            $zip->addFile($filePath, $relative);
        }

        $zip->close();

        return $path;
    }

    /**
     * Open zip from given path.
     *
     * @param string $path
     *
     * @return \ZipArchive
     */
    private function openZip($path)
    {
        $zip = new \ZipArchive();
        if (!$zip->open($path, \ZipArchive::CREATE)) {
            throw new IOException('Cannot create zip file');
        }

        return $zip;
    }
}
