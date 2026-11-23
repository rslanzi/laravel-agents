<?php

declare(strict_types=1);

/**
 * Every English source string used with __(), trans(), trans_choice() or @lang must exist in every
 * lang/{locale}.json. Keys that look like PHP translation files (`validation.required`, `auth.failed`)
 * are skipped. Passes trivially while the project has no lang/*.json file.
 */
test('every translation key exists in every JSON locale', function (): void {
    $root = dirname(__DIR__, 2);
    $locales = glob($root . '/lang/*.json') ?: [];

    if ($locales === []) {
        expect($locales)->toBe([]);

        return;
    }

    $keys = [];
    $sources = new AppendIterator;

    foreach (['app', 'resources/views'] as $directory) {
        if (is_dir($root . '/' . $directory)) {
            $sources->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS)));
        }
    }

    foreach ($sources as $file) {
        if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
            continue;
        }

        preg_match_all(
            '/(?:__|trans|trans_choice|@lang)\(\s*([\'"])((?:(?!\1)[^\\\\]|\\\\.)+)\1/',
            (string) file_get_contents($file->getPathname()),
            $matches,
        );

        foreach ($matches[2] as $key) {
            $keys[stripslashes($key)] = true;
        }
    }

    $keys = array_filter(
        array_keys($keys),
        fn (string $key): bool => preg_match('/^[\w-]+(\.[\w-]+)+$/', $key) !== 1,
    );

    $missing = [];

    foreach ($locales as $locale) {
        $translations = json_decode((string) file_get_contents($locale), true, flags: JSON_THROW_ON_ERROR);

        foreach ($keys as $key) {
            if (! is_array($translations) || ! array_key_exists($key, $translations)) {
                $missing[] = basename($locale) . ': ' . $key;
            }
        }
    }

    sort($missing);

    expect($missing)->toBe([]);
});
