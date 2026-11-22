<?php

declare(strict_types=1);

namespace Rslanzi\LaravelAgents\Tests;

use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;
use Rslanzi\LaravelAgents\LaravelAgentsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected string $projectPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectPath = sys_get_temp_dir() . '/laravel-agents-' . bin2hex(random_bytes(6));
        (new Filesystem)->ensureDirectoryExists($this->projectPath);
        $this->app->setBasePath($this->projectPath);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->projectPath);

        parent::tearDown();
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [LaravelAgentsServiceProvider::class];
    }

    protected function projectFile(string $relative): string
    {
        return $this->projectPath . '/' . $relative;
    }
}
