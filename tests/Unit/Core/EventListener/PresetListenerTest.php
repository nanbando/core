<?php

namespace Unit\Core\EventListener;

use Nanbando\Core\EventListener\PresetListener;
use Nanbando\Core\Events\PreBackupEvent;
use Nanbando\Core\Events\PreRestoreEvent;
use Nanbando\Core\Presets\PresetStore;
use PHPUnit\Framework\TestCase;

/**
 * Tests for preset-listener.
 */
class PresetListenerTest extends TestCase
{
    /**
     * @var string
     */
    private $application = 'sulu';

    /**
     * @var string
     */
    private $version = '1.3';

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var PresetStore
     */
    private $presetStore;

    /**
     * @var PresetListener
     */
    private $listener;

    protected function setUp()
    {
        $this->presetStore = $this->prophesize(PresetStore::class);

        $this->listener = new PresetListener($this->application, $this->version, $this->options, $this->presetStore->reveal());
    }

    public function testOnPreBackup()
    {
        $event = $this->prophesize(PreBackupEvent::class);
        $event->getBackup()->willReturn(['test1' => 1, 'test2' => 2]);
        $event->setBackup(['test1' => 1, 'test2' => 2, 'test3' => 3])->shouldBeCalled();

        $this->presetStore->getPreset($this->application, $this->version, $this->options)
            ->willReturn(['test2' => 3, 'test3' => 3]);

        $this->listener->onPreBackup($event->reveal());
    }

    public function testOnPreRestore()
    {
        $event = $this->prophesize(PreRestoreEvent::class);
        $event->getBackup()->willReturn(['test1' => 1, 'test2' => 2]);
        $event->setBackup(['test1' => 1, 'test2' => 2, 'test3' => 3])->shouldBeCalled();

        $this->presetStore->getPreset($this->application, $this->version, $this->options)
            ->willReturn(['test2' => 3, 'test3' => 3]);

        $this->listener->onPreRestore($event->reveal());
    }
}
