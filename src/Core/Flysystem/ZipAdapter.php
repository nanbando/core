<?php

namespace Nanbando\Core\Flysystem;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Adapter\Polyfill\StreamedTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use LogicException;
use PhpZip\Model\ZipInfo;
use PhpZip\ZipFile;
use PhpZip\ZipOutputFile;

class ZipAdapter extends AbstractAdapter implements AdapterInterface
{
    use NotSupportingVisibilityTrait;
    use StreamedTrait;
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
     * @var ZipOutputFile
     */
    private $zipOutput;

    /**
     * @param string $zipPath
     * @param $prefix
     */
    public function __construct($zipPath, $prefix = null)
    {
        $this->zipPath = $zipPath;
        $this->setPathPrefix($prefix);

        $this->zipOutput = ZipOutputFile::create();
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

        $this->zipOutput->addFromString($location, $contents);

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

        if($this->zip) {
            return $this->zip->getEntryContent($path);
        }

        return $this->zipOutput->getEntryContent($path);
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

        $this->zipOutput->rename($source, $destination);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        $location = $this->applyPathPrefix($path);

        return $this->zipOutput->deleteFromName($location);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $location = $this->applyPathPrefix($dirname);
        $path = Util::normalizePrefix($location, '/');
        $this->zipOutput->deleteFromName($path);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        if (!$this->has($dirname)) {
            $location = $this->applyPathPrefix($dirname);
            $this->zipOutput->addEmptyDir($location);
        }

        return ['path' => $dirname];
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return in_array($path, $this->zipOutput->getListFiles());
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
                        && (substr($item->getPath(), 0, $prefixLength) !== $pathPrefix
                            || $item->getPath() === $pathPrefix)
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
                'path' => $this->removePathPrefix(trim($object->getPath(), '/')),
                'type' => 'dir',
            ];
        }

        return [
            'type' => 'file',
            'size' => $object->getSize(),
            'timestamp' => $object->getMtime(),
            'path' => $this->removePathPrefix($object->getPath()),
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
        $this->zipOutput->saveAsFile($this->zipPath);

        return $this->zipPath;
    }

    public function reopen()
    {
        if (memory_get_usage() < 0.5 * 1024 * 1024 * 1024) {
            return;
        }

        $this->close();
        $this->zipOutput = ZipOutputFile::openFromZipFile($this->zip = ZipFile::openFromFile($this->zipPath));
    }
}
