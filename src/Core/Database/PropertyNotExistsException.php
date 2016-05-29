<?php

namespace Nanbando\Core\Database;

class PropertyNotExistsException extends \Exception
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct(sprintf('Property "%s" not exists in database', $name));

        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
