<?php

namespace Unit\Core\Server\Command\Ssh;

use Nanbando\Core\Server\Command\Ssh\SshConnection;
use Nanbando\Core\Server\Command\Ssh\SshInformationCommand;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests for class "SshInformationCommand".
 */
class SshInformationCommandTest extends TestCase
{
    /**
     * @var SshConnection
     */
    private $connection;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var SshInformationCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->connection = $this->prophesize(SshConnection::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new SshInformationCommand(
            $this->connection->reveal(),
            $this->input->reveal(),
            $this->output->reveal()
        );
    }

    public function testExecute($file = '2017-01-01')
    {
        $this->connection->executeNanbando('information', [$file], Argument::type('callable'))->shouldBeCalled();

        $this->command->execute(['file' => $file]);
    }
}
