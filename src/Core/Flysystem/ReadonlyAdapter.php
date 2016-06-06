<?php

namespace Nanbando\Core\Flysystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class ReadonlyAdapter implements AdapterInterface
{
    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        throw new ReadonlyException();
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        throw new ReadonlyException();
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        throw new ReadonlyException();
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        throw new ReadonlyException();
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        throw new ReadonlyException();
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        throw new ReadonlyException();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        throw new ReadonlyException();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        throw new ReadonlyException();
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        throw new ReadonlyException();
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        throw new ReadonlyException();
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return $this->adapter->has($path);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        return $this->adapter->read($path);
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        return $this->adapter->readStream($path);
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        return $this->adapter->listContents($directory, $recursive);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        return $this->adapter->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->adapter->getSize($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->adapter->getMimetype($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->adapter->getTimestamp($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        return $this->adapter->getVisibility($path);
    }
}
