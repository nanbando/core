<?php

namespace Unit\Core\EventListener;

use League\Flysystem\Filesystem;
use Nanbando\Core\BackupStatus;
use Nanbando\Core\Database\Database;
use Nanbando\Core\EventListener\RestoreListener;
use Nanbando\Core\Events\RestoreEvent;
use Nanbando\Core\Plugin\PluginInterface;
use Nanbando\Core\Plugin\PluginRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RestoreListenerTest extends TestCase
{
    /**
     * @var PluginRegistry
     */
    private $pluginRegistry;

    /**
     * @var RestoreListener
     */
    private $listener;

    /**
     * @var RestoreEvent
     */
    private $event;

    protected function setUp(): void
    {
        $this->pluginRegistry = $this->prophesize(PluginRegistry::class);
        $this->listener = new RestoreListener($this->pluginRegistry->reveal());

        $this->event = $this->prophesize(RestoreEvent::class);
    }

    public function testOnRestore(): void
    {
        $this->event->getOption('parameter')->willReturn(['directory' => '/test']);
        $this->event->getOption('plugin')->willReturn('test');
        $this->event->setStatus(BackupStatus::STATE_SUCCESS)->shouldBeCalled();

        $source = $this->prophesize(Filesystem::class);
        $destination = $this->prophesize(Filesystem::class);
        $database = $this->prophesize(Database::class);

        $this->event->getSource()->willReturn($source->reveal());
        $this->event->getDestination()->willReturn($destination->reveal());
        $this->event->getDatabase()->willReturn($database->reveal());

        $plugin = $this->prophesize(PluginInterface::class);
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))->will(
            function ($arguments) {
                /** @var OptionsResolver $optionResolver */
                $optionsResolver = $arguments[0];

                $optionsResolver->setRequired('directory');
            }
        );
        $plugin->restore($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => '/test'])
            ->shouldBeCalled();

        $this->pluginRegistry->getPlugin('test')->willReturn($plugin->reveal());

        $this->listener->onRestore($this->event->reveal());
    }

    public function testOnRestoreFail(): void
    {
        $exception = $this->prophesize(\Exception::class);

        $this->event->getOption('parameter')->willReturn(['directory' => '/test']);
        $this->event->getOption('plugin')->willReturn('test');
        $this->event->setStatus(BackupStatus::STATE_FAILED)->shouldBeCalled();
        $this->event->setException($exception->reveal())->shouldBeCalled();

        $source = $this->prophesize(Filesystem::class);
        $destination = $this->prophesize(Filesystem::class);
        $database = $this->prophesize(Database::class);

        $this->event->getSource()->willReturn($source->reveal());
        $this->event->getDestination()->willReturn($destination->reveal());
        $this->event->getDatabase()->willReturn($database->reveal());

        $plugin = $this->prophesize(PluginInterface::class);
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))->will(
            function ($arguments) {
                /** @var OptionsResolver $optionResolver */
                $optionsResolver = $arguments[0];

                $optionsResolver->setRequired('directory');
            }
        );
        $plugin->restore($source->reveal(), $destination->reveal(), $database->reveal(), ['directory' => '/test'])
            ->shouldBeCalled()->willThrow($exception->reveal());

        $this->pluginRegistry->getPlugin('test')->willReturn($plugin->reveal());

        $this->listener->onRestore($this->event->reveal());
    }
}
