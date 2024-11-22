<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPreparedSets(codeQuality: true, codingStyle: true)
    ->withPhpSets()
    ->withAttributesSets(doctrine: true, gedmo: true)
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withRootFiles();