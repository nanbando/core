<?php

namespace Nanbando\Application;

use Cocur\Slugify\Bridge\Symfony\CocurSlugifyBundle;
use Composer\IO\NullIO;
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerInterface;
use Nanbando\Bundle\NanbandoBundle;
use Nanbando\Core\Config\JsonLoader;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

class Kernel extends SymfonyKernel implements CompilerPassInterface
{
    protected $name = 'Nanbando';

    /**
     * @var string
     */
    private $userDir;

    /**
     * @var EmbeddedComposerInterface
     */
    private $embeddedComposer;

    /**
     * @param string    $environment The environment
     * @param bool      $debug       Whether to enable debugging or not
     * @param string    $userDir
     */
    public function __construct($environment, $debug, $userDir, $embeddedComposer)
    {
        parent::__construct($environment, $debug);

        $this->userDir = $userDir;
        $this->embeddedComposer = $embeddedComposer;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            NanbandoBundle::class => new NanbandoBundle(),
            CocurSlugifyBundle::class => new CocurSlugifyBundle(),
        ];

        $discoveryFile = Path::join([getcwd(), NANBANDO_DIR, '.discovery']);
        if (!file_exists($discoveryFile)) {
            return $bundles;
        }

        $discovery = json_decode(file_get_contents($discoveryFile), true);
        foreach ($discovery as $class) {
            if (class_exists($class) && !array_key_exists($class, $bundles)) {
                $bundles[$class] = new $class();
            }
        }

        return array_values($bundles);
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
            $loader->load($applicationConfig, 'json');
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

    public function process(ContainerBuilder $container)
    {
        $container->set('composer',$this->embeddedComposer->createComposer(new NullIO()));
    }
}
