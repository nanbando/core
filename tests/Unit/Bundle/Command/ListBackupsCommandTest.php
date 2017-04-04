<?php

namespace Unit\Bundle\Command;

use Nanbando\Bundle\Command\ListBackupsCommand;
use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Server\ServerRegistry;
use Prophecy\Argument;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests for class "ListBackupsCommand".
 */
class ListBackupsCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServerRegistry
     */
    private $serverRegistry;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->serverRegistry = $this->prophesize(ServerRegistry::class);
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    private function getCommandTester()
    {
        $this->container->get('nanbando.server_registry')->willReturn($this->serverRegistry->reveal());

        $command = new ListBackupsCommand();
        $command->setContainer($this->container->reveal());

        $application = new Application();
        $application->add($command);

        $command = $application->find('list:backups');

        return new CommandTester($command);
    }

    public function testExecute()
    {
        $command = $this->prophesize(CommandInterface::class);
        $command->interact(Argument::type(InputInterface::class), Argument::type(OutputInterface::class))
            ->shouldBeCalled();
        $command->execute(['remote' => true])->shouldBeCalled();

        $this->serverRegistry->getCommand('local', 'list:backups')->willReturn($command->reveal());

        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--server' => 'local', '--remote' => true]);
    }
}
