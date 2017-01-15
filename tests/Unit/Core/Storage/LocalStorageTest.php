<?php

namespace Nanbando\Tests\Unit\Core\Storage;

use Cocur\Slugify\SlugifyInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nanbando\Core\Flysystem\ReadonlyAdapter;
use Nanbando\Core\Storage\LocalStorage;
use Nanbando\Core\Storage\RemoteStorageNotConfiguredException;
use Nanbando\Core\Storage\StorageInterface;
use Neutron\TemporaryFilesystem\TemporaryFilesystemInterface;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Webmozart\PathUtil\Path;

class LocalStorageTest extends \PHPUnit_Framework_TestCase
{
    const BACKUP_SUCCESS = '2016-05-29-13-21-37_success';
    const BACKUP_FAIL = '2016-05-29-13-20-37_failed';

    /**
     * @var string
     */
    private $name = 'test';

    /**
     * @var TemporaryFilesystemInterface
     */
    private $temporaryFileSystem;

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
     * @var SymfonyFilesystem
     */
    private $filesystem;

    /**
     * @var StorageInterface
     */
    private $storage;

    public function setUp()
    {
        $this->temporaryFileSystem = $this->prophesize(TemporaryFilesystemInterface::class);
        $this->localFilesystem = $this->prophesize(Filesystem::class);
        $this->remoteFilesystem = $this->prophesize(Filesystem::class);
        $this->slugify = $this->prophesize(SlugifyInterface::class);
        $this->filesystem = $this->prophesize(SymfonyFilesystem::class);

        $this->storage = new LocalStorage(
            $this->name,
            $this->temporaryFileSystem->reveal(),
            $this->slugify->reveal(),
            $this->filesystem->reveal(),
            $this->localFilesystem->reveal(),
            Path::join([DATAFIXTURES_DIR, 'backups']),
            $this->remoteFilesystem->reveal()
        );
    }

    public function testStart()
    {
        $tempFile = tempnam('/tmp', 'nanbando');
        $this->temporaryFileSystem->createTemporaryFile()->willReturn($tempFile);

        $filesystem = $this->storage->start();

        $this->assertInstanceOf(Filesystem::class, $filesystem);
        $this->assertInstanceOf(ZipArchiveAdapter::class, $filesystem->getAdapter());
        $this->assertEquals($tempFile, $filesystem->getAdapter()->getArchive()->filename);

        return $filesystem;
    }

    public function testCancel()
    {
        $filesystem = $this->testStart();

        /** @var \ZipArchive $archive */
        $archive = $filesystem->getAdapter()->getArchive();
        $this->filesystem->remove($archive->filename);

        $this->storage->cancel($filesystem);
    }

    public function testClose()
    {
        $filesystem = $this->testStart();

        $name = date(LocalStorage::FILE_NAME_PATTERN);
        $this->localFilesystem->putStream('test/' . $name . '.zip', Argument::any())->shouldBeCalled();

        $result = $this->storage->close($filesystem);

        $this->assertEquals($name, $result);

        return $name;
    }

    public function testCloseLabel()
    {
        $filesystem = $this->testStart();

        $name = date(LocalStorage::FILE_NAME_PATTERN);
        $this->localFilesystem->putStream('test/' . $name . '_test.zip', Argument::any())->shouldBeCalled();
        $this->slugify->slugify('test')->willReturn('test');

        $result = $this->storage->close($filesystem, 'test');

        $this->assertEquals($name . '_test', $result);
    }

    public function testOpen()
    {
        $name = $this->testClose();
        $path = Path::join([DATAFIXTURES_DIR, 'backups', self::BACKUP_SUCCESS . '.zip']);

        $tempFile = tempnam('/tmp', 'nanbando');
        $this->temporaryFileSystem->createTemporaryFile()->willReturn($tempFile);

        $this->localFilesystem->readStream(sprintf('%s/%s.zip', $this->name, $name))
            ->willReturn(file_get_contents($path));

        $filesystem = $this->storage->open($name);

        $this->assertInstanceOf(Filesystem::class, $filesystem);
        $this->assertInstanceOf(ReadonlyAdapter::class, $filesystem->getAdapter());
        $this->assertInstanceOf(ZipArchiveAdapter::class, $filesystem->getAdapter()->getAdapter());
        $this->assertEquals($tempFile, $filesystem->getAdapter()->getAdapter()->getArchive()->filename);
    }

    public function testOpenAbsolutePath()
    {
        $path = Path::join([DATAFIXTURES_DIR, 'backups', self::BACKUP_SUCCESS . '.zip']);

        $this->temporaryFileSystem->createTemporaryFile()->shouldNotBeCalled();
        $this->localFilesystem->readStream(Argument::any())->shouldNotBeCalled();

        $filesystem = $this->storage->open($path);

        $this->assertInstanceOf(Filesystem::class, $filesystem);
        $this->assertInstanceOf(ReadonlyAdapter::class, $filesystem->getAdapter());
        $this->assertInstanceOf(ZipArchiveAdapter::class, $filesystem->getAdapter()->getAdapter());
        $this->assertEquals($path, $filesystem->getAdapter()->getAdapter()->getArchive()->filename);
    }

    public function testLocalListing()
    {
        $this->localFilesystem->listFiles($this->name)
            ->willReturn(
                [
                    ['filename' => '2016-12-24-09-23-38'],
                    ['filename' => '2016-12-01-17-24-51'],
                    ['filename' => '2016-12-01-17-40-15'],
                ]
            );

        $this->assertEquals(
            ['2016-12-01-17-24-51', '2016-12-01-17-40-15', '2016-12-24-09-23-38'],
            $this->storage->localListing()
        );
    }

    public function testRemoteListing()
    {
        $this->remoteFilesystem->listFiles($this->name)
            ->willReturn(
                [
                    ['filename' => '2016-12-24-09-23-38_test-1'],
                    ['filename' => '2016-12-01-17-24-51_test-2'],
                    ['filename' => '2016-12-01-17-40-15_test-3'],
                ]
            );

        $this->assertEquals(
            ['2016-12-01-17-24-51_test-2', '2016-12-01-17-40-15_test-3', '2016-12-24-09-23-38_test-1'],
            $this->storage->remoteListing()
        );
    }

    public function testSize()
    {
        $path = Path::join([DATAFIXTURES_DIR, 'backups', self::BACKUP_SUCCESS . '.zip']);

        $this->localFilesystem->getSize(Path::join(['test', self::BACKUP_SUCCESS . '.zip']))
            ->willReturn(filesize($path));

        $this->assertEquals(filesize($path), $this->storage->size(self::BACKUP_SUCCESS));
    }

    public function testPath()
    {
        $path = Path::join([DATAFIXTURES_DIR, 'backups', 'test', self::BACKUP_SUCCESS . '.zip']);

        $this->assertEquals($path, $this->storage->path(self::BACKUP_SUCCESS));
    }

    public function testFetch()
    {
        $zipPath = Path::join([DATAFIXTURES_DIR, 'backups', self::BACKUP_SUCCESS . '.zip']);

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
        $zipPath = Path::join([DATAFIXTURES_DIR, 'backups', self::BACKUP_SUCCESS . '.zip']);

        $file = '123-123-123';
        $path = sprintf('%s/%s.zip', $this->name, $file);
        $this->localFilesystem->readStream($path)->willReturn(file_get_contents($zipPath));
        $this->remoteFilesystem->has($path)->willReturn(false);
        $this->remoteFilesystem->putStream($path, Argument::any())->shouldBeCalled();

        $this->storage->push($file);
    }

    public function testPushExistsRemote()
    {
        $zipPath = Path::join([DATAFIXTURES_DIR, 'backups', self::BACKUP_SUCCESS . '.zip']);

        $file = '123-123-123';
        $path = sprintf('%s/%s.zip', $this->name, $file);
        $this->localFilesystem->readStream($path)->willReturn(file_get_contents($zipPath));
        $this->remoteFilesystem->has($path)->willReturn(true);
        $this->remoteFilesystem->putStream($path, Argument::any())->shouldNotBeCalled();

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
            $this->temporaryFileSystem->reveal(),
            $this->slugify->reveal(),
            $this->filesystem->reveal(),
            $this->localFilesystem->reveal(),
            Path::join([DATAFIXTURES_DIR, 'backups'])
     );

        $storage->push('test');
    }

    public function testFetchNoRemote()
    {
        $this->setExpectedException(RemoteStorageNotConfiguredException::class);

        $storage = new LocalStorage(
            $this->name,
            $this->temporaryFileSystem->reveal(),
            $this->slugify->reveal(),
            $this->filesystem->reveal(), $this->localFilesystem->reveal(),
            Path::join([DATAFIXTURES_DIR, 'backups'])
        );

        $storage->fetch('test');
    }

    public function testRemoteListingNoRemote()
    {
        $this->setExpectedException(RemoteStorageNotConfiguredException::class);

        $storage = new LocalStorage(
            $this->name,
            $this->temporaryFileSystem->reveal(),
            $this->slugify->reveal(),
            $this->filesystem->reveal(),
            $this->localFilesystem->reveal(),
            Path::join([DATAFIXTURES_DIR, 'backups'])
        );

        $storage->remoteListing();
    }
}
