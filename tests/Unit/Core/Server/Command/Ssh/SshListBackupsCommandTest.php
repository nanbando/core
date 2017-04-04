<?php

namespace Unit\Core\Server\Command\Ssh;

use Nanbando\Core\Server\Command\Ssh\SshConnection;
use Nanbando\Core\Server\Command\Ssh\SshListBackupsCommand;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests for class "SshListBackupsCommand".
 */
class SshListBackupsCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SshConnection
     */
    private $connection;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var SshListBackupsCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->connection = $this->prophesize(SshConnection::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new SshListBackupsCommand($this->connection->reveal(), $this->output->reveal());
    }

    public function testExecute()
    {
        $this->connection->executeNanbando('list:backups', [], Argument::any())->shouldBeCalled();

        $this->command->execute();
    }

    public function testExecuteWithRemoteFlag()
    {
        $this->connection->executeNanbando('list:backups', ['-r' => ''], Argument::type('callable'))
            ->shouldBeCalled();

        $this->command->execute(['remote' => true]);
    }
}
