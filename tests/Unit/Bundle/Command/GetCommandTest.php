<?php

namespace Unit\Bundle\Command;

use Nanbando\Bundle\Command\GetCommand;
use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Server\ServerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests for class "GetCommand".
 */
class GetCommandTest extends TestCase
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
    protected function setUp(): void
    {
        $this->serverRegistry = $this->prophesize(ServerRegistry::class);
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    private function getCommandTester(): CommandTester
    {
        $this->container->get('nanbando.server_registry')->willReturn($this->serverRegistry->reveal());

        $command = new GetCommand();
        $command->setContainer($this->container->reveal());

        $application = new Application();
        $application->add($command);

        $command = $application->find('get');

        return new CommandTester($command);
    }

    public function testExecute(): void
    {
        $command = $this->prophesize(CommandInterface::class);
        $command->interact(Argument::type(InputInterface::class), Argument::type(OutputInterface::class))
            ->shouldBeCalled();
        $command->execute(['name' => '2017-01-01-12-00-00'])->shouldBeCalled();

        $this->serverRegistry->getCommand('local', 'get')->willReturn($command->reveal());

        $commandTester = $this->getCommandTester();
        $commandTester->execute(['source-server' => 'local', 'name' => '2017-01-01-12-00-00']);
    }
}
