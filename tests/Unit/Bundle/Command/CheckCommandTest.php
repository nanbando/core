<?php

namespace Nanbando\Unit\Bundle\Command;

use Nanbando\Bundle\Command\CheckCommand;
use Nanbando\Core\Plugin\PluginInterface;
use Nanbando\Core\Plugin\PluginRegistry;
use Prophecy\Argument;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var PluginRegistry
     */
    private $plugins;

    protected function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->plugins = $this->prophesize(PluginRegistry::class);
    }

    private function getCommandTester($remote = false, $backup = [])
    {
        $this->container->getParameter('nanbando.storage.local_directory')->willReturn('/User/test/nanbando');
        $this->container->getParameter('nanbando.backup')->willReturn($backup);
        $this->container->has('filesystem.remote')->willReturn($remote);

        $this->container->get('plugins')->willReturn($this->plugins->reveal());

        $command = new CheckCommand();
        $command->setContainer($this->container->reveal());

        $application = new Application();
        $application->add($command);

        $command = $application->find('check');

        return new CommandTester($command);
    }

    public function testExecute()
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $this->assertContains('Local directory: /User/test/nanbando', $commandTester->getDisplay());
        $this->assertContains('No remote storage configuration found.', $commandTester->getDisplay());
        $this->assertContains('No backup configuration found.', $commandTester->getDisplay());
    }

    public function testExecuteWithRemote()
    {
        $commandTester = $this->getCommandTester(true);
        $commandTester->execute([]);

        $this->assertContains('Local directory: /User/test/nanbando', $commandTester->getDisplay());
        $this->assertContains('Remote Storage: YES', $commandTester->getDisplay());
    }

    public function testExecutePluginNotFound()
    {
        $this->plugins->has('my-plugin')->willReturn(false);

        $commandTester = $this->getCommandTester(true, ['test' => ['plugin' => 'my-plugin']]);
        $commandTester->execute([]);

        $this->assertContains('Plugin "my-plugin" not found', $commandTester->getDisplay());
    }

    public function testExecutePluginNotFoundMultiple()
    {
        $plugin = $this->prophesize(PluginInterface::class);
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))->shouldBeCalled();

        $this->plugins->has('my-plugin-1')->willReturn(true);
        $this->plugins->getPlugin('my-plugin-1')->willReturn($plugin->reveal());
        $this->plugins->has('my-plugin-2')->willReturn(false);

        $commandTester = $this->getCommandTester(
            true,
            [
                'test-1' => ['plugin' => 'my-plugin-1', 'parameter' => []],
                'test-2' => ['plugin' => 'my-plugin-2'],
            ]
        );
        $commandTester->execute([]);


        $this->assertRegExp('/test-1[-\s]*OK/', $commandTester->getDisplay());
        $this->assertRegExp('/test-2[-\s]*\[WARNING\] Plugin "my-plugin-2" not found/', $commandTester->getDisplay());
    }

    public function testExecuteParameterNotValid()
    {
        $plugin = $this->prophesize(PluginInterface::class);
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))
            ->will(
                function ($args) {
                    $args[0]->setRequired(['chmod', 'directory']);
                }
            );

        $this->plugins->has('my-plugin')->willReturn(true);
        $this->plugins->getPlugin('my-plugin')->willReturn($plugin->reveal());

        $commandTester = $this->getCommandTester(
            true,
            ['test' => ['plugin' => 'my-plugin', 'parameter' => ['directory' => '/test']]]
        );
        $commandTester->execute([]);

        $this->assertContains('Parameter not valid', $commandTester->getDisplay());
    }

    public function testExecuteParameterNotValidMultiple()
    {
        $plugin = $this->prophesize(PluginInterface::class);
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))
            ->will(
                function ($args) {
                    $args[0]->setRequired(['chmod', 'directory']);
                }
            );

        $this->plugins->has('my-plugin-1')->willReturn(true);
        $this->plugins->has('my-plugin-2')->willReturn(true);
        $this->plugins->getPlugin('my-plugin-1')->willReturn($plugin->reveal());
        $this->plugins->getPlugin('my-plugin-2')->willReturn($plugin->reveal());

        $commandTester = $this->getCommandTester(
            true,
            [
                'test-1' => ['plugin' => 'my-plugin-1', 'parameter' => ['directory' => '/test']],
                'test-2' => ['plugin' => 'my-plugin-2', 'parameter' => ['directory' => '/test', 'chmod' => 0777]],
            ]
        );
        $commandTester->execute([]);

        $this->assertRegExp('/test-1[-\s]*\[WARNING\] Parameter not valid/', $commandTester->getDisplay());
        $this->assertRegExp('/test-2[-\s]*OK/', $commandTester->getDisplay());
    }

    public function testExecuteOK()
    {
        $plugin = $this->prophesize(PluginInterface::class);
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))
            ->will(
                function ($args) {
                    $args[0]->setRequired(['chmod', 'directory']);
                }
            );

        $this->plugins->has('my-plugin')->willReturn(true);
        $this->plugins->getPlugin('my-plugin')->willReturn($plugin->reveal());

        $commandTester = $this->getCommandTester(
            true,
            ['test' => ['plugin' => 'my-plugin', 'parameter' => ['directory' => '/test', 'chmod' => 0777]]]
        );
        $commandTester->execute([]);

        $this->assertContains('OK', $commandTester->getDisplay());
    }

    public function testExecuteOKMultiple()
    {
        $plugin = $this->prophesize(PluginInterface::class);
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))
            ->will(
                function ($args) {
                    $args[0]->setRequired(['chmod', 'directory']);
                }
            );

        $this->plugins->has('my-plugin')->willReturn(true);
        $this->plugins->getPlugin('my-plugin')->willReturn($plugin->reveal());

        $commandTester = $this->getCommandTester(
            true,
            [
                'test-1' => ['plugin' => 'my-plugin', 'parameter' => ['directory' => '/test', 'chmod' => 0777]],
                'test-2' => ['plugin' => 'my-plugin', 'parameter' => ['directory' => '/test', 'chmod' => 0777]],
            ]
        );
        $commandTester->execute([]);

        $this->assertRegExp('/test-1[-\s]*OK/', $commandTester->getDisplay());
        $this->assertRegExp('/test-2[-\s]*OK/', $commandTester->getDisplay());
    }
}
