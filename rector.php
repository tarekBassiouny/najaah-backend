<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/database',
        __DIR__.'/routes',
    ])
    ->withSkip([
        __DIR__.'/database/migrations',
        __DIR__.'/database/factories',
        __DIR__.'/database/seeders',
        __DIR__.'/storage',
        __DIR__.'/vendor',
        __DIR__.'/tests',
    ])
    ->withPreparedSets(
        codingStyle: true,
        typeDeclarations: true,
        deadCode: true,
        privatization: true
    );
