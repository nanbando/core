<?php

namespace Nanbando\Core\Storage;

use League\Flysystem\Filesystem;

interface StorageInterface
{
    /**
     * @return Filesystem
     */
    public function start();

    /**
     * @param Filesystem $filesystem
     */
    public function cancel(Filesystem $filesystem);

    /**
     * @param Filesystem $filesystem
     * @param string|null $label
     *
     * @return string
     */
    public function close(Filesystem $filesystem, $label = null);

    /**
     * @param string $name
     *
     * @return Filesystem
     */
    public function open($name);

    /**
     * @return string[]
     */
    public function localListing();

    /**
     * @return string[]
     */
    public function remoteListing();

    /**
     * Returns size for given backup.
     *
     * @param string $name
     *
     * @return int
     */
    public function size($name);

    /**
     * Returns path for given backup.
     *
     * @param string $name
     *
     * @return string
     */
    public function path($name);

    /**
     * @param string $file
     */
    public function push($file);

    /**
     * @param string $file
     */
    public function fetch($file);
}
