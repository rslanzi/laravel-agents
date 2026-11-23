# Upgrade Guide

Breaking changes and how to adapt a project, newest first. Each version also lists its changes in [CHANGELOG.md](CHANGELOG.md).

After every update run `composer update rslanzi/laravel-agents`, `php artisan boost:update` and `composer check`.

## Upgrading to 2.0.0 from 1.x

- **PHP 8.5 and Laravel 13 are required.** Upgrade the project first; on PHP 8.3 or 8.4, or on Laravel 12, keep `"rslanzi/laravel-agents": "^1.0"`.
- The published CI (`.github/workflows/ci.yml`) still runs on the lowest PHP version allowed by the project's `composer.json` and on PHP 8.5: nothing to change there.

## Upgrading to 1.0.0 from dev-main

Projects that installed the package from `dev-main` before 1.0.0 (switch to `^1.0` as described in [docs/installation.md](docs/installation.md)):

- **Actions expose `execute()`** instead of `handle()`. Rename the method and its callers. The published architecture test checks it; if your copy still checks `handle()`, re-publish it with `php artisan vendor:publish --tag=agents-tests --force` and review the diff.
- **Form Requests have no `authorize()`.** Move each check to the controller (`Gate::authorize('ability', $model)`) or to the route (`->can('ability', 'model')`), then delete the method.
- **Module controllers extend no base class**, and **events are `final`**.
- The `test:unit` composer script is now `test:all`.
- Module routes are loaded from `app/Domain/*/Http/routes.php` and `Http/api.php` with `base_path()`: update the snippet in `bootstrap/app.php` (see the "Modular Architecture" guideline).
