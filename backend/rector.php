<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(
        php82: true,
    )
    ->withImportNames(
        importShortClasses: false,
        removeUnusedImports: true,
    )
    ->withSkip([
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
    ]);