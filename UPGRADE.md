# Upgrade Guide

Breaking changes and how to adapt a project, newest first. Each version also lists its changes in [CHANGELOG.md](CHANGELOG.md).

After every update run `composer update rslanzi/laravel-agents`, `php artisan boost:update` and `composer check`.

## Upgrading from 0.3.x

The module layout now matches the folders a module shares with a Laravel application. Move the folders, fix the namespaces (`composer dump-autoload`), then run `composer check`:

- **`DTOs/` is `DTO/`**: namespace `App\Domain\{Context}\DTO`.
- **Factories, migrations and seeders live in `Database/`**: `Database/Factories`, `Database/Migrations`, `Database/Seeders`. Load the migrations from the module's service provider (`$this->loadMigrationsFrom(__DIR__.'/../Database/Migrations')`).
- **Routes live in `Routes/`**: `Http/routes.php` becomes `Routes/web.php`, `Http/api.php` becomes `Routes/api.php`. Update the two `glob()` calls in `bootstrap/app.php` (see the "Modular Architecture" guideline).
- **Policies and service providers live in the module**: `app/Domain/{Context}/Policies` (auto-discovered) and `app/Domain/{Context}/Providers/{Context}ServiceProvider.php`, registered in `bootstrap/providers.php`. `app/Providers` keeps `AppServiceProvider` and the Filament panel providers.
- The published architecture test treats `Routes` as delivery and `Providers` as wiring, adds `Policies` to the module core and fails on `DTOs/`, `Factories/`, `Migrations/` or `Seeders/` at the module root. Your copy is not overwritten: compare it with `php artisan vendor:publish --tag=agents-tests --force` and review the diff. Do the same for `phpstan.neon` (`--tag=agents-quality`), which now reads the module migrations.
- **Only the user marks an ADR `Accepted`**, and the index in `docs/adr/README.md` copies each file's status. Do not re-publish the `docs` group over your index: copy the new paragraph from the package's `stubs/docs/adr/README.md` and fix any status that differs from its file.

## Upgrading to 0.2.0 from 0.1.x

- **PHP 8.5 and Laravel 13 are required.** Upgrade the project first; on PHP 8.3 or 8.4, or on Laravel 12, keep `"rslanzi/laravel-agents": "^0.1.0"`.
- The published CI (`.github/workflows/ci.yml`) still runs on the lowest PHP version allowed by the project's `composer.json` and on PHP 8.5: nothing to change there.

## Upgrading to 0.1.0 from dev-main

Projects that installed the package from `dev-main` before 0.1.0 (switch to a released version as described in [docs/installation.md](docs/installation.md)):

- **Actions expose `execute()`** instead of `handle()`. Rename the method and its callers. The published architecture test checks it; if your copy still checks `handle()`, re-publish it with `php artisan vendor:publish --tag=agents-tests --force` and review the diff.
- **Form Requests have no `authorize()`.** Move each check to the controller (`Gate::authorize('ability', $model)`) or to the route (`->can('ability', 'model')`), then delete the method.
- **Module controllers extend no base class**, and **events are `final`**.
- The `test:unit` composer script is now `test:all`.
- Module routes are loaded from `app/Domain/*/Http/routes.php` and `Http/api.php` with `base_path()`: update the snippet in `bootstrap/app.php` (see the "Modular Architecture" guideline).
