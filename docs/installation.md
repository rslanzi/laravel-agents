# Installation

## Requirements

PHP 8.5 or later, Laravel 13, Composer 2. Projects on PHP 8.3 or 8.4, or on Laravel 12, can use version 1 (`^1.0`).

## Install

```bash
composer require --dev rslanzi/laravel-agents
```

This adds the current major version (for example `"rslanzi/laravel-agents": "^2.0"`) to `require-dev`, so `composer update` takes new minor and patch versions but never a breaking one.

A project that installed the package from GitHub before it was on Packagist can drop the VCS repository and switch to the released version:

```bash
composer config --unset repositories.laravel-agents
composer require --dev rslanzi/laravel-agents
```

Then:

```bash
composer require --dev laravel/boost          # if it is not installed yet

php artisan boost:install                     # select rslanzi/laravel-agents among the packages
php artisan agents:install                    # tooling and configs
composer fix                                  # Rector, Pint, PHPStan, Pest
```

If Boost is already configured, `agents:install` adds `rslanzi/laravel-agents` to `boost.json`; then run `php artisan boost:update`.

Without Boost, `php artisan agents:markdown` writes a complete `AGENTS.md` with every guideline and skill inline.

## `agents:install`

It installs Pest, Larastan, Rector and rector-laravel (Pint ships with Laravel), publishes the files below without overwriting existing ones, and adds the composer scripts.

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
| `--force` | overwrite existing configs and composer scripts |

On a project that uses PHPUnit, `agents:install` removes `phpunit/phpunit` from `require-dev` because Pest brings its own version; existing PHPUnit tests keep running under Pest. If Composer fails, `composer.json` and `composer.lock` are restored.

New composer scripts are added to the existing `scripts` section and the rest of `composer.json` is left byte for byte as it was. Only `--force` on a script that already exists rewrites the whole file.

`composer check` needs 80% coverage. On a fresh application, start with a test for the `User` model that declares `covers(User::class)`, which mutation testing also needs.

## Updating

```bash
composer update rslanzi/laravel-agents
php artisan boost:update
```

Read [UPGRADE.md](../UPGRADE.md) before moving to a new major version.

Published files (Rector, PHPStan, CI...) belong to the project and are never updated on their own. To take a newer version of one group, re-publish it and review the diff:

```bash
php artisan vendor:publish --tag=agents-ci --force   # or agents-quality, agents-tests, agents-claude, agents-docs, agents
```
