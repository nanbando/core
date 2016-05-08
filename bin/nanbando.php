<?php

set_time_limit(0);

use Dflydev\EmbeddedComposer\Core\EmbeddedComposerBuilder;
use Nanbando\Application\Application;
use Nanbando\Application\Kernel;
use Symfony\Component\Console\Input\ArgvInput;

$input = new ArgvInput();

if ($projectDir = $input->getParameterOption('--root-dir')) {
    if (false !== strpos($projectDir, '~') && function_exists('posix_getuid')) {
        $info = posix_getpwuid(posix_getuid());
        $projectDir = str_replace('~', $info['dir'], $projectDir);
    }
    if (!is_dir($projectDir)) {
        throw new \InvalidArgumentException(
            sprintf("Specified project directory %s does not exist", $projectDir)
        );
    }
    chdir($projectDir);
}

$factoryClass = constant('PULI_FACTORY_CLASS');
$puliFactory = new $factoryClass();
$discovery = $puliFactory->createDiscovery($puliFactory->createRepository());

$kernel = new Kernel('prod', false, getenv('HOME'), $discovery);
$kernel->boot();

$embeddedComposerBuilder = new EmbeddedComposerBuilder($classLoader);
$embeddedComposer = $embeddedComposerBuilder
    ->setComposerFilename('nanbando.json')
    ->setVendorDirectory('.nanbando')
    ->build();

$embeddedComposer->processAdditionalAutoloads();
$application = new Application($kernel, $embeddedComposer);
$application->run($input);
