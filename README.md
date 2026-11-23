# rslanzi/laravel-agents

[Italiano](README.it.md)

Guidelines for AI coding agents (Claude Code, Codex, Cursor, ...) and quality tooling for Laravel applications organized in DDD-style modules.

- **Guidelines** loaded automatically by [Laravel Boost](https://laravel.com/docs/boost) into `AGENTS.md` and `CLAUDE.md`: principles (KISS, YAGNI, SOLID, clean code), modular architecture, TDD, Laravel conventions, ADRs, security, performance, errors and logging, quality tooling, Git, safe migrations, APIs, localization, module READMEs.
- **Skills** that Boost loads only when the task needs them, to keep the always-on context small: Laravel conventions, behavioral contracts, domain building blocks, safe migrations, API conventions, ADRs, module READMEs, performance, localization, PHPStan fixes.
- **`php artisan agents:install`**: installs Pest, Larastan, Rector and rector-laravel (Pint ships with Laravel) and publishes their configs without overwriting existing files: `pint.json`, `rector.php`, `phpstan.neon`, `tests/Pest.php`, `tests/Unit/ArchitectureTest.php`, `tests/Unit/TranslationsTest.php`, GitHub Actions CI, PR template, `.claude/settings.json` without agent attribution, ADR index, architecture map, changelog, project modules file, composer scripts.
- **`php artisan agents:markdown`**: writes a complete `AGENTS.md` (guidelines and skills) for projects that do not use Boost.

## Quick start

The package is not on Packagist yet. Until it is, add the repository to the project's `composer.json`:

```bash
composer config repositories.laravel-agents vcs https://github.com/rslanzi/laravel-agents
composer require --dev rslanzi/laravel-agents:dev-main
composer require --dev laravel/boost          # if it is not installed yet

php artisan boost:install                     # select rslanzi/laravel-agents among the packages
php artisan agents:install                    # tooling and configs
composer fix                                  # Rector, Pint, PHPStan, Pest
```

To update: `composer update rslanzi/laravel-agents && php artisan boost:update`, after reading [UPGRADE.md](UPGRADE.md).

## Documentation

- [Installation](docs/installation.md): `agents:install` options and published files, updating, re-publishing configs.
- [Customization](docs/customization.md): project rules, architecture map, attribution, where the rules come from.
- [Releasing and contributing](docs/releasing.md): development, commit messages, automatic releases.
- [CHANGELOG.md](CHANGELOG.md) and [UPGRADE.md](UPGRADE.md).

## Composer scripts

| Script | What it does |
| --- | --- |
| `composer fix` | Rector, Pint, PHPStan, tests: run it before finishing a task |
| `composer check` | the same checks read-only, with minimum coverage and `composer audit`: what the CI `quality` job runs |
| `composer test:all` | the whole suite in parallel |
| `composer test:coverage` | Pest with 80% minimum coverage (needs PCOV or Xdebug) |
| `composer test:mutate` | mutation testing with a 70% minimum score (needs PCOV or Xdebug); the CI `mutation` job runs it on pull requests |
| `composer test:arch` | the architecture test only |

## PHP support

PHP 8.3, 8.4 and 8.5 are tested on every change, with Laravel 12 and 13. PHP 8.6 runs on its nightly build in a non-blocking job, so incompatibilities show up before its release.

## License

MIT, see [LICENSE](LICENSE).
