<?php

namespace Unit\Core\Server\Command\Local;

use Nanbando\Core\Server\Command\Local\LocalListBackupsCommand;
use Nanbando\Core\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests for class "LocalListBackupsCommand".
 */
class LocalListBackupsCommandTest extends TestCase
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var LocalListBackupsCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->storage = $this->prophesize(StorageInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new LocalListBackupsCommand($this->storage->reveal(), $this->output->reveal());
    }

    public function testExecute(): void
    {
        $this->storage->localListing()->willReturn(['2017-01-01-00-00-00', '2016-01-01-00-00-00']);

        $this->output->writeln('2017-01-01-00-00-00')->shouldBeCalled();
        $this->output->writeln('2016-01-01-00-00-00')->shouldBeCalled();

        $this->command->execute();
    }

    public function testExecuteWithRemoteFlag(): void
    {
        $this->storage->remoteListing()->willReturn(['2017-01-01-00-00-00', '2016-01-01-00-00-00']);

        $this->output->writeln('2017-01-01-00-00-00')->shouldBeCalled();
        $this->output->writeln('2016-01-01-00-00-00')->shouldBeCalled();

        $this->command->execute(['remote' => true]);
    }
}
