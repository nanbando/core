<?php

namespace Nanbando\Tests\Unit\Core\Storage;

use Cocur\Slugify\SlugifyInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nanbando\Core\Flysystem\ReadonlyAdapter;
use Nanbando\Core\Storage\LocalStorage;
use Nanbando\Core\Storage\RemoteStorageNotConfiguredException;
use Nanbando\Core\Storage\StorageInterface;
use Nanbando\Core\Temporary\TemporaryFileManager;
use Prophecy\Argument;
use Webmozart\PathUtil\Path;

class LocalStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $name = 'test';

    /**
     * @var TemporaryFileManager
     */
    private $temporaryFileManager;

    /**
     * @var Filesystem
     */
    private $localFilesystem;

    /**
     * @var Filesystem
     */
    private $remoteFilesystem;

    /**
     * @var SlugifyInterface
     */
    private $slugify;

    /**
     * @var StorageInterface
     */
    private $storage;

    public function setUp()
    {
        $this->temporaryFileManager = $this->prophesize(TemporaryFileManager::class);
        $this->localFilesystem = $this->prophesize(Filesystem::class);
        $this->remoteFilesystem = $this->prophesize(Filesystem::class);
        $this->slugify = $this->prophesize(SlugifyInterface::class);

        $this->storage = new LocalStorage(
            $this->name,
            $this->temporaryFileManager->reveal(),
            $this->slugify->reveal(),
            $this->localFilesystem->reveal(),
            $this->remoteFilesystem->reveal()
        );
    }

    public function testStart()
    {
        $tempFile = tempnam('/tmp', 'nanbando');
        $this->temporaryFileManager->getFilename()->willReturn($tempFile);

        $filesystem = $this->storage->start();

        $this->assertInstanceOf(Filesystem::class, $filesystem);
        $this->assertInstanceOf(ZipArchiveAdapter::class, $filesystem->getAdapter());
        $this->assertEquals($tempFile, $filesystem->getAdapter()->getArchive()->filename);

        return $filesystem;
    }

    public function testClose()
    {
        $filesystem = $this->testStart();

        $name = date('H-i-s-Y-m-d');
        $this->localFilesystem->putStream('test/' . $name . '.zip', Argument::any())->shouldBeCalled();

        $result = $this->storage->close($filesystem);

        $this->assertEquals($name, $result);

        return $name;
    }

    public function testCloseLabel()
    {
        $filesystem = $this->testStart();

        $name = date('H-i-s-Y-m-d');
        $this->localFilesystem->putStream('test/' . $name . '_test.zip', Argument::any())->shouldBeCalled();
        $this->slugify->slugify('test')->willReturn('test');

        $result = $this->storage->close($filesystem, 'test');

        $this->assertEquals($name . '_test', $result);
    }

    public function testOpen()
    {
        $name = $this->testClose();
        $path = Path::join([RESOURCE_DIR, 'backups', '13-21-45-2016-05-29.zip']);

        $tempFile = tempnam('/tmp', 'nanbando');
        $this->temporaryFileManager->getFilename()->willReturn($tempFile);

        $this->localFilesystem->readStream(sprintf('%s/%s.zip', $this->name, $name))
            ->willReturn(file_get_contents($path));

        $filesystem = $this->storage->open($name);

        $this->assertInstanceOf(Filesystem::class, $filesystem);
        $this->assertInstanceOf(ReadonlyAdapter::class, $filesystem->getAdapter());
        $this->assertInstanceOf(ZipArchiveAdapter::class, $filesystem->getAdapter()->getAdapter());
        $this->assertEquals($tempFile, $filesystem->getAdapter()->getAdapter()->getArchive()->filename);
    }

    public function testLocalListing()
    {
        $this->localFilesystem->listFiles($this->name)
            ->willReturn([['filename' => 'test-1'], ['filename' => 'test-2']]);

        $this->assertEquals(['test-1', 'test-2'], $this->storage->localListing());
    }

    public function testRemoteListing()
    {
        $this->remoteFilesystem->listFiles($this->name)
            ->willReturn([['filename' => 'test-1'], ['filename' => 'test-2']]);

        $this->assertEquals(['test-1', 'test-2'], $this->storage->remoteListing());
    }

    public function testSize()
    {
        $path = Path::join([RESOURCE_DIR, 'backups', '13-21-45-2016-05-29.zip']);

        $adapter = new ZipArchiveAdapter($path);
        $filesystem = new Filesystem(new ReadonlyAdapter($adapter));

        $this->assertEquals(filesize($path), $this->storage->size($filesystem));
    }

    public function testFetch()
    {
        $zipPath = Path::join([RESOURCE_DIR, 'backups', '13-21-45-2016-05-29.zip']);

        $file = '123-123-123';
        $path = sprintf('%s/%s.zip', $this->name, $file);
        $this->remoteFilesystem->readStream($path)->willReturn(file_get_contents($zipPath));
        $this->localFilesystem->putStream($path, Argument::any())->shouldBeCalled();

        $this->storage->fetch($file);
    }

    public function testFetchNotExists()
    {
        $file = '123-123-123';
        $path = sprintf('%s/%s.zip', $this->name, $file);
        $this->remoteFilesystem->readStream($path)->willReturn(false);
        $this->localFilesystem->putStream($path, Argument::any())->shouldNotBeCalled();

        $this->storage->fetch($file);
    }

    public function testPush()
    {
        $zipPath = Path::join([RESOURCE_DIR, 'backups', '13-21-45-2016-05-29.zip']);

        $file = '123-123-123';
        $path = sprintf('%s/%s.zip', $this->name, $file);
        $this->localFilesystem->readStream($path)->willReturn(file_get_contents($zipPath));
        $this->remoteFilesystem->putStream($path, Argument::any())->shouldBeCalled();

        $this->storage->push($file);
    }

    public function testPushNotExists()
    {
        $file = '123-123-123';
        $path = sprintf('%s/%s.zip', $this->name, $file);
        $this->localFilesystem->readStream($path)->willReturn(false);
        $this->remoteFilesystem->putStream($path, Argument::any())->shouldNotBeCalled();

        $this->storage->push($file);
    }

    public function testPushNoRemote()
    {
        $this->setExpectedException(RemoteStorageNotConfiguredException::class);

        $storage = new LocalStorage(
            $this->name,
            $this->temporaryFileManager->reveal(),
            $this->slugify->reveal(),
            $this->localFilesystem->reveal()
        );

        $storage->push('test');
    }

    public function testFetchNoRemote()
    {
        $this->setExpectedException(RemoteStorageNotConfiguredException::class);

        $storage = new LocalStorage(
            $this->name,
            $this->temporaryFileManager->reveal(),
            $this->slugify->reveal(),
            $this->localFilesystem->reveal()
        );

        $storage->fetch('test');
    }

    public function testRemoteListingNoRemote()
    {
        $this->setExpectedException(RemoteStorageNotConfiguredException::class);

        $storage = new LocalStorage(
            $this->name,
            $this->temporaryFileManager->reveal(),
            $this->slugify->reveal(),
            $this->localFilesystem->reveal()
        );

        $storage->remoteListing();
    }
}
