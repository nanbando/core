<?php

namespace Nanbando\Tests\Unit\Core\Database;

use Nanbando\Core\Database\Database;

require_once __DIR__ . '/ReadonlyDatabaseTest.php';

class DatabaseTest extends ReadonlyDatabaseTest
{
    /**
     * @var Database
     */
    protected $database;

    protected function setUp(): void
    {
        $this->database = new Database($this->data);
    }

    public function testSet(): void
    {
        $this->database->set('version', '1.0');

        $this->assertEquals(['name' => 'nanbando', 'version' => '1.0'], $this->database->getAll());
    }

    public function testRemove(): void
    {
        $this->database->remove('name');

        $this->assertEmpty($this->database->getAll());
    }

    public function testRemoveNotExists(): void
    {
        $this->database->remove('version');

        $this->assertEquals(['name' => 'nanbando'], $this->database->getAll());
    }
}
