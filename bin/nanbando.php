<?php

set_time_limit(0);

define('NANBANDO_DIR', getenv('NANBANDO_DIR') ?: '.nanbando');

use Dflydev\EmbeddedComposer\Core\EmbeddedComposerBuilder;
use Nanbando\Application\Application;
use Nanbando\Application\Kernel;
use Symfony\Component\Console\Input\ArgvInput;
use Webmozart\PathUtil\Path;

$input = new ArgvInput();
if ($projectDir = $input->getParameterOption('--root-dir')) {
    if (false !== strpos($projectDir, '~') && function_exists('posix_getuid')) {
        $info = posix_getpwuid(posix_getuid());
        $projectDir = str_replace('~', $info['dir'], $projectDir);
    }
    if (!is_dir($projectDir)) {
        throw new \InvalidArgumentException(
            sprintf('Specified project directory %s does not exist', $projectDir)
        );
    }
    chdir($projectDir);
}

$embeddedComposerBuilder = new EmbeddedComposerBuilder($classLoader);
$embeddedComposer = $embeddedComposerBuilder
    ->setComposerFilename('nanbando.json')
    ->setVendorDirectory(NANBANDO_DIR)
    ->build();
$embeddedComposer->processAdditionalAutoloads();

$kernel = new Kernel('prod', false, Path::getHomeDirectory());
$kernel->boot();

$input = $kernel->getContainer()->get('input');
$output = $kernel->getContainer()->get('output');

$application = new Application($kernel, $embeddedComposer);
$application->run($input, $output);
