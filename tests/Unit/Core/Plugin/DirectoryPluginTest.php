<?php

namespace Nanbando\Tests\Unit\Core\Plugin;

use League\Flysystem\Filesystem;
use Nanbando\Core\Database\Database;
use Nanbando\Core\Plugin\DirectoryPlugin;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DirectoryPluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var DirectoryPlugin
     */
    private $plugin;

    public function setUp()
    {
        $this->output = new NullOutput();

        $this->plugin = new DirectoryPlugin($this->output);
    }

    public function testConfigureOptionsResolver()
    {
        $optionsResolver = $this->prophesize(OptionsResolver::class);

        $this->plugin->configureOptionsResolver($optionsResolver->reveal());

        $optionsResolver->setRequired('directory')->shouldBeCalled();
    }

    public function testBackup()
    {
        $source = $this->prophesize(Filesystem::class);
        $destination = $this->prophesize(Filesystem::class);
        $database = $this->prophesize(Database::class);

        $source->listFiles('test', true)->willReturn(
            [
                [
                    'path' => 'test/test.json',
                ],
            ]
        );

        $source->readStream('test/test.json')->willReturn('file resource');
        $destination->writeStream('test.json', 'file resource')->shouldBeCalled();

        $this->plugin->backup($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => 'test']);
    }

    public function testRestore()
    {
        $source = $this->prophesize(Filesystem::class)->willImplement(HashPluginInterface::class);
        $destination = $this->prophesize(Filesystem::class)->willImplement(HashPluginInterface::class);
        $database = $this->prophesize(Database::class);

        $source->listFiles('', true)->willReturn(
            [
                [
                    'path' => 'test.json',
                ],
            ]
        );

        $source->readStream('test.json')->willReturn('file resource');

        $destination->has('test/test.json')->willReturn(false);
        $destination->writeStream('test/test.json', 'file resource')->shouldBeCalled();

        $this->plugin->restore($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => 'test']);
    }

    public function testRestoreExistingFile()
    {
        $source = $this->prophesize(Filesystem::class)->willImplement(HashPluginInterface::class);
        $destination = $this->prophesize(Filesystem::class)->willImplement(HashPluginInterface::class);
        $database = $this->prophesize(Database::class);

        $source->listFiles('', true)->willReturn(
            [
                [
                    'path' => 'test.json',
                ],
            ]
        );

        $source->readStream('test.json')->willReturn('file resource');
        $source->hash('test.json')->willReturn('123-123-123');

        $destination->has('test/test.json')->willReturn(true);
        $destination->hash('test/test.json')->willReturn('123-123-123');
        $destination->writeStream('test/test.json', 'file resource')->shouldNotBeCalled();

        $this->plugin->restore($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => 'test']);
    }

    public function testRestoreExistingDifferentFile()
    {
        $source = $this->prophesize(Filesystem::class)->willImplement(HashPluginInterface::class);
        $destination = $this->prophesize(Filesystem::class)->willImplement(HashPluginInterface::class);
        $database = $this->prophesize(Database::class);

        $source->listFiles('', true)->willReturn(
            [
                [
                    'path' => 'test.json',
                ],
            ]
        );

        $source->readStream('test.json')->willReturn('file resource');
        $source->hash('test.json')->willReturn('xxx');

        $destination->has('test/test.json')->willReturn(true);
        $destination->delete('test/test.json')->willReturn(true);
        $destination->hash('test/test.json')->willReturn('123-123-123');
        $destination->writeStream('test/test.json', 'file resource')->shouldBeCalled();

        $this->plugin->restore($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => 'test']);
    }
}

interface HashPluginInterface
{
    public function hash($file);
}
