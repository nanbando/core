<?php

namespace Nanbando\Tests\Unit\Core\Plugin;

use Nanbando\Core\Plugin\PluginInterface;
use Nanbando\Core\Plugin\PluginNotFoundException;
use Nanbando\Core\Plugin\PluginRegistry;

class PluginRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPlugin()
    {
        $plugin = $this->prophesize(PluginInterface::class);

        $registry = new PluginRegistry(['directory' => $plugin->reveal()]);

        $this->assertEquals($plugin->reveal(), $registry->getPlugin('directory'));
    }

    public function testGetPluginNotExists()
    {
        $this->setExpectedException(PluginNotFoundException::class);

        $registry = new PluginRegistry([]);

        $registry->getPlugin('directory');
    }

    public function provideHasData()
    {
        return [['directory', true], ['mysql', false]];
    }

    /**
     * @dataProvider provideHasData
     */
    public function testHas($name, $expected)
    {
        $plugin = $this->prophesize(PluginInterface::class);

        $registry = new PluginRegistry(['directory' => $plugin->reveal()]);

        $this->assertEquals($expected, $registry->has($name));
    }
}
