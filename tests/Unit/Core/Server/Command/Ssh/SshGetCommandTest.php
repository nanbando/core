<?php

namespace Unit\Core\Server\Command\Ssh;

use Nanbando\Core\Server\Command\Ssh\SshConnection;
use Nanbando\Core\Server\Command\Ssh\SshGetCommand;
use Nanbando\Core\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests for class "SshGetCommand".
 */
class SshGetCommandTest extends TestCase
{
    /**
     * @var SshConnection
     */
    private $connection;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var SshGetCommand
     */
    private $command;

    protected function setUp()
    {
        $this->connection = $this->prophesize(SshConnection::class);
        $this->storage = $this->prophesize(StorageInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new SshGetCommand(
            $this->connection->reveal(),
            $this->storage->reveal(),
            $this->output->reveal()
        );
    }

    public function testExecute($name = '2017-01-01')
    {
        $remotePath = '/var/backups/2017-01-01.zip';
        $localPath = '/var/local/backup/2017-01-01.zip';

        $this->connection->executeNanbando('information', [$name], null)->willReturn('path: ' . $remotePath . PHP_EOL);

        $this->storage->path($name)->willReturn($localPath);
        $this->connection->get($remotePath, $localPath)->shouldBeCalled();

        $this->command->execute(['name' => $name]);
    }
}
