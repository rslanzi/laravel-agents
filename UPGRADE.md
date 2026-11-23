# Upgrade Guide

Breaking changes and how to adapt a project, newest first. Each version also lists its changes in [CHANGELOG.md](CHANGELOG.md).

After every update run `composer update rslanzi/laravel-agents`, `php artisan boost:update` and `composer check`.

## Upgrading to 0.1.0 from dev-main

Projects that installed the package from `dev-main` before 0.1.0:

- **Actions expose `execute()`** instead of `handle()`. Rename the method and its callers. The published architecture test checks it; if your copy still checks `handle()`, re-publish it with `php artisan vendor:publish --tag=agents-tests --force` and review the diff.
- **Form Requests have no `authorize()`.** Move each check to the controller (`Gate::authorize('ability', $model)`) or to the route (`->can('ability', 'model')`), then delete the method.
- **Module controllers extend no base class**, and **events are `final`**.
- The `test:unit` composer script is now `test:all`.
- Module routes are loaded from `app/Domain/*/Http/routes.php` and `Http/api.php` with `base_path()`: update the snippet in `bootstrap/app.php` (see the "Modular Architecture" guideline).
