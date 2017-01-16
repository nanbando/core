<?php

namespace Nanbando\Tests\Unit\Core;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nanbando\Core\BackupStatus;
use Nanbando\Core\Database\Database;
use Nanbando\Core\Database\DatabaseFactory;
use Nanbando\Core\Database\ReadonlyDatabase;
use Nanbando\Core\Events\BackupEvent;
use Nanbando\Core\Events\Events;
use Nanbando\Core\Events\PostBackupEvent;
use Nanbando\Core\Events\PreBackupEvent;
use Nanbando\Core\Events\PreRestoreEvent;
use Nanbando\Core\Events\RestoreEvent;
use Nanbando\Core\Nanbando;
use Nanbando\Core\Storage\StorageInterface;
use Nanbando\Tests\Unit\Core\Storage\LocalStorageTest;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webmozart\PathUtil\Path;

class NanbandoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var DatabaseFactory
     */
    private $databaseFactory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function setUp()
    {
        $this->storage = $this->prophesize(StorageInterface::class);
        $this->databaseFactory = $this->prophesize(DatabaseFactory::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->databaseFactory->create(Argument::any())->will(function ($data) {
            return new Database(isset($data[0]) ? $data[0] : []);
        });
        $this->databaseFactory->createReadonly(Argument::any())->will(function ($data) {
            return new ReadonlyDatabase(isset($data[0]) ? $data[0] : []);
        });
    }

    protected function getNanbando(array $backup)
    {
        return new Nanbando(
            $backup,
            $this->storage->reveal(),
            $this->databaseFactory->reveal(),
            $this->eventDispatcher->reveal()
        );
    }

    public function testBackup()
    {
        $nanbando = $this->getNanbando(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'parameter' => [
                        'directory' => 'uploads',
                    ],
                ],
            ]
        );

        $filesystem = new Filesystem(new MemoryAdapter());
        $this->storage->start()->willReturn($filesystem);

        $this->eventDispatcher->dispatch(Events::PRE_BACKUP_EVENT, Argument::type(PreBackupEvent::class))
            ->shouldBeCalled();
        $this->eventDispatcher->dispatch(Events::BACKUP_EVENT, Argument::type(BackupEvent::class))->shouldBeCalled();
        $this->eventDispatcher->dispatch(Events::POST_BACKUP_EVENT, Argument::type(PostBackupEvent::class))
            ->shouldBeCalled();

        $this->storage->close($filesystem)->shouldBeCalled();

        $this->assertEquals(BackupStatus::STATE_SUCCESS, $nanbando->backup());

        $files = $filesystem->listContents('', true);
        $fileNames = array_map(
            function ($item) {
                return $item['path'];
            },
            $files
        );

        $this->assertEquals(
            [
                'database',
                'database/backup',
                'database/backup/uploads.json',
                'database/system.json',
            ],
            $fileNames
        );
    }

    public function testRestore()
    {
        $path = Path::join([DATAFIXTURES_DIR, 'backups', LocalStorageTest::BACKUP_SUCCESS . '.zip']);
        $nanbando = $this->getNanbando(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'parameter' => [
                        'directory' => 'uploads',
                    ],
                ],
            ]
        );

        $filesystem = new Filesystem(new ZipArchiveAdapter($path));
        $this->storage->open('13-21-45-2016-05-29')->willReturn($filesystem);

        $this->eventDispatcher->dispatch(Events::PRE_RESTORE_EVENT, Argument::type(PreRestoreEvent::class))
            ->shouldBeCalled();
        $this->eventDispatcher->dispatch(Events::RESTORE_EVENT, Argument::type(RestoreEvent::class))->shouldBeCalled();
        $this->eventDispatcher->dispatch(Events::POST_RESTORE_EVENT, Argument::type(Event::class))
            ->shouldBeCalled();

        $nanbando->restore('13-21-45-2016-05-29');
    }

    public function testBackupCancelOnPreBackup()
    {
        $nanbando = $this->getNanbando(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'parameter' => [
                        'directory' => 'uploads',
                    ],
                ],
            ]
        );

        $filesystem = new Filesystem(new MemoryAdapter());
        $this->storage->start()->willReturn($filesystem);
        $this->storage->cancel($filesystem)->shouldBeCalled();

        $this->eventDispatcher->dispatch(Events::PRE_BACKUP_EVENT, Argument::type(PreBackupEvent::class))
            ->will(
                function ($arguments) {
                    $arguments[1]->cancel();
                }
            );
        $this->eventDispatcher->dispatch(Events::BACKUP_EVENT, Argument::type(BackupEvent::class))->shouldNotBeCalled();
        $this->eventDispatcher->dispatch(Events::POST_BACKUP_EVENT, Argument::type(PostBackupEvent::class))
            ->shouldNotBeCalled();

        $this->assertEquals(BackupStatus::STATE_FAILED, $nanbando->backup());
    }

    public function testBackupCancelOnBackup()
    {
        $nanbando = $this->getNanbando(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'parameter' => [
                        'directory' => 'uploads',
                    ],
                ],
            ]
        );

        $filesystem = new Filesystem(new MemoryAdapter());
        $this->storage->start()->willReturn($filesystem);
        $this->storage->cancel($filesystem)->shouldBeCalled();

        $this->eventDispatcher->dispatch(Events::PRE_BACKUP_EVENT, Argument::type(PreBackupEvent::class))
            ->shouldBeCalled();
        $this->eventDispatcher->dispatch(Events::BACKUP_EVENT, Argument::type(BackupEvent::class))
            ->will(
                function ($arguments) {
                    $arguments[1]->cancel();
                }
            );
        $this->eventDispatcher->dispatch(Events::POST_BACKUP_EVENT, Argument::type(PostBackupEvent::class))
            ->shouldNotBeCalled();

        $this->assertEquals(BackupStatus::STATE_FAILED, $nanbando->backup());
    }

    public function testBackupCancelOnBackupGoOn()
    {
        $nanbando = $this->getNanbando(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'parameter' => [
                        'directory' => 'uploads',
                    ],
                ],
            ]
        );

        $filesystem = new Filesystem(new MemoryAdapter());
        $this->storage->start()->willReturn($filesystem);
        $this->storage->close($filesystem)->shouldBeCalled();

        $this->eventDispatcher->dispatch(Events::PRE_BACKUP_EVENT, Argument::type(PreBackupEvent::class))
            ->shouldBeCalled();
        $this->eventDispatcher->dispatch(Events::BACKUP_EVENT, Argument::type(BackupEvent::class))
            ->will(
                function ($arguments) {
                    $arguments[1]->setStatus(BackupStatus::STATE_FAILED);
                }
            );
        $this->eventDispatcher->dispatch(Events::POST_BACKUP_EVENT, Argument::type(PostBackupEvent::class))
            ->shouldBeCalled();

        $this->assertEquals(BackupStatus::STATE_PARTIALLY, $nanbando->backup());

        $files = $filesystem->listContents('', true);
        $fileNames = array_map(
            function ($item) {
                return $item['path'];
            },
            $files
        );

        $this->assertEquals(
            [
                'database',
                'database/backup',
                'database/backup/uploads.json',
                'database/system.json',
            ],
            $fileNames
        );
    }
}
