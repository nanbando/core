<?php

namespace Nanbando\Core\Plugin;

class PluginNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $available;

    /**
     * @param string $name
     * @param string[] $available
     */
    public function __construct($name, array $available)
    {
        parent::__construct(sprintf('Plugin "%s" not found in [%s]', $name, implode(',', $available)));

        $this->name = $name;
        $this->available = $available;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getAvailable()
    {
        return $this->available;
    }
}
