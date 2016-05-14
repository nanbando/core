<?php

namespace Nanbando\Core\Plugin;

class PluginRegistry
{
    /**
     * @var PluginInterface[]
     */
    private $plugins;

    /**
     * @param PluginInterface[] $plugins
     */
    public function __construct(array $plugins)
    {
        $this->plugins = $plugins;
    }

    /**
     * @return PluginInterface
     */
    public function getPlugin($name)
    {
        return $this->plugins[$name];
    }
}
