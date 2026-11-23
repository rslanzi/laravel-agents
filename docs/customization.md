# Customization

## Project rules

The package rules apply to every project. A project's own rules go in `.ai/guidelines/20-project-contexts.md`: bounded contexts, glossary, exceptions with the ADR that justifies them, supported locales, external services. Boost puts it at the top of the instructions. Keep it short, because agents read it on every task.

Longer, human-oriented documentation stays next to the code:

- `docs/architecture.md`: the module map, how modules talk to each other and the external services.
- `app/Domain/{Context}/README.md`: each module's contract with the rest of the application.
- `docs/adr/`: the decisions and why they were made.
- `CHANGELOG.md`: what changed for users, kept in the `Unreleased` section until a release.

The guidelines tell the agent to keep all four current in the same change.

## Architecture test

`tests/Unit/ArchitectureTest.php` belongs to the project. Add rules for real invariants and cross-module boundaries as the project grows; the file ends with examples.

## Commits and PRs in your name

The guidelines forbid the agent to add `Co-Authored-By`, "Generated with..." footers and session links. Claude Code adds attribution on its own: the `.claude/settings.json` published by `agents:install` turns it off (`attribution.commit` and `attribution.pr` empty, `sessionUrl: false`). To get the same in every project, put it in `~/.claude/settings.json`. To keep attribution, install without that group: `--only=quality --only=tests --only=ci --only=docs`.

## Where the rules come from

The PHP, Laravel, SQL, SOLID and clean code rules follow the [IxDF handbook](https://handbook.ixdf.org/library/backend/conventions--laravel.html), adapted to the modular layout. Actions expose `execute()` and Form Requests only validate, as in IxDF. Where the two differ, the package keeps its own choices:

- every application is split into modules, whatever its size;
- Form Requests live in `Http/Requests`;
- factories live in the module;
- exceptions keep the `Exception` suffix;
- translation keys are English source strings in `lang/*.json`;
- views are kebab-case;
- PHPStan at max level and Pest architecture tests replace Psalm and Deptrac.

A project that needs a different rule writes it in `20-project-contexts.md` with an ADR.
