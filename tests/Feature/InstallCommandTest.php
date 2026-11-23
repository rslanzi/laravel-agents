<?php

declare(strict_types=1);

use Rslanzi\LaravelAgents\Package;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Contracts\Process\ProcessResult;
use Rslanzi\LaravelAgents\LaravelAgentsServiceProvider;

/**
 * @return array<string, string> relative path => content hash
 */
function snapshotProject(string $root): array
{
    return collect((new Filesystem)->allFiles($root, true))
        ->mapWithKeys(fn (SplFileInfo $file): array => [$file->getRelativePathname() => md5_file($file->getPathname())])
        ->sortKeys()
        ->all();
}

beforeEach(function (): void {
    // agents:install shells out to composer and artisan: never run them for real in the suite.
    Process::fake();

    file_put_contents($this->projectFile('composer.json'), json_encode([
        'name' => 'acme/app',
        'autoload-dev' => new stdClass,
        'scripts' => ['test' => ['@php artisan test']],
    ], JSON_PRETTY_PRINT));
});

it('publishes the tooling stubs into the project', function (): void {
    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    foreach (['pint.json', 'rector.php', 'phpstan.neon', 'tests/Pest.php', 'tests/Unit/ArchitectureTest.php', '.claude/settings.json', '.github/workflows/ci.yml', '.github/pull_request_template.md', '.ai/guidelines/20-project-contexts.md', 'docs/adr/README.md', 'docs/architecture.md', 'CHANGELOG.md'] as $file) {
        expect($this->projectFile($file))->toBeFile();
    }
});

it('never overwrites existing files without --force', function (): void {
    file_put_contents($this->projectFile('pint.json'), '{"preset": "psr12"}');

    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    expect(file_get_contents($this->projectFile('pint.json')))->toBe('{"preset": "psr12"}');
});

it('overwrites existing files with --force', function (): void {
    file_put_contents($this->projectFile('pint.json'), '{"preset": "psr12"}');

    $this->artisan('agents:install', ['--no-tools' => true, '--force' => true])->assertSuccessful();

    expect(file_get_contents($this->projectFile('pint.json')))->toContain('"preset": "laravel"');
});

it('adds missing composer scripts and keeps existing ones', function (): void {
    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    $composer = json_decode((string) file_get_contents($this->projectFile('composer.json')), true);

    expect($composer['scripts']['test'])->toBe(['@php artisan test'])
        ->and($composer['scripts'])->toHaveKeys(['fix', 'lint', 'lint:check', 'types:check', 'test:coverage', 'test:mutate']);
});

it('keeps empty JSON objects in composer.json intact', function (): void {
    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    expect(file_get_contents($this->projectFile('composer.json')))->toContain('"autoload-dev": {}');
});

it('disables agent attribution in the published Claude Code settings', function (): void {
    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    $settings = json_decode((string) file_get_contents($this->projectFile('.claude/settings.json')), true);

    expect($settings['attribution'])->toBe(['commit' => '', 'pr' => '', 'sessionUrl' => false]);
});

it('enables the guidelines in an existing boost.json', function (): void {
    file_put_contents($this->projectFile('boost.json'), json_encode(['guidelines' => true, 'packages' => ['filament/filament']]));

    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    $boost = json_decode((string) file_get_contents($this->projectFile('boost.json')), true);

    expect($boost['packages'])->toBe(['filament/filament', 'rslanzi/laravel-agents'])
        ->and($boost['guidelines'])->toBeTrue();
});

it('does not create boost.json when Boost is not configured', function (): void {
    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    expect($this->projectFile('boost.json'))->not->toBeFile();
});

it('fails with a clear message when composer.json is missing', function (): void {
    unlink($this->projectFile('composer.json'));

    $this->artisan('agents:install', ['--no-tools' => true])
        ->expectsOutputToContain('No composer.json found')
        ->assertFailed();
});

it('fails with a clear message when composer.json is invalid', function (): void {
    file_put_contents($this->projectFile('composer.json'), '{"name": ');

    $this->artisan('agents:install', ['--no-tools' => true])
        ->expectsOutputToContain('composer.json is not valid JSON')
        ->assertFailed();
});

it('keeps the formatting of the rest of composer.json', function (): void {
    $original = <<<'JSON'
        {
            "name": "acme/app",
            "keywords": ["laravel", "framework"],
            "scripts": {
                "test": ["@php artisan test"]
            }
        }

        JSON;
    file_put_contents($this->projectFile('composer.json'), $original);

    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    $written = (string) file_get_contents($this->projectFile('composer.json'));

    expect($written)
        ->toStartWith("{\n    \"name\": \"acme/app\",\n    \"keywords\": [\"laravel\", \"framework\"],\n    \"scripts\": {\n        \"test\": [\"@php artisan test\"],\n        \"lint\": [\n            \"@php vendor/bin/rector process --ansi\",")
        ->and(json_decode($written, true)['scripts'])->toHaveKeys(['test', 'lint', 'check', 'fix']);
});

it('publishes only the requested groups', function (): void {
    $this->artisan('agents:install', ['--no-tools' => true, '--only' => ['ci']])->assertSuccessful();

    expect($this->projectFile('.github/workflows/ci.yml'))->toBeFile()
        ->and($this->projectFile('pint.json'))->not->toBeFile()
        ->and($this->projectFile('tests/Pest.php'))->not->toBeFile();
});

it('rejects an unknown group', function (): void {
    $this->artisan('agents:install', ['--no-tools' => true, '--only' => ['nope']])
        ->expectsOutputToContain('Unknown group(s): nope')
        ->assertFailed();

    expect($this->projectFile('pint.json'))->not->toBeFile();
});

it('changes nothing in dry-run mode', function (): void {
    $composer = (string) file_get_contents($this->projectFile('composer.json'));
    file_put_contents($this->projectFile('boost.json'), '{"guidelines": true}');
    Process::fake();

    $this->artisan('agents:install', ['--dry-run' => true])->assertSuccessful();

    Process::assertNothingRan();
    expect(file_get_contents($this->projectFile('composer.json')))->toBe($composer)
        ->and(file_get_contents($this->projectFile('boost.json')))->toBe('{"guidelines": true}')
        ->and($this->projectFile('pint.json'))->not->toBeFile();
});

it('swaps PHPUnit for Pest and installs the missing tools', function (): void {
    file_put_contents($this->projectFile('composer.json'), json_encode([
        'name' => 'acme/app',
        'require-dev' => ['phpunit/phpunit' => '^12.0', 'laravel/pint' => '^1.0'],
    ], JSON_PRETTY_PRINT));
    Process::fake();

    $this->artisan('agents:install')->assertSuccessful();

    Process::assertRan(fn (PendingProcess $process): bool => in_array('remove', (array) $process->command, true)
        && in_array('phpunit/phpunit', (array) $process->command, true));
    Process::assertRan(fn (PendingProcess $process): bool => in_array('require', (array) $process->command, true)
        && in_array('pestphp/pest', (array) $process->command, true)
        && ! in_array('laravel/pint', (array) $process->command, true));
});

it('restores composer.json and composer.lock when composer fails', function (): void {
    file_put_contents($this->projectFile('composer.json'), '{"require-dev": {"phpunit/phpunit": "^12.0"}}');
    file_put_contents($this->projectFile('composer.lock'), '{"lock": true}');

    Process::fake(function (PendingProcess $process): ProcessResult {
        // Simulate composer editing composer.json before the require step fails.
        file_put_contents($this->projectFile('composer.json'), '{"require-dev": {}}');

        return in_array('require', (array) $process->command, true)
            ? Process::result(errorOutput: 'network down', exitCode: 1)
            : Process::result();
    });

    $this->artisan('agents:install')
        ->expectsOutputToContain('composer.json and composer.lock were restored')
        ->assertFailed();

    expect(file_get_contents($this->projectFile('composer.json')))->toBe('{"require-dev": {"phpunit/phpunit": "^12.0"}}')
        ->and(file_get_contents($this->projectFile('composer.lock')))->toBe('{"lock": true}')
        ->and($this->projectFile('pint.json'))->not->toBeFile();
});

it('adds the packages key to a boost.json that has none', function (): void {
    file_put_contents($this->projectFile('boost.json'), '{"guidelines": true}');

    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    expect(json_decode((string) file_get_contents($this->projectFile('boost.json')), true)['packages'])
        ->toBe(['rslanzi/laravel-agents']);
});

it('is idempotent', function (): void {
    file_put_contents($this->projectFile('boost.json'), '{"packages": []}');
    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();
    $snapshot = snapshotProject($this->projectPath);

    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    expect(snapshotProject($this->projectPath))->toBe($snapshot);
});

it('assigns every stub to exactly one group', function (): void {
    $stubs = collect((new Filesystem)->allFiles(Package::stubsPath(), true))
        ->map(fn (SplFileInfo $file): string => str_replace('\\', '/', $file->getRelativePathname()))
        ->sort()
        ->values()
        ->all();

    $grouped = collect(Package::STUB_GROUPS)->flatten()->sort()->values()->all();

    expect($grouped)->toBe($stubs);
});

it('registers each stub group for vendor:publish', function (): void {
    $paths = ServiceProvider::pathsToPublish(LaravelAgentsServiceProvider::class, 'agents-quality');

    expect(array_map(fn (string $stub): string => mb_substr($stub, mb_strlen(Package::stubsPath()) + 1), array_keys($paths)))
        ->toBe(Package::STUB_GROUPS['quality'])
        ->and(array_map(basename(...), array_values($paths)))->toBe(Package::STUB_GROUPS['quality'])
        ->and(ServiceProvider::pathsToPublish(LaravelAgentsServiceProvider::class, 'agents'))->toHaveCount(count(Package::STUB_GROUPS, COUNT_RECURSIVE) - count(Package::STUB_GROUPS));
});

it('publishes a pint.json that is safe on PHP 8.3', function (): void {
    $pint = json_decode((string) file_get_contents(Package::stubsPath() . '/pint.json'), true);

    expect($pint['rules']['mb_str_functions'])->toBeFalse();
});

it('runs boost:install, enables the package, runs boost:update and composer fix', function (): void {
    Process::fake(function (PendingProcess $process): ProcessResult {
        if (in_array('boost:install', (array) $process->command, true)) {
            file_put_contents($this->projectFile('boost.json'), '{"agents": ["claude_code"]}');
        }

        return Process::result();
    });

    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    foreach (['boost:install', 'boost:update', 'fix'] as $argument) {
        Process::assertRan(fn (PendingProcess $process): bool => in_array($argument, (array) $process->command, true));
    }

    expect(json_decode((string) file_get_contents($this->projectFile('boost.json')), true)['packages'])->toBe(['rslanzi/laravel-agents']);
});

it('skips boost:install when Boost is already configured', function (): void {
    file_put_contents($this->projectFile('boost.json'), '{"packages": []}');

    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    Process::assertNotRan(fn (PendingProcess $process): bool => in_array('boost:install', (array) $process->command, true));
    Process::assertRan(fn (PendingProcess $process): bool => in_array('boost:update', (array) $process->command, true));
});

it('skips boost:install in non-interactive mode', function (): void {
    $this->artisan('agents:install', ['--no-tools' => true, '--no-interaction' => true])
        ->expectsOutputToContain('boost:install asks questions')
        ->assertSuccessful();

    Process::assertNotRan(fn (PendingProcess $process): bool => in_array('boost:install', (array) $process->command, true)
        || in_array('boost:update', (array) $process->command, true));
});

it('installs Boost with the tools unless --no-boost is given', function (bool $noBoost): void {
    $this->artisan('agents:install', ['--no-boost' => $noBoost, '--no-fix' => true])->assertSuccessful();

    $requiresBoost = fn (PendingProcess $process): bool => in_array('require', (array) $process->command, true)
        && in_array('laravel/boost', (array) $process->command, true);

    $noBoost ? Process::assertNotRan($requiresBoost) : Process::assertRan($requiresBoost);
})->with([false, true]);

it('runs nothing after the tooling with --no-boost and --no-fix', function (): void {
    $this->artisan('agents:install', ['--no-tools' => true, '--no-boost' => true, '--no-fix' => true])->assertSuccessful();

    Process::assertNothingRan();
});

it('fails when composer fix fails', function (): void {
    file_put_contents($this->projectFile('boost.json'), '{"packages": []}');
    Process::fake(fn (PendingProcess $process): ProcessResult => in_array('fix', (array) $process->command, true)
        ? Process::result(exitCode: 1)
        : Process::result());

    $this->artisan('agents:install', ['--no-tools' => true])
        ->expectsOutputToContain('composer fix reported problems')
        ->assertFailed();
});
