<?php

namespace Nanbando\Core\EventListener;

use Nanbando\Core\BackupStatus;
use Nanbando\Core\Events\BackupEvent;
use Nanbando\Core\Events\PostBackupEvent;
use Nanbando\Core\Events\PreBackupEvent;
use Nanbando\Core\Events\PreRestoreEvent;
use Nanbando\Core\Events\RestoreEvent;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Listens on differnet events and handles output.
 */
class OutputListener
{
    /**
     * Contains messages foreach state.
     *
     * @var string[]
     */
    const STATE_MESSAGES = [
        BackupStatus::STATE_SUCCESS => '<info>successfully</info>',
        BackupStatus::STATE_FAILED => '<error>failed</error>',
        BackupStatus::STATE_PARTIALLY => '<comment>partially</comment>',
    ];

    /**
     * @var string
     */
    private $name;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param string $name
     * @param OutputInterface $output
     */
    public function __construct($name, OutputInterface $output)
    {
        $this->name = $name;
        $this->output = $output;
    }

    /**
     * Print info for pre-backup to output.
     *
     * @param PreBackupEvent $event
     */
    public function onPreBackup(PreBackupEvent $event)
    {
        $systemDatabase = $event->getSystemDatabase();

        $this->output->writeln(sprintf('Backup "%s" started:', $this->name));
        $this->output->writeln(sprintf(' * label:    %s', $systemDatabase->get('label')));
        $this->output->writeln(sprintf(' * message:  %s', $systemDatabase->get('message')));
        $this->output->writeln(sprintf(' * started:  %s', $systemDatabase->get('started')));
        $this->output->writeln(sprintf(' * nanbando: %s', $systemDatabase->get('nanbando_version')));

        if ($systemDatabase->exists('process')) {
            $this->output->writeln(sprintf(' * process: %s', $systemDatabase->get('process')));
        }

        $this->output->writeln('');
    }

    /**
     * Print info for backup-started to output.
     *
     * @param BackupEvent $event
     */
    public function onBackupStarted(BackupEvent $event)
    {
        $this->output->writeln('- ' . $event->getName() . ' (' . $event->getOption('plugin') . '):');
    }

    /**
     * Print info for backup-finished to output.
     */
    public function onBackupFinished()
    {
        $this->output->writeln('');
    }

    /**
     * Print info for post-backup to output.
     *
     * @param PostBackupEvent $event
     */
    public function onPostBackup(PostBackupEvent $event)
    {
        $this->output->writeln(
            sprintf('Backup "%s" finished %s', $event->getName(), self::STATE_MESSAGES[$event->getStatus()])
        );
        $this->output->writeln('Cleanup temporary files ...');
    }

    /**
     * Print info for pre-restore to output.
     *
     * @param PreRestoreEvent $event
     */
    public function onPreRestore(PreRestoreEvent $event)
    {
        $systemDatabase = $event->getSystemDatabase();

        $this->output->writeln(sprintf('Backup "%s" started will be restored:', $this->name));
        $this->output->writeln(sprintf(' * label:   %s', $systemDatabase->get('label')));
        $this->output->writeln(sprintf(' * message: %s', $systemDatabase->get('message')));
        $this->output->writeln(sprintf(' * started: %s', $systemDatabase->get('started')));

        if ($systemDatabase->exists('process')) {
            $this->output->writeln(sprintf(' * process: %s', $systemDatabase->get('process')));
        }

        $this->output->writeln('');
    }

    /**
     * Print info for restore-started to output.
     *
     * @param RestoreEvent $event
     */
    public function onRestoreStarted(RestoreEvent $event)
    {
        $this->output->writeln('- ' . $event->getName() . ' (' . $event->getOption('plugin') . '):');
    }

    /**
     * Print info for restore-finished to output.
     */
    public function onRestoreFinished()
    {
        $this->output->writeln('');
        $this->output->writeln('');
    }

    /**
     * Print info for restore finished to output.
     */
    public function onPostRestore()
    {
        $this->output->writeln('Restore finished');
    }
}
