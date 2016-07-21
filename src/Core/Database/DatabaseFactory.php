<?php

namespace Nanbando\Core\Database;

/**
 * Factory for databases.
 */
class DatabaseFactory
{
    /**
     * Create a new database.
     *
     * @param array $data
     *
     * @return Database
     */
    public function create(array $data = [])
    {
        return new Database($data);
    }

    /**
     * Create a new database.
     *
     * @param array $data
     *
     * @return ReadonlyDatabase
     */
    public function createReadonly(array $data = [])
    {
        return new ReadonlyDatabase($data);
    }
}
