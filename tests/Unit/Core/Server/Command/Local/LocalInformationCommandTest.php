<?php

namespace Unit\Core\Server\Command\Local;

use League\Flysystem\FilesystemInterface;
use Nanbando\Core\Database\DatabaseFactory;
use Nanbando\Core\Database\ReadonlyDatabase;
use Nanbando\Core\Server\Command\Local\LocalInformationCommand;
use Nanbando\Core\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests for class "LocalInformationCommand".
 */
class LocalInformationCommandTest extends TestCase
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
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var LocalInformationCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->storage = $this->prophesize(StorageInterface::class);
        $this->databaseFactory = $this->prophesize(DatabaseFactory::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new LocalInformationCommand(
            $this->storage->reveal(), $this->databaseFactory->reveal(), $this->input->reveal(), $this->output->reveal()
        );
    }

    public function testExecute($file = '2017-01-01-00-00'): void
    {
        $filesystem = $this->prophesize(FilesystemInterface::class);
        $filesystem->read('database/system.json')->willReturn('{"test":"test"}');

        $this->storage->open($file)->willReturn($filesystem->reveal());
        $this->storage->size($file)->willReturn(1024);
        $this->storage->path($file)->willReturn('/var/backups/test.zip');

        $database = $this->prophesize(ReadonlyDatabase::class);
        $this->databaseFactory->createReadonly(['test' => 'test'])->willReturn($database->reveal())->shouldBeCalled();

        $this->command->execute(['file' => $file]);
    }
}
