<?php

namespace Nanbando\Core\Database;

class Database extends ReadonlyDatabase
{
    /**
     * Set property name with given value.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Removes value for given property name.
     *
     * @param string $name
     */
    public function remove($name)
    {
        if (!$this->exists($name)) {
            return;
        }

        unset($this->data[$name]);
    }
}
