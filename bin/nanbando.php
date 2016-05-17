<?php

set_time_limit(0);

use Dflydev\EmbeddedComposer\Core\EmbeddedComposerBuilder;
use Nanbando\Application\Application;
use Nanbando\Application\Kernel;
use Puli\Discovery\JsonDiscovery;
use Webmozart\PathUtil\Path;

$discovery = new JsonDiscovery(getcwd(). '/.puli/bindings.json');

$embeddedComposerBuilder = new EmbeddedComposerBuilder($classLoader);
$embeddedComposer = $embeddedComposerBuilder
    ->setComposerFilename('nanbando.json')
    ->setVendorDirectory('.nanbando')
    ->build();
$embeddedComposer->processAdditionalAutoloads();

$kernel = new Kernel('prod', true, Path::getHomeDirectory(), $discovery);
$kernel->boot();

$input = $kernel->getContainer()->get('input');
$output = $kernel->getContainer()->get('output');

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

$application = new Application($kernel, $embeddedComposer);
$application->run($input, $output);
