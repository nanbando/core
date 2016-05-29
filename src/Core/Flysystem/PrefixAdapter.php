<?php

namespace Nanbando\Core\Flysystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use Webmozart\PathUtil\Path;

class PrefixAdapter implements AdapterInterface
{
    /**
     * @var string
     */
    private $root;

    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @param string           $root
     * @param AdapterInterface $adapter
     */
    public function __construct($root, AdapterInterface $adapter)
    {
        $this->root = $root;
        $this->adapter = $adapter;
    }

    /**
     * Returns prefixed path.
     *
     * @param string $path
     *
     * @return string
     */
    private function getPath($path)
    {
        return rtrim(sprintf('%s/%s', $this->root, ltrim($path, '/')), '/');
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        return $this->adapter->write($this->getPath($path), $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->adapter->writeStream($this->getPath($path), $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        return $this->adapter->update($this->getPath($path), $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->adapter->updateStream($this->getPath($path), $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        return $this->adapter->rename($this->getPath($path), $this->getPath($newpath));
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        return $this->adapter->copy($this->getPath($path), $this->getPath($newpath));
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        return $this->adapter->delete($this->getPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        return $this->adapter->deleteDir($this->getPath($dirname));
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        return $this->adapter->createDir($this->getPath($dirname), $config);
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        return $this->adapter->setVisibility($this->getPath($path), $visibility);
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return $this->adapter->has($this->getPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        return $this->adapter->read($this->getPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        return $this->adapter->readStream($this->getPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        $contents = $this->adapter->listContents($this->getPath($directory), $recursive);
        $root = ltrim($this->root, '/');

        return array_map(
            function ($file) use ($root) {
                $file['path'] = Path::makeRelative($file['path'], $root);
                if (array_key_exists('dirname', $file)) {
                    $file['dirname'] = Path::makeRelative($file['dirname'], $root);
                }

                return $file;
            },
            array_filter(
                $contents,
                function ($file) use ($root) {
                    if (0 !== strpos($file['path'], $root)) {
                        return false;
                    }

                    return true;
                }
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        return $this->adapter->getMetadata($this->getPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->adapter->getSize($this->getPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->adapter->getMimetype($this->getPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->adapter->getTimestamp($this->getPath($path));
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        return $this->adapter->getVisibility($this->getPath($path));
    }
}
