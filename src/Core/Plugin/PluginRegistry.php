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
     * Returns plugin by name.
     *
     * @param string $name
     *
     * @return PluginInterface
     *
     * @throws PluginNotFoundException
     */
    public function getPlugin($name)
    {
        if (!$this->has($name)) {
            throw new PluginNotFoundException($name, array_keys($this->plugins));
        }

        return $this->plugins[$name];
    }

    /**
     * Returns true if plugin exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->plugins);
    }
}
