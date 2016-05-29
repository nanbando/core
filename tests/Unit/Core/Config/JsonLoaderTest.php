<?php

namespace Nanbando\Tests\Unit\Core\Config;

use Nanbando\Core\Config\JsonLoader;
use Prophecy\Argument;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Webmozart\PathUtil\Path;

class JsonLoaderTest extends \PHPUnit_Framework_TestCase
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

    protected function setUp()
    {
        $this->container = $this->prophesize(ContainerBuilder::class);
        $this->locator = $this->prophesize(FileLocatorInterface::class);

        $this->loader = new JsonLoader($this->container->reveal(), $this->locator->reveal());
    }

    public function provideSupportData()
    {
        return [
            ['/test.json', true],
            ['/test.xml', false],
        ];
    }

    /**
     * @dataProvider provideSupportData
     */
    public function testSupport($resource, $expected)
    {
        $this->assertEquals($expected, $this->loader->supports($resource));
    }

    public function testLoad()
    {
        $path = Path::join([RESOURCE_DIR, 'Config', 'test.json']);

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
}
