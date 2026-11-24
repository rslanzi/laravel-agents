<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Module core layers: everything in a module except its delivery (Http, Routes, Livewire)
 * and its wiring (Providers), which may reference both rings.
 */
const CORE_LAYERS = ['Actions', 'Contracts', 'Database', 'DTO', 'Enums', 'Events', 'Exceptions', 'Jobs', 'Listeners', 'Models', 'Policies', 'Queries', 'Services', 'Settings', 'StateMachines'];

/**
 * Folders that have a fixed place in a module: DTO/, Database/Factories, Database/Migrations.
 */
const MISPLACED_MODULE_FOLDERS = ['DTOs' => 'DTO', 'Factories' => 'Database/Factories', 'Migrations' => 'Database/Migrations', 'Seeders' => 'Database/Seeders'];

function projectPath(string $relative = ''): string
{
    return dirname(__DIR__, 2) . ($relative === '' ? '' : '/' . $relative);
}

/**
 * Namespaces of one layer across all modules, e.g. ['App\Domain\Ordering\Actions', ...].
 * Built from the folders that exist. Each namespace gets its own rule: Pest evaluates a list of
 * targets unreliably with not->toUse(), and an empty list fails.
 *
 * @return list<string>
 */
function domainLayer(string ...$layers): array
{
    $namespaces = [];

    foreach ($layers as $layer) {
        foreach (glob(projectPath('app/Domain/*/' . $layer), GLOB_ONLYDIR) ?: [] as $directory) {
            $relative = mb_substr($directory, mb_strlen(projectPath('app/')));
            $namespaces[] = 'App\\' . str_replace('/', '\\', $relative);
        }
    }

    return $namespaces;
}

/**
 * Every PHP file under the given directories, at any depth (PHP's glob() has no recursive `**`).
 *
 * @return list<string>
 */
function phpFiles(string ...$directories): array
{
    $files = [];

    foreach ($directories as $directory) {
        if (! is_dir($directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    sort($files);

    return $files;
}

/**
 * The given layer folders of every module, e.g. app/Domain/Ordering/Http.
 *
 * @return list<string>
 */
function moduleDirectories(string ...$layers): array
{
    $directories = [];

    foreach ($layers as $layer) {
        array_push($directories, ...(glob(projectPath('app/Domain/*/' . $layer), GLOB_ONLYDIR) ?: []));
    }

    return $directories;
}

arch()->preset()->php();
arch()->preset()->security();

arch('env() is only read in config files')
    ->expect('App')
    ->not->toUse('env');

$delivery = [
    Request::class,
    FormRequest::class,
    'Illuminate\Routing',
    'Filament',
    'Livewire',
    'App\Http',
    'App\Filament',
    'App\Livewire',
];

foreach (domainLayer(...CORE_LAYERS) as $namespace) {
    arch("module core does not depend on delivery: {$namespace}")
        ->expect($namespace)
        ->not->toUse($delivery);
}

if (is_dir(projectPath('app/Domain/Shared'))) {
    arch('the shared kernel does not depend on delivery')
        ->expect('App\Domain\Shared')
        ->not->toUse($delivery);
}

test('module core never imports a module delivery namespace', function (): void {
    $violations = collect(phpFiles(...moduleDirectories(...CORE_LAYERS)))
        ->filter(fn (string $file): bool => preg_match('#^use App\\\\Domain\\\\\w+\\\\(Http|Livewire)\\\\#m', (string) file_get_contents($file)) === 1)
        ->values()
        ->all();

    expect($violations)->toBe([]);
});

test('modules follow the standard layout', function (): void {
    $violations = [];

    foreach (glob(projectPath('app/Domain/*'), GLOB_ONLYDIR) ?: [] as $module) {
        foreach (MISPLACED_MODULE_FOLDERS as $folder => $expected) {
            if (is_dir("{$module}/{$folder}")) {
                $violations[] = basename($module) . "/{$folder} must be " . basename($module) . "/{$expected}";
            }
        }
    }

    expect($violations)->toBe([]);
});

test('Filament resources, pages and widgets are grouped by context', function (): void {
    $contexts = array_map(basename(...), glob(projectPath('app/Domain/*'), GLOB_ONLYDIR) ?: []);
    $allowedAtRoot = ['Dashboard.php'];

    $violations = collect(['Resources', 'Pages', 'Widgets'])
        ->flatMap(fn (string $type): array => glob(projectPath("app/Filament/{$type}/*")) ?: [])
        ->reject(fn (string $path): bool => in_array(basename($path), $contexts, true) || in_array(basename($path), $allowedAtRoot, true))
        ->values()
        ->all();

    expect($violations)->toBe([]);
});

test('presentation and async code delegate Eloquent writes to Actions', function (): void {
    // Eloquent write calls only: save()/delete() without arguments, update()/create() with an attribute
    // array, and the *OrCreate helpers. Storage::delete($path) or Cache calls do not match.
    $eloquentWrite = '/(->(?:save|delete|forceDelete|restore|push)\(\)|->update\(\s*\[|::(?:query\(\)->)?create\(\s*\[|->(?:updateOrCreate|firstOrCreate|createMany|upsert|insert)\()/';

    $files = phpFiles(
        ...moduleDirectories('Http', 'Livewire', 'Jobs', 'Listeners'),
        ...[projectPath('app/Filament'), projectPath('app/Livewire')],
    );

    $violations = collect($files)
        ->filter(fn (string $file): bool => preg_match($eloquentWrite, (string) file_get_contents($file)) === 1)
        ->values()
        ->all();

    expect($violations)->toBe([]);
});

foreach (domainLayer('Http/Controllers') as $namespace) {
    arch("controllers are thin, final and extend nothing: {$namespace}")
        ->expect($namespace)
        ->toBeFinal()
        ->toExtendNothing()
        ->toHaveSuffix('Controller')
        ->not->toUse(DB::class);
}

foreach (domainLayer('Http/Requests') as $namespace) {
    arch("form requests only validate: {$namespace}")
        ->expect($namespace)
        ->toExtend(FormRequest::class)
        ->toHaveSuffix('Request')
        ->not->toHaveMethod('authorize');
}

foreach (domainLayer('Actions') as $namespace) {
    arch("actions are final and readonly: {$namespace}")
        ->expect($namespace)
        ->toBeFinal()
        ->toBeReadonly()
        ->toHaveMethod('execute');
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

foreach (domainLayer('Events') as $namespace) {
    arch("events are final: {$namespace}")
        ->expect($namespace)
        ->toBeFinal();
}

foreach (domainLayer('Exceptions') as $namespace) {
    arch("exceptions extend the base exception: {$namespace}")
        ->expect($namespace)
        ->toExtend(Exception::class);
}

foreach (domainLayer('Contracts') as $namespace) {
    arch("contracts are interfaces: {$namespace}")
        ->expect($namespace)
        ->toBeInterfaces()
        ->toHaveSuffix('Contract');
}

test('every contract method documents its behavior', function (): void {
    $violations = [];

    foreach (phpFiles(...moduleDirectories('Contracts')) as $file) {
        $source = (string) file_get_contents($file);
        // PHPDoc, then optional attributes, then the method.
        preg_match_all('#(/\*\*(?:(?!\*/).)*\*/)?\s*(?:\#\[[^\]]*\]\s*)*public function (\w+)#s', $source, $methods, PREG_SET_ORDER);

        foreach ($methods as $method) {
            if (! str_contains($method[1], '@post')) {
                $violations[] = basename($file, '.php') . '::' . $method[2] . '() has no @post in its PHPDoc';
            }
        }
    }

    expect($violations)->toBe([]);
});

test('every contract has a contract test', function (): void {
    $missing = [];

    foreach (glob(projectPath('app/Domain/*/Contracts/*.php')) ?: [] as $file) {
        $context = basename(dirname($file, 2));
        $expected = "tests/Feature/Contracts/{$context}/" . basename($file, '.php') . 'Test.php';

        if (! is_file(projectPath($expected))) {
            $missing[] = $expected;
        }
    }

    expect($missing)->toBe([]);
});

test('App\Models holds at most the User model', function (): void {
    $models = array_map(basename(...), glob(projectPath('app/Models/*.php')) ?: []);

    expect(array_diff($models, ['User.php']))->toBe([]);
});

// Add rules for real invariants and cross-module boundaries as the project grows, e.g.:
//
// arch('Inventory models are only mutated inside Inventory')
//     ->expect('App\Domain\Inventory\Models')
//     ->toOnlyBeUsedIn('App\Domain\Inventory');
//
// arch('Billing never calls Ordering actions, it reacts to its events')
//     ->expect('App\Domain\Billing')
//     ->not->toUse('App\Domain\Ordering\Actions');
