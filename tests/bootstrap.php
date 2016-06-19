<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Webmozart\PathUtil\Path;

$file = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies to run test suite.');
}

$loader = require $file;
AnnotationRegistry::registerLoader([$loader, 'loadClass']);

define('RESOURCE_DIR', Path::join([__DIR__, 'Resources']));
define('DATAFIXTURES_DIR', Path::join([__DIR__, 'DataFixtures']));

return $loader;
