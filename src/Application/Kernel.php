<?php

namespace Nanbando\Application;

use Nanbando\Core\Config\JsonLoader;
use Puli\Discovery\Api\Discovery;
use Puli\Discovery\Binding\ClassBinding;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Config\FileLocator;
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
        $userConfig = realpath(sprintf('%s/.nanbando.yml', $this->userDir));
        if (is_file($userConfig)) {
            $loader->load($userConfig);
        }

        $applicationConfig = realpath('nanbando.json');
        if (is_file($applicationConfig)) {
            $loader->load($applicationConfig);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerLoader(ContainerInterface $container)
    {
        $locator = new FileLocator($this);
        $resolver = new LoaderResolver(
            array(
                new XmlFileLoader($container, $locator),
                new YamlFileLoader($container, $locator),
                new IniFileLoader($container, $locator),
                new PhpFileLoader($container, $locator),
                new DirectoryLoader($container, $locator),
                new JsonLoader($container, $locator),
                new ClosureLoader($container),
            )
        );

        return new DelegatingLoader($resolver);
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

    /**
     * {@inheritdoc}
     */
    protected function getKernelParameters()
    {
        $parameter = parent::getKernelParameters();

        return array_merge($parameter, ['home' => $this->userDir]);
    }
}
