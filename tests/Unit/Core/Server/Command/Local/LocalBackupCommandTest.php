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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests for class "LocalBackupCommand".
 */
class LocalBackupCommandTest extends TestCase
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
    public function setUp(): void
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
    private function createCommand(array $backup): LocalBackupCommand
    {
        return new LocalBackupCommand(
            $this->storage->reveal(),
            $this->databaseFactory->reveal(),
            $this->eventDispatcher->reveal(),
            $backup
        );
    }

    public function testBackup(): void
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

        $this->eventDispatcher->dispatch(Argument::type(PreBackupEvent::class), Events::PRE_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(BackupEvent::class), Events::BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(PostBackupEvent::class), Events::POST_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());

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

    public function testBackupCancelOnPreBackup(): void
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

        $this->eventDispatcher->dispatch(Argument::type(PreBackupEvent::class), Events::PRE_BACKUP_EVENT)
            ->will(
                function ($arguments) {
                    $arguments[0]->cancel();

                    return $arguments[0];
                }
            );
        $this->eventDispatcher->dispatch(Argument::type(BackupEvent::class), Events::BACKUP_EVENT)
            ->shouldNotBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(PostBackupEvent::class), Events::POST_BACKUP_EVENT)
            ->shouldNotBeCalled()
            ->willReturn(new \stdClass());

        $this->assertEquals(BackupStatus::STATE_FAILED, $nanbando->execute());
    }

    public function testBackupCancelOnBackup(): void
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

        $this->eventDispatcher->dispatch(Argument::type(PreBackupEvent::class), Events::PRE_BACKUP_EVENT)
            ->shouldBeCalled();
        $this->eventDispatcher->dispatch(Argument::type(BackupEvent::class), Events::BACKUP_EVENT)
            ->will(
                function ($arguments) {
                    $arguments[0]->cancel();

                    return $arguments[0];
                }
            );
        $this->eventDispatcher->dispatch(Argument::type(PostBackupEvent::class), Events::POST_BACKUP_EVENT)
            ->shouldNotBeCalled()
            ->willReturn(new \stdClass());

        $this->assertEquals(BackupStatus::STATE_FAILED, $nanbando->execute());
    }

    public function testBackupCancelOnBackupGoOn(): void
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

        $this->eventDispatcher->dispatch(Argument::type(PreBackupEvent::class), Events::PRE_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(BackupEvent::class), Events::BACKUP_EVENT)
            ->will(
                function ($arguments) {
                    $arguments[0]->setStatus(BackupStatus::STATE_FAILED);

                    return $arguments[0];
                }
            );
        $this->eventDispatcher->dispatch(Argument::type(PostBackupEvent::class), Events::POST_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());

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

    public function testBackupProcess(): void
    {
        $nanbando = $this->createCommand(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'process' => ['test'],
                    'parameter' => [
                        'directory' => 'uploads',
                    ],
                ],
            ]
        );

        $filesystem = new Filesystem(new MemoryAdapter());
        $this->storage->start()->willReturn($filesystem);

        $this->eventDispatcher->dispatch(Argument::type(PreBackupEvent::class), Events::PRE_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(BackupEvent::class), Events::BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(PostBackupEvent::class), Events::POST_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());

        $this->storage->close($filesystem)->shouldBeCalled();

        $this->assertEquals(BackupStatus::STATE_SUCCESS, $nanbando->execute(['process' => ['test']]));
    }

    public function testBackupProcessNoProcessGiven(): void
    {
        $nanbando = $this->createCommand(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'process' => ['test'],
                    'parameter' => [
                        'directory' => 'uploads',
                    ],
                ],
            ]
        );

        $filesystem = new Filesystem(new MemoryAdapter());
        $this->storage->start()->willReturn($filesystem);

        $this->eventDispatcher->dispatch(Argument::type(PreBackupEvent::class), Events::PRE_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(BackupEvent::class), Events::BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(PostBackupEvent::class), Events::POST_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());

        $this->storage->close($filesystem)->shouldBeCalled();

        $this->assertEquals(BackupStatus::STATE_SUCCESS, $nanbando->execute());
    }

    public function testBackupWrongProcess(): void
    {
        $nanbando = $this->createCommand(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'process' => ['test'],
                    'parameter' => [
                        'directory' => 'uploads',
                    ],
                ],
            ]
        );

        $filesystem = new Filesystem(new MemoryAdapter());
        $this->storage->start()->willReturn($filesystem);

        $this->eventDispatcher->dispatch(Argument::type(PreBackupEvent::class), Events::PRE_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(BackupEvent::class), Events::BACKUP_EVENT)
            ->shouldNotBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(PostBackupEvent::class), Events::POST_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());

        $this->storage->close($filesystem)->shouldBeCalled();

        $this->assertEquals(BackupStatus::STATE_SUCCESS, $nanbando->execute(['process' => ['test-2']]));
    }

    public function testBackupNoProcess(): void
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

        $this->eventDispatcher->dispatch(Argument::type(PreBackupEvent::class), Events::PRE_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(BackupEvent::class), Events::BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(PostBackupEvent::class), Events::POST_BACKUP_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());

        $this->storage->close($filesystem)->shouldBeCalled();

        $this->assertEquals(BackupStatus::STATE_SUCCESS, $nanbando->execute(['process' => []]));
    }
}
