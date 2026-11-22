<?php

declare(strict_types=1);

namespace Rslanzi\LaravelAgents\Console;

use stdClass;
use Illuminate\Console\Command;
use Rslanzi\LaravelAgents\Package;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Finder\SplFileInfo;

final class InstallCommand extends Command
{
    /**
     * Dev tools the guidelines rely on.
     *
     * @var list<string>
     */
    public const array TOOLS = [
        'pestphp/pest',
        'pestphp/pest-plugin-laravel',
        'laravel/pint',
        'larastan/larastan',
        'rector/rector',
        'driftingly/rector-laravel',
    ];

    public const string PACKAGE = 'rslanzi/laravel-agents';

    /** @var string */
    protected $signature = 'agents:install
        {--force : Overwrite config files and composer scripts that already exist}
        {--no-tools : Do not install the dev tools with composer}';

    /** @var string */
    protected $description = 'Publish the quality tooling (Pint, Rector, PHPStan, Pest, CI, agent settings) without overwriting existing files';

    public function handle(Filesystem $filesystem): int
    {
        if (! $this->option('no-tools') && ! $this->installTools()) {
            return self::FAILURE;
        }

        $this->publishStubs($filesystem);
        $this->mergeComposerScripts($filesystem);
        $boostConfigured = $this->registerInBoost($filesystem);

        $this->newLine();
        $this->components->info('Done. Next steps:');
        $this->components->bulletList([
            $boostConfigured
                ? 'Run <comment>php artisan boost:update</comment> to load the guidelines into AGENTS.md and CLAUDE.md.'
                : 'Run <comment>php artisan boost:install</comment> and select <comment>rslanzi/laravel-agents</comment> to load the guidelines.',
            'Without Boost, run <comment>php artisan agents:markdown</comment> to write a standalone AGENTS.md.',
            'Describe your modules in <comment>.ai/guidelines/20-project-contexts.md</comment>.',
            'Run <comment>composer fix</comment> and commit the result.',
        ]);

        return self::SUCCESS;
    }

    private function installTools(): bool
    {
        $composer = $this->readComposerJson();
        $installed = array_keys(array_merge($composer['require'] ?? [], $composer['require-dev'] ?? []));
        $missing = array_values(array_diff(self::TOOLS, $installed));

        if ($missing === []) {
            $this->components->info('Dev tools already installed.');

            return true;
        }

        if (in_array('pestphp/pest', $missing, true) && in_array('phpunit/phpunit', $installed, true)) {
            $this->components->info('Removing phpunit/phpunit (Pest brings its own).');

            if (! $this->composer(['remove', '--dev', '--no-update', 'phpunit/phpunit'])) {
                return false;
            }
        }

        $this->components->info('Installing ' . implode(', ', $missing) . '...');

        if (! $this->composer(['require', '--dev', '--with-all-dependencies', ...$missing])) {
            $this->components->error('Composer failed. Install the tools by hand: composer require --dev ' . implode(' ', $missing));

            return false;
        }

        return true;
    }

    /**
     * @param  list<string>  $arguments
     */
    private function composer(array $arguments): bool
    {
        $result = Process::path(base_path())
            ->timeout(900)
            ->env(['COMPOSER_NO_INTERACTION' => '1'])
            ->run(['composer', ...$arguments, '--no-interaction']);

        if ($result->failed()) {
            $this->line($result->errorOutput());
        }

        return $result->successful();
    }

    private function publishStubs(Filesystem $filesystem): void
    {
        $stubs = collect($filesystem->allFiles(Package::stubsPath(), true))
            ->sortBy(fn (SplFileInfo $file): string => $file->getRelativePathname());

        foreach ($stubs as $stub) {
            $relative = str_replace('\\', '/', $stub->getRelativePathname());
            $target = base_path($relative);

            if ($filesystem->exists($target) && ! $this->option('force')) {
                $this->components->twoColumnDetail($relative, '<fg=yellow>skipped (exists)</>');

                continue;
            }

            $filesystem->ensureDirectoryExists(dirname($target));
            $filesystem->copy($stub->getPathname(), $target);
            $this->components->twoColumnDetail($relative, '<fg=green>published</>');
        }
    }

    private function mergeComposerScripts(Filesystem $filesystem): void
    {
        // Decoded as objects, so empty JSON objects ({}) survive the rewrite unchanged.
        $composer = json_decode((string) $filesystem->get(base_path('composer.json')), flags: JSON_THROW_ON_ERROR);
        $kit = json_decode((string) $filesystem->get(Package::path('resources/composer-scripts.json')), flags: JSON_THROW_ON_ERROR);

        $composer->scripts ??= new stdClass;
        $added = [];

        foreach (get_object_vars($kit->scripts) as $name => $commands) {
            if (property_exists($composer->scripts, $name) && ! $this->option('force')) {
                $this->components->twoColumnDetail("composer {$name}", '<fg=yellow>skipped (exists)</>');

                continue;
            }

            $composer->scripts->{$name} = $commands;
            $added[] = $name;
        }

        if ($added === []) {
            return;
        }

        $filesystem->put(
            base_path('composer.json'),
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n",
        );

        $this->components->twoColumnDetail('composer scripts', '<fg=green>' . implode(', ', $added) . '</>');
    }

    /**
     * Adds the package to boost.json so `boost:update` includes its guidelines without asking.
     */
    private function registerInBoost(Filesystem $filesystem): bool
    {
        $path = base_path('boost.json');

        if (! $filesystem->exists($path)) {
            return false;
        }

        $boost = json_decode((string) $filesystem->get($path), flags: JSON_THROW_ON_ERROR);
        $packages = isset($boost->packages) && is_array($boost->packages) ? $boost->packages : [];

        if (! in_array(self::PACKAGE, $packages, true)) {
            $boost->packages = [...$packages, self::PACKAGE];
            $filesystem->put($path, json_encode($boost, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
            $this->components->twoColumnDetail('boost.json', '<fg=green>rslanzi/laravel-agents enabled</>');
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function readComposerJson(): array
    {
        /** @var array<string, mixed> */
        return json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    }
}
