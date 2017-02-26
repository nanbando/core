<?php

namespace Unit\Core\Server\Command\Ssh;

use Nanbando\Core\Server\Command\Ssh\SshConnection;
use phpseclib\Net\SCP;
use phpseclib\Net\SSH2;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests for class "SshConnection".
 *
 * TODO implement tests
 */
class SshConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SSH2
     */
    private $ssh;

    /**
     * @var SCP
     */
    private $scp;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $name = 'test';

    /**
     * @var string
     */
    private $directory = 'test-directory';

    /**
     * @var string
     */
    private $executable = 'nanbando.phar';

    protected function createConnection(array $sshConfig)
    {
        $this->ssh = $this->prophesize(SSH2::class);
        $this->scp = $this->prophesize(SCP::class);
        $this->input = $this->prophesize(InputInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->ssh->exec("\nif [ -d \"" . $this->directory . "\" ]; then\n  echo \"exists\"\nfi")->willReturn('exists');
        $this->ssh->exec(
            'cd ' . $this->directory . "\nif [ -x \"" . $this->executable . "\" ]; then\n  echo \"exists\"\nfi"
        )->willReturn('exists');

        return new SshConnection(
            $this->ssh->reveal(),
            $this->scp->reveal(),
            $this->input->reveal(),
            $this->output->reveal(),
            $this->name,
            $this->directory,
            $this->executable,
            $sshConfig
        );
    }

    public function testExecute()
    {
        $connection = $this->createConnection(['password' => 'test->password', 'username' => 'test']);

        $this->ssh->login('test', 'test->password')->willReturn(true)->shouldBeCalled();
        $this->ssh->exec('cd ' . $this->directory . '; test-command', Argument::type('callable'))
            ->shouldBeCalled()
            ->willReturn('test-result');

        $result = $connection->execute(
            'test-command',
            function () {
            }
        );

        $this->assertEquals('test-result', $result);
    }

    public function testExecuteNanbando()
    {
        $connection = $this->createConnection(['password' => 'test->password', 'username' => 'test']);

        $this->ssh->login('test', 'test->password')->willReturn(true)->shouldBeCalled();
        $this->ssh->exec(
            'cd ' . $this->directory . '; ' . $this->executable . ' information 2017-01-01 --latest  -x 1',
            Argument::type('callable')
        )->shouldBeCalled()->willReturn('test-result');

        $result = $connection->executeNanbando(
            'information',
            ['2017-01-01', '--latest' => '', '-x' => 1],
            function () {
            }
        );

        $this->assertEquals('test-result', $result);
    }

    public function testGet()
    {
        $connection = $this->createConnection(['password' => 'test->password', 'username' => 'test']);

        $this->ssh->login('test', 'test->password')->willReturn(true)->shouldBeCalled();
        $this->scp->get('/var/local/2017-01-01.zip', '/var/server/2017-01-01.zip')->shouldBeCalled()->willReturn(true);

        $this->assertTrue($connection->get('/var/local/2017-01-01.zip', '/var/server/2017-01-01.zip'));
    }
}
