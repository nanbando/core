<?php

namespace Nanbando\Core\EventListener;

use Nanbando\Core\BackupStatus;
use Nanbando\Core\Environment\EnvironmentInterface;
use Nanbando\Core\Events\BackupEvent;
use Nanbando\Core\Events\PreRestoreEvent;
use Nanbando\Core\Events\RestoreEvent;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Listens on differnet events and handles environment.
 */
class EnvironmentListener
{
    /**
     * @var EnvironmentInterface
     */
    private $environment;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param EnvironmentInterface $environment
     * @param OutputInterface $output
     */
    public function __construct(EnvironmentInterface $environment, OutputInterface $output)
    {
        $this->environment = $environment;
        $this->output = $output;
    }

    /**
     * Asks environment (e.g. user over console) if he wants to continue failed backup.
     *
     * @param BackupEvent $event
     */
    public function onBackupFinished(BackupEvent $event)
    {
        if ($event->getStatus() === BackupStatus::STATE_FAILED
            && !$this->environment->continueFailedBackup($event->getException())
        ) {
            $event->cancel();
        }
    }

    /**
     * Asks environment (e.g. user over console) if he wants to restore partially backup.
     *
     * @param PreRestoreEvent $event
     */
    public function onPreRestore(PreRestoreEvent $event)
    {
        $systemDatabase = $event->getSystemDatabase();
        if ($systemDatabase->getWithDefault('state', BackupStatus::STATE_SUCCESS) === BackupStatus::STATE_PARTIALLY
            && !$this->environment->restorePartiallyBackup()
        ) {
            $event->cancel();
        }
    }

    /**
     * Bypasses failed backup in restore process.
     *
     * @param RestoreEvent $event
     */
    public function onRestoreStarted(RestoreEvent $event)
    {
        $database = $event->getDatabase();
        if ($database->getWithDefault('state', BackupStatus::STATE_SUCCESS) === BackupStatus::STATE_FAILED) {
            $this->output->writeln('  <info>Bypassed</info>');

            $event->stopPropagation();
        }
    }
}
