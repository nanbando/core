<?php

namespace Nanbando\Core\Config;

use Composer\Json\JsonFile;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Loader\FileLoader;
use Webmozart\PathUtil\Path;

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
    public function load($resource, string $type = null)
    {
        $path = $this->locator->locate($resource);
        $this->container->addResource(new FileResource($path));

        $file = new JsonFile($path);
        $content = $file->read();
        $extension = pathinfo($resource, PATHINFO_FILENAME);

        if (array_key_exists('parameters', $content)) {
            foreach ($content['parameters'] as $name => $parameter) {
                $this->container->setParameter($name, $parameter);
            }

            unset($content['parameters']);
        }

        if (array_key_exists('imports', $content)) {
            foreach ($content['imports'] as $import) {
                $importFilename = $import;
                if (!Path::isAbsolute($importFilename)) {
                    $importFilename = Path::join([dirname($path), $import]);
                }

                $this->import($importFilename, null, false, $file->getPath());
            }

            unset($content['imports']);
        }

        $this->container->loadFromExtension($extension, $content);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, string $type = null)
    {
        return is_string($resource) && 'json' === pathinfo(
            $resource,
            PATHINFO_EXTENSION
        );
    }
}
