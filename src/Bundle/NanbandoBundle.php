<?php

namespace Nanbando\Bundle;

use Nanbando\Bundle\DependencyInjection\Compiler\RegisterKernelListenersPass;
use Nanbando\Core\Symfony\CollectorCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NanbandoBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CollectorCompilerPass('plugins', 'nanbando.plugin', 'alias'));
        $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
