<?php

namespace Nanbando\Application;

use Puli\Discovery\Api\Discovery;
use Puli\Discovery\Binding\ClassBinding;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;

class Kernel extends SymfonyKernel
{
    protected $name = 'Nanbando';

    /**
     * @var string
     */
    private $userDir;

    /**
     * @var Discovery
     */
    private $discovery;

    /**
     * @param string $environment The environment
     * @param bool $debug Whether to enable debugging or not
     * @param string $userDir
     * @param Discovery $discovery
     */
    public function __construct($environment, $debug, $userDir, Discovery $discovery)
    {
        parent::__construct($environment, $debug);

        $this->userDir = $userDir;
        $this->discovery = $discovery;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [];

        /** @var ClassBinding $binding */
        foreach ($this->discovery->findBindings('nanbando/bundle') as $binding) {
            $class = $binding->getClassName();
            $bundles[] = new $class;
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $userConfig = sprintf('%s/.nanbando.yml', $this->userDir);
        if (is_file($userConfig)) {
            $loader->load($userConfig);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return '.nanbando/app/cache';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return '.nanbando/app/log';
    }
}
