<?php

namespace Nanbando\Unit\Core\Database;

use Nanbando\Core\Database\Database;
use Nanbando\Core\Database\DatabaseFactory;
use Nanbando\Core\Database\ReadonlyDatabase;

class DatabaseFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $factory = new DatabaseFactory();

        $this->assertInstanceOf(Database::class, $factory->create());
    }

    public function testCreateWithData()
    {
        $factory = new DatabaseFactory();

        $this->assertEquals(['test' => 1], $factory->create(['test' => 1])->getAll());
    }

    public function testCreateReadonly()
    {
        $factory = new DatabaseFactory();

        $this->assertInstanceOf(ReadonlyDatabase::class, $factory->createReadonly());
    }

    public function testCreateReadonlyWithData()
    {
        $factory = new DatabaseFactory();

        $this->assertEquals(['test' => 1], $factory->createReadonly(['test' => 1])->getAll());
    }
}
