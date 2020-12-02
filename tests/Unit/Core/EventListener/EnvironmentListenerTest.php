<?php

namespace Unit\Core\EventListener;

use Nanbando\Core\BackupStatus;
use Nanbando\Core\Database\ReadonlyDatabase;
use Nanbando\Core\Environment\EnvironmentInterface;
use Nanbando\Core\EventListener\EnvironmentListener;
use Nanbando\Core\Events\BackupEvent;
use Nanbando\Core\Events\PreRestoreEvent;
use Nanbando\Core\Events\RestoreEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

class EnvironmentListenerTest extends TestCase
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
     * @var EnvironmentListener
     */
    private $listener;

    public function setUp()
    {
        $this->environment = $this->prophesize(EnvironmentInterface::class);
        $this->output = $this->prophesize(OutputInterface::class);

        $this->listener = new EnvironmentListener($this->environment->reveal(), $this->output->reveal());
    }

    public function testOnBackupFinished()
    {
        $exception = $this->prophesize(\Exception::class);

        $event = $this->prophesize(BackupEvent::class);
        $event->getStatus()->willReturn(BackupStatus::STATE_FAILED);
        $event->getException()->willReturn($exception->reveal());

        $this->environment->continueFailedBackup($exception->reveal())->shouldBeCalled()->willReturn(false);

        $event->cancel()->shouldBeCalled();

        $this->listener->onBackupFinished($event->reveal());
    }

    public function testOnBackupFinishedSuccess()
    {
        $event = $this->prophesize(BackupEvent::class);
        $event->getStatus()->willReturn(BackupStatus::STATE_SUCCESS);

        $this->environment->continueFailedBackup(Argument::any())->shouldNotBeCalled();

        $event->cancel()->shouldNotBeCalled();

        $this->listener->onBackupFinished($event->reveal());
    }

    public function testOnBackupFinishedContinue()
    {
        $exception = $this->prophesize(\Exception::class);

        $event = $this->prophesize(BackupEvent::class);
        $event->getStatus()->willReturn(BackupStatus::STATE_FAILED);
        $event->getException()->willReturn($exception->reveal());

        $this->environment->continueFailedBackup($exception->reveal())->shouldBeCalled()->willReturn(true);

        $event->cancel()->shouldNotBeCalled();

        $this->listener->onBackupFinished($event->reveal());
    }

    public function testOnPreRestore()
    {
        $systemDatabase = $this->prophesize(ReadonlyDatabase::class);

        $event = $this->prophesize(PreRestoreEvent::class);
        $event->getSystemDatabase()->willReturn($systemDatabase->reveal());

        $systemDatabase->getWithDefault('state', BackupStatus::STATE_SUCCESS)->willReturn(BackupStatus::STATE_PARTIALLY);
        $this->environment->restorePartiallyBackup()->willReturn(false);

        $event->cancel()->shouldBeCalled();

        $this->listener->onPreRestore($event->reveal());
    }

    public function testOnPreRestoreSuccess()
    {
        $systemDatabase = $this->prophesize(ReadonlyDatabase::class);

        $event = $this->prophesize(PreRestoreEvent::class);
        $event->getSystemDatabase()->willReturn($systemDatabase->reveal());

        $systemDatabase->getWithDefault('state', BackupStatus::STATE_SUCCESS)->willReturn(BackupStatus::STATE_SUCCESS);
        $this->environment->restorePartiallyBackup()->shouldNotBeCalled();

        $event->cancel()->shouldNotBeCalled();

        $this->listener->onPreRestore($event->reveal());
    }

    public function testOnPreRestoreContinue()
    {
        $systemDatabase = $this->prophesize(ReadonlyDatabase::class);

        $event = $this->prophesize(PreRestoreEvent::class);
        $event->getSystemDatabase()->willReturn($systemDatabase->reveal());

        $systemDatabase->getWithDefault('state', BackupStatus::STATE_SUCCESS)->willReturn(BackupStatus::STATE_PARTIALLY);
        $this->environment->restorePartiallyBackup()->shouldBeCalled()->willReturn(true);

        $event->cancel()->shouldNotBeCalled();

        $this->listener->onPreRestore($event->reveal());
    }

    public function testOnRestoreStated()
    {
        $database = $this->prophesize(ReadonlyDatabase::class);

        $event = $this->prophesize(RestoreEvent::class);
        $event->getDatabase()->willReturn($database->reveal());

        $database->getWithDefault('state', BackupStatus::STATE_SUCCESS)->willReturn(BackupStatus::STATE_FAILED);

        $event->stopPropagation()->shouldBeCalled();

        $this->listener->onRestoreStarted($event->reveal());
    }

    public function testOnRestoreStatedSuccess()
    {
        $database = $this->prophesize(ReadonlyDatabase::class);

        $event = $this->prophesize(RestoreEvent::class);
        $event->getDatabase()->willReturn($database->reveal());

        $database->getWithDefault('state', BackupStatus::STATE_SUCCESS)->willReturn(BackupStatus::STATE_SUCCESS);

        $event->stopPropagation()->shouldNotBeCalled();

        $this->listener->onRestoreStarted($event->reveal());
    }
}
