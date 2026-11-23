# Changelog

All notable changes to this package are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Boost skills in `resources/boost/skills/`: behavioral contracts, domain building blocks, safe migrations, API conventions, ADRs, module READMEs, performance, localization, PHPStan fixes. The guidelines keep a short summary of each, so the always-on context is about 30% smaller.
- `agents:install --only=<group>` and `--dry-run`; stubs can be re-published per group with `vendor:publish --tag=agents-<group>`.
- `tests/Unit/TranslationsTest.php` stub.
- MIT license, PHPStan for the package, a CI matrix over PHP 8.3 to 8.5, Laravel 12 and 13 and lowest/stable dependencies, a non-blocking PHP 8.6 nightly job, and an integration workflow that installs the package into a fresh Laravel application on PHP 8.3 to 8.5 (and 8.6 nightly).

### Changed

- Module routes are loaded from `Http/routes.php` (web) and `Http/api.php` (api, `/api` prefix) with `base_path()`.
- The published CI runs `composer check` on the lowest PHP version allowed by `composer.json` and on PHP 8.5, with mutation testing in a separate pull request job; Composer cache and the frontend build work on projects without `package-lock.json`.
- `agents:install` restores `composer.json` and `composer.lock` when Composer fails, keeps the formatting of `composer.json`, finds Composer the way Laravel does and reports a missing or invalid `composer.json`.
- The architecture test scans module folders recursively, covers every core layer and the shared kernel, and only flags Eloquent-style writes.
- The `test:unit` composer script is now `test:all`.
- README in English, with an Italian copy.

### Fixed

- The route registration example in the guidelines pointed outside the project and registered no routes.
- The published `pint.json` enabled `mb_str_functions`, which rewrites `trim()` to the PHP 8.4-only `mb_trim()`.
