<?php

namespace Nanbando\Bundle;

use Nanbando\Application\CompilerPass\CollectorCompilerPass;
use Nanbando\Application\CompilerPass\CommandCompilerPass;
use Nanbando\Application\CompilerPass\SshServerCompilerPass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Entry-point of nanbando core-bundle.
 */
class NanbandoBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddConsoleCommandPass());
        $container->addCompilerPass(new SshServerCompilerPass());
        $container->addCompilerPass(new CommandCompilerPass());
        $container->addCompilerPass(new CollectorCompilerPass('plugins', 'nanbando.plugin', 'alias'));
        $container->addCompilerPass(new RegisterListenersPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
