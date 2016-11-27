<?php

namespace Nanbando\Application;

use Cocur\Slugify\Bridge\Symfony\CocurSlugifyBundle;
use Nanbando\Bundle\NanbandoBundle;
use Nanbando\Core\Config\JsonLoader;
use Oneup\FlysystemBundle\OneupFlysystemBundle;
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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;
use Webmozart\PathUtil\Path;

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
     * @param string    $environment The environment
     * @param bool      $debug       Whether to enable debugging or not
     * @param string    $userDir
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
        $bundles = [
            new NanbandoBundle(),
            new OneupFlysystemBundle(),
            new CocurSlugifyBundle(),
        ];

        /** @var ClassBinding $binding */
        foreach ($this->discovery->findBindings('nanbando/bundle') as $binding) {
            $class = $binding->getClassName();
            if (class_exists($class)) {
                $bundles[] = new $class();
            }
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
            [
                new XmlFileLoader($container, $locator),
                new YamlFileLoader($container, $locator),
                new IniFileLoader($container, $locator),
                new PhpFileLoader($container, $locator),
                new DirectoryLoader($container, $locator),
                new JsonLoader($container, $locator),
                new ClosureLoader($container),
            ]
        );

        return new DelegatingLoader($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        $cacheDir = Path::join([getcwd(), NANBANDO_DIR, 'app', 'cache']);

        $filesystem = new Filesystem();
        $filesystem->mkdir($cacheDir);

        return $cacheDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        $logDir = Path::join([getcwd(), NANBANDO_DIR, 'app', 'log']);

        $filesystem = new Filesystem();
        $filesystem->mkdir($logDir);

        return $logDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function getKernelParameters()
    {
        $parameter = parent::getKernelParameters();

        return array_merge($parameter, ['home' => $this->userDir, 'project' => getcwd()]);
    }
}
