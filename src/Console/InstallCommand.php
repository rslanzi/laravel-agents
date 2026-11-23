<?php

declare(strict_types=1);

namespace Rslanzi\LaravelAgents\Console;

use JsonException;
use InvalidArgumentException;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Rslanzi\LaravelAgents\Package;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Rslanzi\LaravelAgents\ComposerJson;
use Symfony\Component\Process\Process as SymfonyProcess;

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

    public const string BOOST = 'laravel/boost';

    /** @var string */
    protected $signature = 'agents:install
        {--only=* : Publish only these stub groups (quality, tests, ci, claude, docs); repeat the option for several}
        {--force : Overwrite config files and composer scripts that already exist}
        {--dry-run : Show what would change without touching the project}
        {--no-tools : Do not install the dev tools with composer}
        {--no-boost : Do not install, configure or update Laravel Boost}
        {--no-fix : Do not run composer fix at the end}';

    /** @var string */
    protected $description = 'Install Boost and the quality tooling, publish the configs without overwriting existing files, load the guidelines and run composer fix';

    public function handle(Filesystem $filesystem, Composer $composer): int
    {
        $groups = $this->selectedGroups();

        if ($groups === null) {
            return self::FAILURE;
        }

        $composerPath = base_path('composer.json');

        if (! $filesystem->exists($composerPath)) {
            $this->components->error('No composer.json found in ' . base_path() . '. Run this command from a Laravel project.');

            return self::FAILURE;
        }

        try {
            $project = ComposerJson::parse($filesystem->get($composerPath));
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->isDryRun()) {
            $this->components->warn('Dry run: nothing will be written.');
        }

        if (! $this->option('no-tools') && ! $this->installTools($filesystem, $composer, $project)) {
            return self::FAILURE;
        }

        $this->publishStubs($filesystem, $groups);
        $this->mergeComposerScripts($filesystem);

        if ($this->isDryRun()) {
            $this->registerInBoost($filesystem);

            return self::SUCCESS;
        }

        if ($this->option('no-boost')) {
            $this->registerInBoost($filesystem);
            $boostLoaded = false;
        } else {
            $boostLoaded = $this->setUpBoost($filesystem);
        }

        $fixed = $this->option('no-fix') ? null : $this->runFix($composer);

        $this->newLine();
        $this->components->info('Done. Next steps:');
        $this->components->bulletList(array_values(array_filter([
            match (true) {
                $boostLoaded => null,
                $filesystem->exists(base_path('boost.json')) => 'Run <comment>php artisan boost:update</comment> to load the guidelines and skills into AGENTS.md and CLAUDE.md.',
                default => 'Run <comment>php artisan boost:install</comment>, select <comment>rslanzi/laravel-agents</comment>, then <comment>php artisan boost:update</comment> to load the guidelines and skills (without Boost, <comment>php artisan agents:markdown</comment> writes a standalone AGENTS.md).',
            },
            'Describe your modules in <comment>.ai/guidelines/20-project-contexts.md</comment> and <comment>docs/architecture.md</comment>.',
            match ($fixed) {
                true => null,
                false => 'Fix what <comment>composer fix</comment> reported, run it again and commit the result.',
                null => 'Run <comment>composer fix</comment> and commit the result.',
            },
            '<comment>composer check</comment> needs 80% coverage: on a fresh app, start with a test for the User model that declares <comment>covers(User::class)</comment> (mutation testing needs it).',
        ])));

        return $fixed === false ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Runs boost:install when Boost is not configured yet, adds the package to boost.json and runs boost:update.
     * Boost may have been installed by this same command, so its commands run in a fresh PHP process.
     *
     * @return bool whether the guidelines are now loaded
     */
    private function setUpBoost(Filesystem $filesystem): bool
    {
        if (! $filesystem->exists(base_path('boost.json'))) {
            if (! $this->input->isInteractive()) {
                $this->components->warn('boost:install asks questions, so it was skipped in non-interactive mode.');

                return false;
            }

            $this->components->info('Running boost:install: select rslanzi/laravel-agents among the packages.');

            if (! $this->artisanProcess(['boost:install'], interactive: true) || ! $filesystem->exists(base_path('boost.json'))) {
                $this->components->warn('boost:install did not complete.');

                return false;
            }
        }

        if (! $this->registerInBoost($filesystem)) {
            return false;
        }

        $this->components->info('Running boost:update to load the guidelines and skills.');

        if (! $this->artisanProcess(['boost:update'])) {
            $this->components->warn('boost:update failed.');

            return false;
        }

        return true;
    }

    private function runFix(Composer $composer): bool
    {
        $this->components->info('Running composer fix (Rector, Pint, PHPStan, tests).');

        $result = Process::path(base_path())
            ->forever()
            ->env(['COMPOSER_NO_INTERACTION' => '1'])
            ->run([...$composer->findComposer(), 'fix', '--no-interaction'], function (string $type, string $output): void {
                $this->output->write($output);
            });

        if ($result->failed()) {
            $this->components->error('composer fix reported problems.');
        }

        return $result->successful();
    }

    /**
     * @param  list<string>  $arguments
     */
    private function artisanProcess(array $arguments, bool $interactive = false): bool
    {
        $process = Process::path(base_path())->forever();
        $command = [PHP_BINARY, 'artisan', ...$arguments];

        if ($interactive && SymfonyProcess::isTtySupported()) {
            return $process->tty()->run($command)->successful();
        }

        return $process->run([...$command, '--no-interaction'], function (string $type, string $output): void {
            $this->output->write($output);
        })->successful();
    }

    /**
     * @return list<string>|null the stub groups to publish, or null when an unknown group was asked for
     */
    private function selectedGroups(): ?array
    {
        /** @var list<string> $only */
        $only = array_values(array_filter((array) $this->option('only')));
        $known = array_keys(Package::STUB_GROUPS);

        if ($only === []) {
            return $known;
        }

        $unknown = array_diff($only, $known);

        if ($unknown !== []) {
            $this->components->error('Unknown group(s): ' . implode(', ', $unknown) . '. Available: ' . implode(', ', $known) . '.');

            return null;
        }

        return array_values(array_unique($only));
    }

    private function isDryRun(): bool
    {
        return (bool) $this->option('dry-run');
    }

    private function installTools(Filesystem $filesystem, Composer $composer, ComposerJson $project): bool
    {
        $installed = $project->packages();
        $tools = $this->option('no-boost') ? self::TOOLS : [...self::TOOLS, self::BOOST];
        $missing = array_values(array_diff($tools, $installed));

        if ($missing === []) {
            $this->components->info('Dev tools already installed.');

            return true;
        }

        $removePhpunit = in_array('pestphp/pest', $missing, true) && in_array('phpunit/phpunit', $installed, true);

        if ($this->isDryRun()) {
            $this->components->twoColumnDetail('composer require --dev', implode(' ', $missing));

            if ($removePhpunit) {
                $this->components->twoColumnDetail('composer remove --dev', 'phpunit/phpunit (Pest brings its own)');
            }

            return true;
        }

        $backup = $this->backupComposerFiles($filesystem);

        if ($removePhpunit) {
            $this->components->info('Removing phpunit/phpunit (Pest brings its own).');
        }

        $this->components->info('Installing ' . implode(', ', $missing) . '...');

        $succeeded = (! $removePhpunit || $this->composer($composer, ['remove', '--dev', '--no-update', 'phpunit/phpunit']))
            && $this->composer($composer, ['require', '--dev', '--with-all-dependencies', ...$missing]);

        if ($succeeded) {
            return true;
        }

        $this->restoreComposerFiles($filesystem, $backup);
        $this->components->error('Composer failed, composer.json and composer.lock were restored. Install the tools by hand: composer require --dev -W ' . implode(' ', $missing));

        return false;
    }

    /**
     * @return array<string, string> file path => original content
     */
    private function backupComposerFiles(Filesystem $filesystem): array
    {
        $backup = [];

        foreach (['composer.json', 'composer.lock'] as $file) {
            $path = base_path($file);

            if ($filesystem->exists($path)) {
                $backup[$path] = $filesystem->get($path);
            }
        }

        return $backup;
    }

    /**
     * @param  array<string, string>  $backup
     */
    private function restoreComposerFiles(Filesystem $filesystem, array $backup): void
    {
        foreach ($backup as $path => $content) {
            $filesystem->put($path, $content);
        }
    }

    /**
     * @param  list<string>  $arguments
     */
    private function composer(Composer $composer, array $arguments): bool
    {
        $result = Process::path(base_path())
            ->timeout(900)
            ->env(['COMPOSER_NO_INTERACTION' => '1'])
            ->run([...$composer->findComposer(), ...$arguments, '--no-interaction']);

        if ($result->failed()) {
            $this->line($result->errorOutput());
        }

        return $result->successful();
    }

    /**
     * @param  list<string>  $groups
     */
    private function publishStubs(Filesystem $filesystem, array $groups): void
    {
        foreach ($groups as $group) {
            foreach (Package::STUB_GROUPS[$group] as $relative) {
                $target = base_path($relative);
                $exists = $filesystem->exists($target);

                if ($exists && ! $this->option('force')) {
                    $this->components->twoColumnDetail($relative, '<fg=yellow>skipped (exists)</>');

                    continue;
                }

                if (! $this->isDryRun()) {
                    $filesystem->ensureDirectoryExists(dirname($target));
                    $filesystem->copy(Package::stubsPath() . '/' . $relative, $target);
                }

                $this->components->twoColumnDetail($relative, $exists ? '<fg=green>overwritten</>' : '<fg=green>published</>');
            }
        }
    }

    private function mergeComposerScripts(Filesystem $filesystem): void
    {
        $path = base_path('composer.json');
        $project = ComposerJson::parse($filesystem->get($path));

        /** @var array{scripts: array<string, list<string>>} $kit */
        $kit = json_decode($filesystem->get(Package::path('resources/composer-scripts.json')), true, flags: JSON_THROW_ON_ERROR);
        $scripts = [];

        foreach ($kit['scripts'] as $name => $commands) {
            if ($project->hasScript($name) && ! $this->option('force')) {
                $this->components->twoColumnDetail("composer {$name}", '<fg=yellow>skipped (exists)</>');

                continue;
            }

            $scripts[$name] = $commands;
        }

        if ($scripts === []) {
            return;
        }

        if (! $this->isDryRun()) {
            $filesystem->put($path, $project->withScripts($scripts)->source());
        }

        $this->components->twoColumnDetail('composer scripts', '<fg=green>' . implode(', ', array_keys($scripts)) . '</>');
    }

    /**
     * Adds the package to boost.json so `boost:update` includes its guidelines and skills without asking.
     */
    private function registerInBoost(Filesystem $filesystem): bool
    {
        $path = base_path('boost.json');

        if (! $filesystem->exists($path)) {
            return false;
        }

        try {
            $boost = json_decode($filesystem->get($path), flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->components->twoColumnDetail('boost.json', '<fg=yellow>skipped (invalid JSON)</>');

            return true;
        }

        if (! $boost instanceof \stdClass) {
            $this->components->twoColumnDetail('boost.json', '<fg=yellow>skipped (not a JSON object)</>');

            return true;
        }

        $packages = isset($boost->packages) && is_array($boost->packages) ? $boost->packages : [];

        if (in_array(Package::NAME, $packages, true)) {
            return true;
        }

        $boost->packages = [...$packages, Package::NAME];

        if (! $this->isDryRun()) {
            $filesystem->put($path, json_encode($boost, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
        }

        $this->components->twoColumnDetail('boost.json', '<fg=green>' . Package::NAME . ' enabled</>');

        return true;
    }
}
