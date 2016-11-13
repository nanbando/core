<?php

namespace Nanbando\Core\EventListener;

use Nanbando\Core\BackupStatus;
use Nanbando\Core\Events\RestoreEvent;
use Nanbando\Core\Plugin\PluginRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Listens on restore event.
 */
class RestoreListener
{
    /**
     * @var PluginRegistry
     */
    private $pluginRegistry;

    /**
     * @param PluginRegistry $pluginRegistry
     */
    public function __construct(PluginRegistry $pluginRegistry)
    {
        $this->pluginRegistry = $pluginRegistry;
    }

    /**
     * Executes restore for given event.
     *
     * @param RestoreEvent $event
     */
    public function onRestore(RestoreEvent $event)
    {
        $plugin = $this->pluginRegistry->getPlugin($event->getOption('plugin'));

        $optionsResolver = new OptionsResolver();
        $plugin->configureOptionsResolver($optionsResolver);
        $parameter = $optionsResolver->resolve($event->getOption('parameter'));

        try {
            $plugin->restore($event->getSource(), $event->getDestination(), $event->getDatabase(), $parameter);
            $event->setStatus(BackupStatus::STATE_SUCCESS);
        } catch (\Exception $exception) {
            $event->setStatus(BackupStatus::STATE_FAILED);
            $event->setException($exception);
        }
    }
}
