<?php

declare(strict_types=1);

namespace Rslanzi\LaravelAgents;

use Illuminate\Support\ServiceProvider;
use Rslanzi\LaravelAgents\Console\InstallCommand;
use Rslanzi\LaravelAgents\Console\AgentsMarkdownCommand;

final class LaravelAgentsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            AgentsMarkdownCommand::class,
        ]);

        // `php artisan vendor:publish --tag=agents-ci --force` re-publishes a single group after an upgrade.
        foreach (Package::STUB_GROUPS as $group => $files) {
            $paths = [];

            foreach ($files as $file) {
                $paths[Package::stubsPath() . '/' . $file] = $this->app->basePath($file);
            }

            $this->publishes($paths, ['agents', "agents-{$group}"]);
        }
    }
}
