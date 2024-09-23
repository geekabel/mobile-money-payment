<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;

return RectorConfig::configure()
    ->withRootFiles()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/rector.php',
    ])->withSkip([
        DisallowedEmptyRuleFixerRector::class,
    ])->withSets([
        // global
        SetList::PHP_83,
        SetList::CODE_QUALITY,
        SetList::PRIVATIZATION,
        SetList::INSTANCEOF,
        SetList::STRICT_BOOLEANS,
    ]);