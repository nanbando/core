<?php

namespace Nanbando\Tests\Unit\Core\Plugin;

use League\Flysystem\Filesystem;
use Nanbando\Core\Database\Database;
use Nanbando\Core\Plugin\DirectoryPlugin;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DirectoryPluginTest extends TestCase
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var DirectoryPlugin
     */
    private $plugin;

    public function setUp(): void
    {
        $this->output = new NullOutput();

        $this->plugin = new DirectoryPlugin($this->output);
    }

    public function testConfigureOptionsResolver(): void
    {
        $optionsResolver = $this->prophesize(OptionsResolver::class);

        $this->plugin->configureOptionsResolver($optionsResolver->reveal());

        $optionsResolver->setRequired('directory')->shouldBeCalled();
    }

    public function testBackup(): void
    {
        $source = $this->prophesize(Filesystem::class);
        $destination = $this->prophesize(Filesystem::class);
        $database = $this->prophesize(Database::class);

        $source->has('test')->wilLReturn(true);
        $source->listFiles('test', true)->willReturn(
            [
                [
                    'path' => 'test/test.json',
                ],
            ]
        );

        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'file resource');
        rewind($stream);

        $source->readStream('test/test.json')->willReturn($stream);
        $destination->writeStream('test.json', $stream)->shouldBeCalled();

        $this->plugin->backup($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => 'test']);
        $this->assertTrue(is_resource($stream));
    }

    public function testRestore(): void
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

        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'file resource');
        rewind($stream);

        $source->readStream('test.json')->willReturn($stream);

        $destination->has('test/test.json')->willReturn(false);
        $destination->writeStream('test/test.json', $stream)->shouldBeCalled();

        $database->getWithDefault('metadata', [])->willReturn(['test.json' => ['hash' => '123-123-123']]);

        $this->plugin->restore($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => 'test']);
        $this->assertFalse(is_resource($stream));
    }

    public function testRestoreExistingFile(): void
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

        $source->readStream('test.json')->shouldNotBeCalled();
        $source->hash('test.json')->shouldNotBeCalled();

        $destination->has('test/test.json')->willReturn(true);
        $destination->hash('test/test.json')->willReturn('123-123-123');
        $destination->writeStream('test/test.json', Argument::any())->shouldNotBeCalled();

        $database->getWithDefault('metadata', [])->willReturn(['test.json' => ['hash' => '123-123-123']]);

        $this->plugin->restore($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => 'test']);
    }

    public function testRestoreExistingDifferentFile(): void
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

        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'file resource');
        rewind($stream);

        $source->readStream('test.json')->willReturn($stream);
        $source->hash('test.json')->shouldNotBeCalled();

        $destination->has('test/test.json')->willReturn(true);
        $destination->delete('test/test.json')->willReturn(true);
        $destination->hash('test/test.json')->willReturn('123-123-123');
        $destination->writeStream('test/test.json', $stream)->shouldBeCalled();

        $database->getWithDefault('metadata', [])->willReturn(['test.json' => ['hash' => 'abc-abc-abc']]);

        $this->plugin->restore($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => 'test']);
        $this->assertFalse(is_resource($stream));
    }
}

interface HashPluginInterface
{
    public function hash($file);
}
