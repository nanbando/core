<?php

namespace Nanbando\Tests\Unit\Core\Flysystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use Nanbando\Core\Flysystem\PrefixAdapter;
use Prophecy\Argument;

class PrefixAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AdapterInterface
     */
    private $decorated;

    /**
     * @var PrefixAdapter
     */
    private $adapter;

    public function setUp()
    {
        $this->decorated = $this->prophesize(AdapterInterface::class);

        $this->adapter = new PrefixAdapter(__DIR__, $this->decorated->reveal());
    }

    public function provideDelegateData()
    {
        $config = new Config();

        return [
            ['write', ['/test.json', 'test content', $config], ['/test/test.json', 'test content', $config]],
            ['writeStream', ['/test.json', 'test content', $config], ['/test/test.json', 'test content', $config]],
            ['update', ['/test.json', 'test content', $config], ['/test/test.json', 'test content', $config]],
            ['updateStream', ['/test.json', 'test content', $config], ['/test/test.json', 'test content', $config]],
            ['rename', ['/test.json', '/test-new.json'], ['/test/test.json', '/test/test-new.json']],
            ['copy', ['/test.json', '/test-new.json'], ['/test/test.json', '/test/test-new.json']],
            ['delete', ['/test'], ['/test/test']],
            ['deleteDir', ['/test'], ['/test/test']],
            ['createDir', ['/test', $config], ['/test/test', $config]],
            ['createDir', ['/test', $config], ['/test/test', $config]],
            ['setVisibility', ['/test.json', true], ['/test/test.json', true]],
            ['setVisibility', ['/test.json', false], ['/test/test.json', false]],
            ['has', ['/test.json'], ['/test/test.json'], true],
            ['has', ['/test.json'], ['/test/test.json'], false],
            ['read', ['/test.json'], ['/test/test.json'], 'test content'],
            ['getMetadata', ['/test.json'], ['/test/test.json'], 'test metadata'],
            ['getSize', ['/test.json'], ['/test/test.json'], 42],
            ['getMimetype', ['/test.json'], ['/test/test.json'], 'application/test'],
            ['getTimestamp', ['/test.json'], ['/test/test.json'], new \DateTime()],
            ['getTimestamp', ['/test.json'], ['/test/test.json'], new \DateTime()],
            ['getVisibility', ['/test.json'], ['/test/test.json'], true],
            ['getVisibility', ['/test.json'], ['/test/test.json'], false],
        ];
    }

    /**
     * @dataProvider provideDelegateData
     */
    public function testDelegate($method, array $parameter, array $delegatedParameter, $result = null, $root = '/test')
    {
        $adapter = $this->prophesize(AdapterInterface::class);

        $adapter->{$method}(Argument::cetera())->will(
            function ($arguments) use ($delegatedParameter, $result) {
                PrefixAdapterTest::assertEquals($delegatedParameter, $arguments);

                return $result;
            }
        );

        $this->assertEquals(
            $result,
            call_user_func_array([new PrefixAdapter($root, $adapter->reveal()), $method], $parameter)
        );
    }

    public function testListContents()
    {
        $adapter = $this->prophesize(AdapterInterface::class);
        $prefixAdapter = new PrefixAdapter('/test', $adapter->reveal());

        $adapter->listContents('/test/dir', false)->willReturn(
            [
                ['path' => 'test/dir/test-1.json', 'dirname' => 'test/dir'],
                ['path' => 'test/dir/test-2.json', 'dirname' => 'test/dir'],
            ]
        );

        $result = $prefixAdapter->listContents('/dir');

        $this->assertEquals(
            [
                ['path' => 'dir/test-1.json', 'dirname' => 'dir'],
                ['path' => 'dir/test-2.json', 'dirname' => 'dir'],
            ],
            $result
        );
    }
}
