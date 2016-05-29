<?php

namespace Nanbando\Core\Database;

class ReadonlyDatabase
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Returns value for name or default if not exists.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getWithDefault($name, $default = null)
    {
        if (!$this->exists($name)) {
            return $default;
        }

        return $this->data[$name];
    }

    /**
     * Returns value for name.
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws PropertyNotExistsException
     */
    public function get($name)
    {
        if (!$this->exists($name)) {
            throw new PropertyNotExistsException($name);
        }

        return $this->data[$name];
    }

    /**
     * Returns true if property exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function exists($name)
    {
        return array_key_exists($name, $this->data);
    }
}
