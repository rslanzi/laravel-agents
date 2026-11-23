# rslanzi/laravel-agents

[Italiano](README.it.md)

Guidelines for AI coding agents (Claude Code, Codex, Cursor, ...) and quality tooling for Laravel applications organized in DDD-style modules.

- **Guidelines** loaded automatically by [Laravel Boost](https://laravel.com/docs/boost) into `AGENTS.md` and `CLAUDE.md`: principles (KISS, YAGNI, SOLID, clean code), modular architecture, TDD, Laravel conventions, ADRs, security, performance, errors and logging, quality tooling, Git, safe migrations, APIs, localization, module READMEs.
- **Skills** that Boost loads only when the task needs them, to keep the always-on context small: Laravel conventions, behavioral contracts, domain building blocks, safe migrations, API conventions, ADRs, module READMEs, performance, localization, PHPStan fixes.
- **`php artisan agents:install`**: installs Pest, Larastan, Rector and rector-laravel (Pint ships with Laravel) and publishes their configs without overwriting existing files: `pint.json`, `rector.php`, `phpstan.neon`, `tests/Pest.php`, `tests/Unit/ArchitectureTest.php`, `tests/Unit/TranslationsTest.php`, GitHub Actions CI, PR template, `.claude/settings.json` without agent attribution, ADR index, project modules file, composer scripts.
- **`php artisan agents:markdown`**: writes a complete `AGENTS.md` (guidelines and skills) for projects that do not use Boost.

## Installation

The package is not on Packagist yet. Until it is, add the repository to the project's `composer.json`:

```bash
composer config repositories.laravel-agents vcs https://github.com/rslanzi/laravel-agents
composer require --dev rslanzi/laravel-agents:dev-main
```

Then:

```bash
composer require --dev laravel/boost          # if it is not installed yet

php artisan boost:install                     # select rslanzi/laravel-agents among the packages
php artisan agents:install                    # tooling and configs
composer fix                                  # Rector, Pint, PHPStan, Pest
```

If Boost is already configured, `agents:install` adds `rslanzi/laravel-agents` to `boost.json`; then run `php artisan boost:update`.

`agents:install` options:

| Option | Effect |
| --- | --- |
| `--only=<group>` | publish only one group of files (`quality`, `tests`, `ci`, `claude`, `docs`); repeat it for several |
| `--dry-run` | show what would change, write nothing |
| `--no-tools` | do not run `composer require` for the tools |
| `--force` | overwrite existing configs and composer scripts |

On a project that uses PHPUnit, `agents:install` removes `phpunit/phpunit` from `require-dev` because Pest brings its own version; existing PHPUnit tests keep running under Pest. If Composer fails, `composer.json` and `composer.lock` are restored.

New composer scripts are added to the existing `scripts` section and the rest of `composer.json` is left byte for byte as it was. Only `--force` on a script that already exists rewrites the whole file.

## Updating the rules in every project

```bash
composer update rslanzi/laravel-agents
php artisan boost:update
```

Published configs (Rector, PHPStan, CI...) belong to the project. To take a newer version of one group, re-publish it and review the diff:

```bash
php artisan vendor:publish --tag=agents-ci --force   # or agents-quality, agents-tests, agents-claude, agents-docs, agents
```

## Composer scripts

| Script | What it does |
| --- | --- |
| `composer fix` | Rector, Pint, PHPStan, tests: run it before finishing a task |
| `composer check` | the same checks read-only, with minimum coverage and `composer audit`: what the CI `quality` job runs |
| `composer test:all` | the whole suite in parallel |
| `composer test:coverage` | Pest with 80% minimum coverage (needs PCOV or Xdebug) |
| `composer test:mutate` | mutation testing with a 70% minimum score (needs PCOV or Xdebug); the CI `mutation` job runs it on pull requests |
| `composer test:arch` | the architecture test only |

## Commits and PRs in your name

The guidelines forbid the agent to add `Co-Authored-By`, "Generated with..." footers and session links. Claude Code adds attribution on its own: the `.claude/settings.json` published by `agents:install` turns it off (`attribution.commit` and `attribution.pr` empty, `sessionUrl: false`). To get the same in every project, put it in `~/.claude/settings.json`. To keep attribution, install without that group: `--only=quality --only=tests --only=ci --only=docs`.

## Sources

The PHP, Laravel, SQL, SOLID and clean code rules follow the [IxDF handbook](https://handbook.ixdf.org/library/backend/conventions--laravel.html), adapted to the modular layout. Where they differ, the package keeps Laravel's own conventions: Actions expose `handle()`, Form Requests authorize and live in `Http/Requests`, factories live in the module, exceptions keep the `Exception` suffix, translation keys are English source strings and views are kebab-case.

## Per-project rules

The package rules apply to every project. A project's own rules go in `.ai/guidelines/20-project-contexts.md` (modules, glossary, exceptions), which Boost puts at the top of the instructions.

## Developing the package

Guidelines live in `resources/boost/guidelines/`, skills in `resources/boost/skills/{name}/SKILL.md`, published files in `stubs/`.

```bash
composer install
composer test        # Pest
composer analyse     # PHPStan
composer format      # Pint
```

The `Integration` workflow installs the package into a fresh Laravel application and runs `agents:install` twice (the second run must change nothing), then `composer check` and mutation testing.

## PHP support

PHP 8.3, 8.4 and 8.5 are tested on every change, with Laravel 12 and 13. PHP 8.6 runs on its nightly build in a non-blocking job, so incompatibilities show up before its release.

## License

MIT, see [LICENSE](LICENSE).
