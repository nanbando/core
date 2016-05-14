<?php

namespace Nanbando\Core\Config;

use Composer\Json\JsonFile;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Loader\FileLoader;

class JsonLoader extends FileLoader
{
    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \LogicException
     * @throws BadMethodCallException
     */
    public function load($resource, $type = null)
    {
        $path = $this->locator->locate($resource);
        $this->container->addResource(new FileResource($path));

        $file = new JsonFile($path);
        $content = $file->read();
        $extension = pathinfo($resource, PATHINFO_FILENAME);

        $this->container->loadFromExtension($extension, $content);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'json' === pathinfo(
            $resource,
            PATHINFO_EXTENSION
        );
    }
}
