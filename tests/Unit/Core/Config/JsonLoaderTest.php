<?php

namespace Nanbando\Tests\Unit\Core\Config;

use Nanbando\Core\Config\JsonLoader;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\PathUtil\Path;

class JsonLoaderTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var FileLocatorInterface
     */
    private $locator;

    /**
     * @var JsonLoader
     */
    private $loader;

    protected function setUp(): void
    {
        $this->container = $this->prophesize(ContainerBuilder::class);
        $this->locator = $this->prophesize(FileLocatorInterface::class);

        $this->loader = new JsonLoader($this->container->reveal(), $this->locator->reveal());
    }

    public function provideSupportData(): array
    {
        return [
            ['/test.json', true],
            ['/test.xml', false],
        ];
    }

    /**
     * @dataProvider provideSupportData
     */
    public function testSupport($resource, $expected): void
    {
        $this->assertEquals($expected, $this->loader->supports($resource));
    }

    public function testLoad(): void
    {
        $path = Path::join([DATAFIXTURES_DIR, 'config', 'test.json']);

        $this->locator->locate('test.json')->willReturn($path);

        $this->container->addResource(
            Argument::that(
                function (FileResource $resource) use ($path) {
                    return $resource->getResource() === $path;
                }
            )
        )->shouldBeCalled();
        $this->container->loadFromExtension('test', ['name' => 'test'])->shouldBeCalled();

        $this->loader->load('test.json');
    }

    public function testImport(): void
    {
        $ymlLoader = $this->prophesize(LoaderInterface::class);
        $ymlLoader->load(Path::join([DATAFIXTURES_DIR, 'config', 'parameters.yml']), null)->shouldBeCalled();

        $resolver = $this->prophesize(LoaderResolverInterface::class);
        $resolver->resolve(Path::join([DATAFIXTURES_DIR, 'config', 'parameters.yml']), null)
            ->willReturn($ymlLoader->reveal());

        $this->loader->setResolver($resolver->reveal());

        $path1 = Path::join([DATAFIXTURES_DIR, 'config', 'test-imports.json']);
        $path2 = Path::join([DATAFIXTURES_DIR, 'config', 'parameters.json']);

        $this->locator->locate('test-imports.json')->willReturn($path1);
        $this->locator->locate('parameters.yml')->willReturn($path2);

        $this->container->addResource(
            Argument::that(
                function (FileResource $resource) use ($path1) {
                    return $resource->getResource() === $path1;
                }
            )
        )->shouldBeCalled();
        $this->container->loadFromExtension('test-imports', ['name' => 'test'])->shouldBeCalled();

        $this->loader->load('test-imports.json');
    }

    public function testParameters(): void
    {
        $path = Path::join([DATAFIXTURES_DIR, 'config', 'test-parameters.json']);

        $this->locator->locate('test-parameters.json')->willReturn($path);

        $this->container->addResource(
            Argument::that(
                function (FileResource $resource) use ($path) {
                    return $resource->getResource() === $path;
                }
            )
        )->shouldBeCalled();
        $this->container->loadFromExtension('test-parameters', ['name' => 'test'])->shouldBeCalled();
        $this->container->setParameter('test', 'value')->shouldBeCalled();

        $this->loader->load('test-parameters.json');
    }
}
