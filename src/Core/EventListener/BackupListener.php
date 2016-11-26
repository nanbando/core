<?php

namespace Nanbando\Core\EventListener;

use Nanbando\Core\BackupStatus;
use Nanbando\Core\Events\BackupEvent;
use Nanbando\Core\Plugin\PluginRegistry;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Listens on backup event.
 */
class BackupListener
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
     * Write information to database.
     *
     * @param BackupEvent $event
     */
    public function onBackupStarted(BackupEvent $event)
    {
        $database = $event->getDatabase();
        $database->set('started', (new \DateTime())->format(\DateTime::RFC3339));
    }

    /**
     * Executes backup for given event.
     *
     * @param BackupEvent $event
     */
    public function onBackup(BackupEvent $event)
    {
        $plugin = $this->pluginRegistry->getPlugin($event->getOption('plugin'));

        $optionsResolver = new OptionsResolver();
        $plugin->configureOptionsResolver($optionsResolver);
        $parameter = $optionsResolver->resolve($event->getOption('parameter'));

        try {
            $plugin->backup($event->getSource(), $event->getDestination(), $event->getDatabase(), $parameter);
            $event->setStatus(BackupStatus::STATE_SUCCESS);
        } catch (\Exception $exception) {
            $event->setStatus(BackupStatus::STATE_FAILED);
            $event->setException($exception);
        }
    }

    /**
     * Write information from event to database..
     *
     * @param BackupEvent $event
     */
    public function onBackupFinished(BackupEvent $event)
    {
        $database = $event->getDatabase();
        $database->set('finished', (new \DateTime())->format(\DateTime::RFC3339));
        $database->set('state', $event->getStatus());

        if (BackupStatus::STATE_FAILED === $event->getStatus()) {
            $database->set('exception', $this->serializeException($event->getException()));
        }
    }

    /**
     * Serializes exception.
     *
     * @param \Exception $exception
     *
     * @return array
     */
    private function serializeException(\Exception $exception)
    {
        return [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTrace(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'previous' => null !== $exception->getPrevious() ? $this->serializeException($exception->getPrevious()) : null,
        ];
    }
}
