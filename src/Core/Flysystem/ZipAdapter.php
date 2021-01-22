<?php

namespace Nanbando\Core\Flysystem;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Adapter\Polyfill\StreamedReadingTrait;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use LogicException;
use PhpZip\Exception\ZipException;
use PhpZip\Model\ZipInfo;
use PhpZip\ZipFile;

class ZipAdapter extends AbstractAdapter implements AdapterInterface
{
    use NotSupportingVisibilityTrait;
    use StreamedReadingTrait;
    use StreamedCopyTrait;

    /**
     * @var string
     */
    private $zipPath;

    /**
     * @var ZipFile
     */
    private $zip;

    /**
     * @var int
     */
    private $maxMemory;

    /**
     * @param string $zipPath
     * @param $prefix
     */
    public function __construct($zipPath, $prefix = null, $maxMemory = (512 * 1024 * 1024))
    {
        $this->zipPath = $zipPath;
        $this->setPathPrefix($prefix);

        $this->maxMemory = $maxMemory;

        try {
            $this->zip = new ZipFile();
            $this->zip->openFile($zipPath);
        } catch (ZipException $exception) {
            // empty file
            $this->zip = new ZipFile();
        }
    }

    public function getZipPath()
    {
        return $this->zipPath;
    }

    public function writeStream($path, $resource, Config $config)
    {
        Util::rewindStream($resource);
        $this->zip->addFromStream($resource, $path);

        return compact('path');
    }

    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        $this->reopen();

        $location = $this->applyPathPrefix($path);
        $dirname = Util::dirname($path);

        if (!empty($dirname) && !$this->has($dirname)) {
            $this->createDir($dirname, $config);
        }

        $this->zip->addFromString($location, $contents);

        $result = compact('path', 'contents');

        if ($config && $config->get('visibility')) {
            throw new LogicException(get_class($this) . ' does not support visibility settings.');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $path = $this->applyPathPrefix($path);

        return ['contents' => $this->zip->getEntryContents($path)];
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        $this->delete($path);

        return $this->write($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        $source = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);

        $this->zip->rename($source, $destination);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        return $this->zip->deleteFromName($location);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $location = $this->applyPathPrefix($dirname);
        $path = Util::normalizePrefix($location, '/');
        $this->zip->deleteFromName($path);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        if (!$this->has($dirname)) {
            $location = $this->applyPathPrefix($dirname);
            $this->zip->addEmptyDir($location);
        }

        return ['path' => $dirname];
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return in_array($path, $this->zip->getListFiles());
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        $result = $this->zip->getAllInfo();

        $pathPrefix = $this->getPathPrefix();
        $prefixLength = strlen($pathPrefix);

        return array_filter(
            array_map(
                function (ZipInfo $item) use ($pathPrefix, $prefixLength) {
                    if ($pathPrefix
                        && (substr($item->getName(), 0, $prefixLength) !== $pathPrefix
                            || $item->getName() === $pathPrefix)
                    ) {
                        return false;
                    }

                    return $this->normalizeObject($item);
                },
                $result
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $location = $this->applyPathPrefix($path);

        if (!$info = $this->zip->getEntryInfo($location)) {
            return false;
        }

        return $this->normalizeObject($info);
    }

    /**
     * Normalize a zip response array.
     *
     * @param ZipInfo $object
     *
     * @return array
     */
    protected function normalizeObject(ZipInfo $object)
    {
        if ($object->isFolder()) {
            return [
                'path' => $this->removePathPrefix(trim($object->getName(), '/')),
                'type' => 'dir',
            ];
        }

        return [
            'type' => 'file',
            'size' => $object->getSize(),
            'timestamp' => $object->getMtime(),
            'path' => $this->removePathPrefix($object->getName()),
        ];
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        if (!$data = $this->read($path)) {
            return false;
        }

        $data['mimetype'] = Util::guessMimeType($path, $data['contents']);

        return $data;
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    public function close()
    {
        $this->zip->saveAsFile($this->zipPath);

        return $this->zipPath;
    }

    public function reopen()
    {
        if (memory_get_usage() < $this->maxMemory) {
            return;
        }

        $this->close();
        $this->zip = new ZipFile();
        $this->zip->openFile($this->zipPath);
    }
}
