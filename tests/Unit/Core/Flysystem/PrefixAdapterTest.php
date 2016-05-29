<?php

namespace Nanbando\Tests\Unit\Core\Flysystem;

use League\Flysystem\AdapterInterface;
use Nanbando\Core\Flysystem\PrefixAdapter;
use Webmozart\PathUtil\Path;

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

    public function testCopy()
    {
        $this->decorated->copy(Path::join([__DIR__, 'test.json']), Path::join([__DIR__, 'test-new.json']));

        $this->adapter->copy('test.json', 'test-new.json');
    }

    public function testHas()
    {
        $this->decorated->has(Path::join([__DIR__, 'test.json']));

        $this->adapter->has('test.json');
    }
}
