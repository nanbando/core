<?php

namespace Unit\Core\Server\Command\Local;

use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nanbando\Core\Database\Database;
use Nanbando\Core\Database\DatabaseFactory;
use Nanbando\Core\Database\ReadonlyDatabase;
use Nanbando\Core\Events\Events;
use Nanbando\Core\Events\PreRestoreEvent;
use Nanbando\Core\Events\RestoreEvent;
use Nanbando\Core\Server\Command\Local\LocalRestoreCommand;
use Nanbando\Core\Storage\StorageInterface;
use Nanbando\Tests\Unit\Core\Storage\LocalStorageTest;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Webmozart\PathUtil\Path;

/**
 * Tests for class "LocalRestoreCommand".
 */
class LocalRestoreCommandTest extends TestCase
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
     * @return LocalRestoreCommand
     */
    protected function createCommand(array $backup): LocalRestoreCommand
    {
        return new LocalRestoreCommand(
            $this->storage->reveal(),
            $this->databaseFactory->reveal(),
            $this->eventDispatcher->reveal(),
            $backup
        );
    }

    public function testRestore(): void
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

        $path = Path::join([DATAFIXTURES_DIR, 'backups', LocalStorageTest::BACKUP_SUCCESS . '.zip']);
        $filesystem = new Filesystem(new ZipArchiveAdapter($path));
        $this->storage->open('13-21-45-2016-05-29')->willReturn($filesystem);

        $this->eventDispatcher->dispatch(Argument::type(PreRestoreEvent::class), Events::PRE_RESTORE_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(RestoreEvent::class), Events::RESTORE_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());
        $this->eventDispatcher->dispatch(Argument::type(Event::class), Events::POST_RESTORE_EVENT)
            ->shouldBeCalled()
            ->willReturn(new \stdClass());

        $nanbando->execute(['name' => '13-21-45-2016-05-29']);
    }
}
