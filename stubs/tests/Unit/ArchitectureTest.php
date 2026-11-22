<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Namespaces of one layer across all modules, e.g. ['App\Domain\Booking\Actions', ...].
 * Built from the folders that exist. Each namespace gets its own rule: Pest evaluates a list of
 * targets unreliably with not->toUse(), and an empty list fails.
 *
 * @return list<string>
 */
function domainLayer(string ...$layers): array
{
    $namespaces = [];

    foreach ($layers as $layer) {
        foreach (glob(dirname(__DIR__, 2) . '/app/Domain/*/' . $layer, GLOB_ONLYDIR) ?: [] as $directory) {
            $relative = mb_substr($directory, mb_strlen(dirname(__DIR__, 2) . '/app/'));
            $namespaces[] = 'App\\' . str_replace('/', '\\', $relative);
        }
    }

    return $namespaces;
}

arch()->preset()->php();
arch()->preset()->security();

arch('env() is only read in config files')
    ->expect('App')
    ->not->toUse('env');

foreach (domainLayer('Actions', 'Models', 'Services', 'Queries', 'Events', 'Listeners', 'Jobs', 'DTO') as $namespace) {
    arch("module core does not depend on delivery: {$namespace}")
        ->expect($namespace)
        ->not->toUse([
            Request::class,
            FormRequest::class,
            'Illuminate\Routing',
            'Filament',
            'Livewire',
            'App\Http',
            'App\Filament',
            'App\Livewire',
        ]);
}

test('module core never imports a module delivery namespace', function (): void {
    $violations = collect(glob(dirname(__DIR__, 2) . '/app/Domain/*/{Actions,Models,Services,Queries,Events,Listeners,Jobs,DTO}/*.php', GLOB_BRACE) ?: [])
        ->filter(fn (string $file): bool => preg_match('#^use App.Domain.\w+.(Http|Livewire).#m', (string) file_get_contents($file)) === 1)
        ->values()
        ->all();

    expect($violations)->toBe([]);
});

test('Filament resources, pages and widgets are grouped by context', function (): void {
    $contexts = array_map(basename(...), glob(dirname(__DIR__, 2) . '/app/Domain/*', GLOB_ONLYDIR) ?: []);
    $allowedAtRoot = ['Dashboard.php'];

    $violations = collect(['Resources', 'Pages', 'Widgets'])
        ->flatMap(fn (string $type): array => glob(dirname(__DIR__, 2) . "/app/Filament/{$type}/*") ?: [])
        ->reject(fn (string $path): bool => in_array(basename($path), $contexts, true) || in_array(basename($path), $allowedAtRoot, true))
        ->values()
        ->all();

    expect($violations)->toBe([]);
});

test('presentation and async code delegate Eloquent writes to Actions', function (): void {
    $root = dirname(__DIR__, 2) . '/app';
    $files = array_merge(
        glob($root . '/Domain/*/{Http,Livewire,Jobs,Listeners}/**/*.php', GLOB_BRACE) ?: [],
        glob($root . '/Domain/*/{Http,Livewire,Jobs,Listeners}/*.php', GLOB_BRACE) ?: [],
        is_dir($root . '/Filament') ? array_map(strval(...), iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/Filament', FilesystemIterator::SKIP_DOTS)))) : [],
        is_dir($root . '/Livewire') ? array_map(strval(...), iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/Livewire', FilesystemIterator::SKIP_DOTS)))) : [],
    );

    $violations = collect($files)
        ->filter(fn (string $file): bool => str_ends_with($file, '.php'))
        ->filter(fn (string $file): bool => preg_match('/(::query\(\)->create\(|->(?:save|update|delete|forceDelete|restore)\()/', (string) file_get_contents($file)) === 1)
        ->values()
        ->all();

    expect($violations)->toBe([]);
});

foreach (domainLayer('Http/Controllers') as $namespace) {
    arch("controllers are thin and final: {$namespace}")
        ->expect($namespace)
        ->toBeFinal()
        ->toHaveSuffix('Controller')
        ->not->toUse(DB::class);
}

foreach (domainLayer('Http/Requests') as $namespace) {
    arch("form requests live in the module Http layer: {$namespace}")
        ->expect($namespace)
        ->toExtend(FormRequest::class)
        ->toHaveSuffix('Request');
}

foreach (domainLayer('Actions') as $namespace) {
    arch("actions are final and readonly: {$namespace}")
        ->expect($namespace)
        ->toBeFinal()
        ->toBeReadonly()
        ->toHaveMethod('handle');
}

foreach (domainLayer('DTO') as $namespace) {
    arch("DTOs are readonly: {$namespace}")
        ->expect($namespace)
        ->toBeReadonly();
}

foreach (domainLayer('Enums') as $namespace) {
    arch("enums are enums: {$namespace}")
        ->expect($namespace)
        ->toBeEnums();
}

foreach (domainLayer('Exceptions') as $namespace) {
    arch("exceptions extend the base exception: {$namespace}")
        ->expect($namespace)
        ->toExtend(Exception::class);
}

test('only User lives in App\Models', function (): void {
    expect(glob(dirname(__DIR__, 2) . '/app/Models/*.php'))->toBe([dirname(__DIR__, 2) . '/app/Models/User.php']);
});

// Add rules for real invariants and cross-module boundaries as the project grows, e.g.:
//
// arch('Scheduling models are only mutated inside Scheduling')
//     ->expect('App\Domain\Scheduling\Models')
//     ->toOnlyBeUsedIn('App\Domain\Scheduling');
//
// arch('Billing never calls Booking actions, it reacts to its events')
//     ->expect('App\Domain\Billing')
//     ->not->toUse('App\Domain\Booking\Actions');
