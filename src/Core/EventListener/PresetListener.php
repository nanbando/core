<?php

namespace Nanbando\Core\EventListener;

use Nanbando\Core\Events\PreBackupEvent;
use Nanbando\Core\Events\PreRestoreEvent;
use Nanbando\Core\Presets\PresetStore;

/**
 * Listens on backup and restore events to prepend configuration.
 */
class PresetListener
{
    /**
     * @var string
     */
    private $application;

    /**
     * @var string
     */
    private $version;

    /**
     * @var PresetStore
     */
    private $presetStore;
    /**
     * @var array
     */
    private $options;

    /**
     * @param string $application
     * @param string $version
     * @param array $options
     * @param PresetStore $presetStore
     */
    public function __construct($application, $version, array $options, PresetStore $presetStore)
    {
        $this->application = $application;
        $this->version = $version;
        $this->options = $options;
        $this->presetStore = $presetStore;
    }

    /**
     * Extend configuration with preset.
     *
     * @param PreBackupEvent $event
     */
    public function onPreBackup(PreBackupEvent $event)
    {
        $event->setBackup($this->extend($event->getBackup()));
    }

    /**
     * Extend configuration with preset.
     *
     * @param PreRestoreEvent $event
     */
    public function onPreRestore(PreRestoreEvent $event)
    {
        $event->setBackup($this->extend($event->getBackup()));
    }

    /**
     * Extend backup with preset.
     *
     * @param array $backup
     *
     * @return array
     */
    private function extend(array $backup)
    {
        $preset = $this->presetStore->getPreset($this->application, $this->version, $this->options);

        return array_merge($preset, $backup);
    }
}
