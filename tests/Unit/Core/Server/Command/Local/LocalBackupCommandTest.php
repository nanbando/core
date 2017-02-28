<?php

namespace Nanbando\Tests\Unit\Core\Server\Command\Local;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Nanbando\Core\BackupStatus;
use Nanbando\Core\Database\Database;
use Nanbando\Core\Database\DatabaseFactory;
use Nanbando\Core\Database\ReadonlyDatabase;
use Nanbando\Core\Events\BackupEvent;
use Nanbando\Core\Events\Events;
use Nanbando\Core\Events\PostBackupEvent;
use Nanbando\Core\Events\PreBackupEvent;
use Nanbando\Core\Server\Command\Local\LocalBackupCommand;
use Nanbando\Core\Storage\StorageInterface;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests for class "LocalBackupCommand".
 */
class LocalBackupCommandTest extends \PHPUnit_Framework_TestCase
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

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->storage = $this->prophesize(StorageInterface::class);
        $this->databaseFactory = $this->prophesize(DatabaseFactory::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->databaseFactory->create(Argument::any())->will(
            function ($data) {
                return new Database(isset($data[0]) ? $data[0] : []);
            }
        );
        $this->databaseFactory->createReadonly(Argument::any())->will(
            function ($data) {
                return new ReadonlyDatabase(isset($data[0]) ? $data[0] : []);
            }
        );
    }

    /**
     * Create command with given configuration.
     *
     * @param array $backup
     *
     * @return LocalBackupCommand
     */
    private function createCommand(array $backup)
    {
        return new LocalBackupCommand(
            $this->storage->reveal(),
            $this->databaseFactory->reveal(),
            $this->eventDispatcher->reveal(),
            $backup
        );
    }

    public function testBackup()
    {
        $nanbando = $this->createCommand(
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

        $this->assertEquals(BackupStatus::STATE_SUCCESS, $nanbando->execute());

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

    public function testBackupCancelOnPreBackup()
    {
        $nanbando = $this->createCommand(
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

        $this->assertEquals(BackupStatus::STATE_FAILED, $nanbando->execute());
    }

    public function testBackupCancelOnBackup()
    {
        $nanbando = $this->createCommand(
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

        $this->assertEquals(BackupStatus::STATE_FAILED, $nanbando->execute());
    }

    public function testBackupCancelOnBackupGoOn()
    {
        $nanbando = $this->createCommand(
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

        $this->assertEquals(BackupStatus::STATE_PARTIALLY, $nanbando->execute());

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
