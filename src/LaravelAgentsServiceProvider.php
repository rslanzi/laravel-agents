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
    }
}
