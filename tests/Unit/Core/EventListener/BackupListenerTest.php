<?php

namespace Unit\Core\EventListener;

use League\Flysystem\Filesystem;
use Nanbando\Core\BackupStatus;
use Nanbando\Core\Database\Database;
use Nanbando\Core\EventListener\BackupListener;
use Nanbando\Core\Events\BackupEvent;
use Nanbando\Core\Plugin\PluginInterface;
use Nanbando\Core\Plugin\PluginRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BackupListenerTest extends TestCase
{
    /**
     * @var PluginRegistry
     */
    private $pluginRegistry;

    /**
     * @var BackupListener
     */
    private $listener;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var BackupEvent
     */
    private $event;

    protected function setUp(): void
    {
        $this->pluginRegistry = $this->prophesize(PluginRegistry::class);
        $this->listener = new BackupListener($this->pluginRegistry->reveal());

        $this->database = $this->prophesize(Database::class);

        $this->event = $this->prophesize(BackupEvent::class);
        $this->event->getDatabase()->willReturn($this->database->reveal());
    }

    public function testOnBackupStarted(): void
    {
        $this->database->set(
            'started',
            Argument::that(
                function ($value) {
                    $date = \DateTime::createFromFormat(\DateTime::RFC3339, $value);

                    return new \DateTime() >= $date;
                }
            )
        )->shouldBeCalled();

        $this->listener->onBackupStarted($this->event->reveal());
    }

    public function testOnBackup(): void
    {
        $this->event->getOption('parameter')->willReturn(['directory' => '/test']);
        $this->event->getOption('plugin')->willReturn('test');

        $source = $this->prophesize(Filesystem::class);
        $destination = $this->prophesize(Filesystem::class);
        $database = $this->prophesize(Database::class);

        $this->event->getSource()->willReturn($source->reveal());
        $this->event->getDestination()->willReturn($destination->reveal());
        $this->event->getDatabase()->willReturn($database->reveal());

        $this->event->setStatus(BackupStatus::STATE_SUCCESS)->shouldBeCalled();

        $plugin = $this->prophesize(PluginInterface::class);
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))->will(
            function ($arguments) {
                /** @var OptionsResolver $optionResolver */
                $optionsResolver = $arguments[0];

                $optionsResolver->setRequired('directory');
            }
        );
        $plugin->backup($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => '/test'])
            ->shouldBeCalled();

        $this->pluginRegistry->getPlugin('test')->willReturn($plugin->reveal());

        $this->listener->onBackup($this->event->reveal());
    }

    public function testOnBackupException(): void
    {
        $this->event->getOption('parameter')->willReturn(['directory' => '/test']);
        $this->event->getOption('plugin')->willReturn('test');

        $source = $this->prophesize(Filesystem::class);
        $destination = $this->prophesize(Filesystem::class);
        $database = $this->prophesize(Database::class);

        $this->event->getSource()->willReturn($source->reveal());
        $this->event->getDestination()->willReturn($destination->reveal());
        $this->event->getDatabase()->willReturn($database->reveal());

        $exception = $this->prophesize(\Exception::class);

        $this->event->setStatus(BackupStatus::STATE_FAILED)->shouldBeCalled();
        $this->event->setException($exception->reveal())->shouldBeCalled();

        $plugin = $this->prophesize(PluginInterface::class);
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))->will(
            function ($arguments) {
                /** @var OptionsResolver $optionResolver */
                $optionsResolver = $arguments[0];

                $optionsResolver->setRequired('directory');
            }
        );

        $plugin->backup($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => '/test'])
            ->shouldBeCalled()
            ->willThrow($exception->reveal());

        $this->pluginRegistry->getPlugin('test')->willReturn($plugin->reveal());

        $this->listener->onBackup($this->event->reveal());
    }

    public function testOnBackupFinished(): void
    {
        $this->event->getStatus()->willReturn(BackupStatus::STATE_SUCCESS);

        $this->database->set(
            'finished',
            Argument::that(
                function ($value) {
                    $date = \DateTime::createFromFormat(\DateTime::RFC3339, $value);

                    return new \DateTime() >= $date;
                }
            )
        )->shouldBeCalled();
        $this->database->set('state', BackupStatus::STATE_SUCCESS)->shouldBeCalled();

        $this->listener->onBackupFinished($this->event->reveal());
    }

    public function testOnBackupFinishedFailed(): void
    {
        $this->event->getStatus()->willReturn(BackupStatus::STATE_FAILED);
        $this->event->getException()->willReturn(new \Exception());

        $this->database->set(
            'finished',
            Argument::that(
                function ($value) {
                    $date = \DateTime::createFromFormat(\DateTime::RFC3339, $value);

                    return new \DateTime() >= $date;
                }
            )
        )->shouldBeCalled();
        $this->database->set('state', BackupStatus::STATE_FAILED)->shouldBeCalled();
        $this->database->set('exception', Argument::any())->shouldBeCalled();

        $this->listener->onBackupFinished($this->event->reveal());
    }
}
