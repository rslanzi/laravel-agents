<?php

declare(strict_types=1);

namespace Rslanzi\LaravelAgents\Console;

use Illuminate\Console\Command;
use Rslanzi\LaravelAgents\Package;
use Illuminate\Filesystem\Filesystem;

final class AgentsMarkdownCommand extends Command
{
    /** @var string */
    protected $signature = 'agents:markdown
        {--path=AGENTS.md : Where to write the file, absolute or relative to the project root}
        {--force : Overwrite the file if it exists}';

    /** @var string */
    protected $description = 'Write a standalone AGENTS.md with every guideline and skill (for projects that do not use Laravel Boost)';

    public function handle(Filesystem $filesystem): int
    {
        $option = $this->option('path');
        $path = is_string($option) && $option !== '' ? $option : 'AGENTS.md';
        $target = $this->isAbsolute($path) ? $path : base_path($path);

        if ($filesystem->exists($target) && ! $this->option('force')) {
            $this->components->error("{$path} already exists. Use --force to overwrite it (Laravel Boost users should run boost:update instead).");

            return self::FAILURE;
        }

        $filesystem->ensureDirectoryExists(dirname($target));
        $filesystem->put($target, Package::agentsMarkdown());

        $this->components->info("{$path} written.");

        return self::SUCCESS;
    }

    private function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
