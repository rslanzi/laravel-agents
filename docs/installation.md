# Installation

## Requirements

PHP 8.5 or later, Laravel 13, Composer 2. Projects on PHP 8.3 or 8.4, or on Laravel 12, can use 0.1 (`^0.1.0`).

## Install

```bash
composer require --dev rslanzi/laravel-agents
```

This adds the current version (for example `"rslanzi/laravel-agents": "^0.2"`) to `require-dev`. While the package is 0.x, a breaking change raises the minor version (0.2 to 0.3), so `^0.2` takes only the compatible patch releases.

A project that installed the package from GitHub before it was on Packagist can drop the VCS repository and switch to the released version:

```bash
composer config --unset repositories.laravel-agents
composer require --dev rslanzi/laravel-agents
```

Then run the whole setup with one command:

```bash
php artisan agents:install
```

Without Boost, use `php artisan agents:install --no-boost`, then `php artisan agents:markdown` writes a complete `AGENTS.md` with every guideline and skill inline.

## `agents:install`

It runs these steps in order:

1. Installs Laravel Boost, Pest, Larastan, Rector and rector-laravel with Composer (Pint ships with Laravel), skipping those already in `composer.json`.
2. Publishes the files below without overwriting existing ones, and adds the composer scripts.
3. Runs `php artisan boost:install` when the project has no `boost.json` yet. It asks which agents and packages to configure: select `rslanzi/laravel-agents`. In non-interactive mode (`--no-interaction`, CI) this step is skipped.
4. Adds `rslanzi/laravel-agents` to `boost.json` and runs `php artisan boost:update`, which writes the guidelines and skills into `AGENTS.md` and `CLAUDE.md`.
5. Runs `composer fix` (Rector, Pint, PHPStan, tests). The command fails if `composer fix` reports problems; fix them and run `composer fix` again. On a fresh application, build the frontend first (`npm install && npm run build`): Laravel's example test renders the welcome page, which needs the Vite manifest.

Running it again is safe: existing files and scripts are kept, Boost is only updated, and `composer fix` changes nothing on clean code.

| Group | Files |
| --- | --- |
| `quality` | `pint.json`, `rector.php`, `phpstan.neon` |
| `tests` | `tests/Pest.php`, `tests/Unit/ArchitectureTest.php`, `tests/Unit/TranslationsTest.php` |
| `ci` | `.github/workflows/ci.yml`, `.github/pull_request_template.md` |
| `claude` | `.claude/settings.json` (no agent attribution in commits and PRs) |
| `docs` | `.ai/guidelines/20-project-contexts.md`, `docs/adr/README.md`, `docs/architecture.md`, `CHANGELOG.md` |

| Option | Effect |
| --- | --- |
| `--only=<group>` | publish only one group of files; repeat it for several |
| `--dry-run` | show what would change, write nothing |
| `--no-tools` | do not run `composer require` for the tools |
| `--no-boost` | do not install, configure or update Laravel Boost |
| `--no-fix` | do not run `composer fix` at the end |
| `--force` | overwrite existing configs and composer scripts |

On a project that uses PHPUnit, `agents:install` removes `phpunit/phpunit` from `require-dev` because Pest brings its own version; existing PHPUnit tests keep running under Pest. If Composer fails, `composer.json` and `composer.lock` are restored.

New composer scripts are added to the existing `scripts` section and the rest of `composer.json` is left byte for byte as it was. Only `--force` on a script that already exists rewrites the whole file.

`composer check` needs 80% coverage. On a fresh application, start with a test for the `User` model that declares `covers(User::class)`, which mutation testing also needs.

## Updating

```bash
composer update rslanzi/laravel-agents
php artisan boost:update
```

Read [UPGRADE.md](../UPGRADE.md) before moving to a new minor version (while the package is 0.x) or a new major version.

Published files (Rector, PHPStan, CI...) belong to the project and are never updated on their own. To take a newer version of one group, re-publish it and review the diff:

```bash
php artisan vendor:publish --tag=agents-ci --force   # or agents-quality, agents-tests, agents-claude, agents-docs, agents
```
