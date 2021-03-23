<?php

namespace Unit\Core\Server\Command\Ssh;

use Nanbando\Core\BackupStatus;
use Nanbando\Core\Server\Command\Ssh\SshBackupCommand;
use Nanbando\Core\Server\Command\Ssh\SshConnection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests for class "SshBackupCommand".
 */
class SshBackupCommandTest extends TestCase
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
     * @var SshBackupCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->connection = $this->prophesize(SshConnection::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->command = new SshBackupCommand($this->connection->reveal(), $this->output->reveal());
    }

    public function provideData(): array
    {
        return [
            ['successfully', BackupStatus::STATE_SUCCESS],
            ['failed', BackupStatus::STATE_FAILED],
            ['partially', BackupStatus::STATE_PARTIALLY],
        ];
    }

    /**
     * @dataProvider provideData
     */
    public function testExecute($output, $status, $label = 'test-label', $message = 'test-message'): void
    {
        $this->connection->executeNanbando('backup', [$label, '-m' => '"' . $message . '"'], Argument::type('callable'))
            ->shouldBeCalled()
            ->will(
                function ($arguments) use ($output) {
                    $arguments[2]('"2017-01-01" finished ' . $output);
                }
            );

        $result = $this->command->execute(['label' => $label, 'message' => $message]);

        $this->assertEquals($status, $result);
    }

    /**
     * @dataProvider provideData
     */
    public function testExecuteWithProcess($output, $status, $label = 'test-label', $message = 'test-message'): void
    {
        $this->connection->executeNanbando(
                'backup',
                [$label, '-m' => '"' . $message . '"', '-p' => ['test-1']],
                Argument::type('callable')
            )
            ->shouldBeCalled()
            ->will(
                function ($arguments) use ($output) {
                    $arguments[2]('"2017-01-01" finished ' . $output);
                }
            );

        $result = $this->command->execute(['label' => $label, 'message' => $message, 'process' => ['test-1']]);

        $this->assertEquals($status, $result);
    }
}
