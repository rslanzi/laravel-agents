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
        {--path=AGENTS.md : Where to write the file, relative to the project root}
        {--force : Overwrite the file if it exists}';

    /** @var string */
    protected $description = 'Write a standalone AGENTS.md with every guideline (for projects that do not use Laravel Boost)';

    public function handle(Filesystem $filesystem): int
    {
        $target = base_path((string) $this->option('path'));

        if ($filesystem->exists($target) && ! $this->option('force')) {
            $this->components->error("{$this->option('path')} already exists. Use --force to overwrite it (Laravel Boost users should run boost:update instead).");

            return self::FAILURE;
        }

        $filesystem->put($target, Package::agentsMarkdown());

        $this->components->info("{$this->option('path')} written.");

        return self::SUCCESS;
    }
}
