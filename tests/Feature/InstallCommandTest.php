<?php

declare(strict_types=1);

beforeEach(function (): void {
    file_put_contents($this->projectFile('composer.json'), json_encode([
        'name' => 'acme/app',
        'autoload-dev' => new stdClass,
        'scripts' => ['test' => ['@php artisan test']],
    ], JSON_PRETTY_PRINT));
});

it('publishes the tooling stubs into the project', function (): void {
    $this->artisan('agents:install', ['--no-tools' => true])->assertSuccessful();

    foreach (['pint.json', 'rector.php', 'phpstan.neon', 'tests/Pest.php', 'tests/Unit/ArchitectureTest.php', '.claude/settings.json', '.github/workflows/ci.yml', '.github/pull_request_template.md', '.ai/guidelines/20-project-contexts.md', 'docs/adr/README.md'] as $file) {
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
