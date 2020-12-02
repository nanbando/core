<?php

namespace Nanbando\Tests\Unit\Core\Flysystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use Nanbando\Core\Flysystem\ReadonlyAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ReadonlyAdapterTest extends TestCase
{
    public function provideExceptionData()
    {
        return [
            ['write', ['/test.json', 'test content', new Config()]],
            ['writeStream', ['/test.json', 'test content', new Config()]],
            ['update', ['/test.json', 'test content', new Config()]],
            ['updateStream', ['/test.json', 'test content', new Config()]],
            ['rename', ['/test.json', '/test-new.json']],
            ['copy', ['/test.json', '/test-new.json']],
            ['delete', ['/test']],
            ['deleteDir', ['/test']],
            ['createDir', ['/test', new Config()]],
            ['createDir', ['/test', new Config()]],
            ['setVisibility', ['/test.json', true]],
            ['setVisibility', ['/test.json', false]],
        ];
    }

    /**
     * @dataProvider provideExceptionData
     *
     * @expectedException \Nanbando\Core\Flysystem\ReadonlyException
     */
    public function testException($method, array $parameter)
    {
        $adapter = $this->prophesize(AdapterInterface::class);

        call_user_func_array([new ReadonlyAdapter($adapter->reveal()), $method], $parameter);
    }

    public function provideDelegateData()
    {
        return [
            ['has', ['/test.json'], true],
            ['has', ['/test.json'], false],
            ['read', ['/test.json'], 'test content'],
            ['listContents', ['/', true], 'test content'],
            ['getMetadata', ['/test.json'], 'test metadata'],
            ['getSize', ['/test.json'], 42],
            ['getMimetype', ['/test.json'], 'application/test'],
            ['getTimestamp', ['/test.json'], new \DateTime()],
            ['getTimestamp', ['/test.json'], new \DateTime()],
            ['getVisibility', ['/test.json'], true],
            ['getVisibility', ['/test.json'], false],
        ];
    }

    /**
     * @dataProvider provideDelegateData
     */
    public function testDelegate($method, array $parameter, $result = null)
    {
        $adapter = $this->prophesize(AdapterInterface::class);

        $adapter->{$method}(Argument::cetera())->will(
            function ($arguments) use ($parameter, $result) {
                ReadonlyAdapterTest::assertEquals($parameter, $arguments);

                return $result;
            }
        );

        $this->assertEquals(
            $result,
            call_user_func_array([new ReadonlyAdapter($adapter->reveal()), $method], $parameter)
        );
    }

    public function testGetAdapter()
    {
        $adapter = $this->prophesize(AdapterInterface::class);
        $readonlyAdapter = new ReadonlyAdapter($adapter->reveal());

        $this->assertEquals($adapter->reveal(), $readonlyAdapter->getAdapter());
    }
}
