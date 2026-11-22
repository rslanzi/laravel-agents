<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodingStyle\Rector\Use_\SeparateMultiUseImportsRector;
use RectorLaravel\Rector\StaticCall\EloquentMagicMethodToQueryBuilderRector;
use RectorLaravel\Rector\MethodCall\ValidationRuleArrayStringValueToArrayRector;

return RectorConfig::configure()
    ->withCache(
        cacheDirectory: sys_get_temp_dir() . '/rector',
        cacheClass: FileCacheStorage::class,
    )
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/bootstrap/cache',
        // Splitting `use HasFactory, Notifiable;` drops the `@use HasFactory<...>` generic PHPDoc.
        SeparateMultiUseImportsRector::class,
    ])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        carbon: true,
        rectorPreset: true,
    )
    ->withComposerBased(laravel: true)
    ->withRules([
        ValidationRuleArrayStringValueToArrayRector::class,
        EloquentMagicMethodToQueryBuilderRector::class,
    ])
    ->withImportNames(removeUnusedImports: true);
