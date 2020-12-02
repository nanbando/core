<?php

namespace Unit\Core\Server;

use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Server\MissingCommandException;
use Nanbando\Core\Server\MissingServerException;
use Nanbando\Core\Server\ServerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Tests for class "ServerRegistry".
 */
class ServerRegistryTest extends TestCase
{
    public function testGetCommand()
    {
        $command = $this->prophesize(CommandInterface::class);

        $registry = new ServerRegistry(['test::command' => $command->reveal()], ['test']);
        $this->assertEquals($command->reveal(), $registry->getCommand('test', 'command'));
    }

    public function testGetCommandMissingServer()
    {
        $this->expectException(MissingServerException::class);

        $command = $this->prophesize(CommandInterface::class);

        $registry = new ServerRegistry(['test::command' => $command->reveal()], ['test']);
        $registry->getCommand('test-1', 'command');
    }

    public function testGetCommandMissingCommand()
    {
        $this->expectException(MissingCommandException::class);

        $command = $this->prophesize(CommandInterface::class);

        $registry = new ServerRegistry(['test::command' => $command->reveal()], ['test']);
        $registry->getCommand('test', 'command-1');
    }
}
