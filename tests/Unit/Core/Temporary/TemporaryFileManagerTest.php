<?php

namespace Nanbando\Tests\Unit\Core\Temporary;

use Nanbando\Core\Temporary\TemporaryFileManager;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class TemporaryFileManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TemporaryFileManager
     */
    private $fileManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $name = 'nanbando';

    /**
     * @var string
     */
    private $tempFolder = __DIR__;

    public function setUp()
    {
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->fileManager = new TemporaryFileManager($this->filesystem->reveal(), $this->name, $this->tempFolder);
    }

    public function testGetFilename()
    {
        $path = Path::join([$this->tempFolder, $this->name]);
        $this->filesystem->tempnam($this->tempFolder, $this->name)->willReturn($path);

        $this->assertEquals($path, $this->fileManager->getFilename());
    }

    public function testGetFilenameWithPrefix()
    {
        $path = Path::join([$this->tempFolder, $this->name . 'test']);
        $this->filesystem->tempnam($this->tempFolder, $this->name . 'test')->willReturn($path);

        $this->assertEquals($path, $this->fileManager->getFilename('test'));
    }

    public function testCleanup()
    {
        $path = Path::join([$this->tempFolder, $this->name]);
        $this->filesystem->tempnam($this->tempFolder, $this->name)->willReturn($path);
        $this->filesystem->remove([$path])->shouldBeCalled();

        $this->assertEquals($path, $this->fileManager->getFilename());
        $this->fileManager->cleanup();
    }
}
