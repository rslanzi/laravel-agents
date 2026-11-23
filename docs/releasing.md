# Releasing and contributing

## Development

Guidelines live in `resources/boost/guidelines/`, skills in `resources/boost/skills/{name}/SKILL.md`, published files in `stubs/`.

```bash
composer install
composer test        # Pest
composer analyse     # PHPStan
composer format      # Pint
```

The `Tests` workflow runs the suite on PHP 8.3 to 8.5 with Laravel 12 and 13, plus a non-blocking PHP 8.6 nightly job. The `Integration` workflow installs the package into a fresh Laravel application and runs `agents:install` twice (the second run must change nothing), then `composer check` and mutation testing.

## Commit messages

Commits follow [Conventional Commits](https://www.conventionalcommits.org/): they decide the next version and the changelog.

| Type | Changelog section | Version bump |
| --- | --- | --- |
| `feat` | Features | minor |
| `fix`, `perf` | Bug Fixes, Performance | patch |
| `docs` | Guidelines and Documentation | patch |
| `feat!`, `BREAKING CHANGE:` footer | Breaking changes | major |
| `ci`, `test`, `refactor`, `chore`, `build`, `style` | hidden | none |

Guideline and skill changes change how agents work in every project, so they are `docs` at least, `feat` when they add a rule, and breaking when an existing project would fail its checks after updating. Every breaking change also gets a section in [UPGRADE.md](../UPGRADE.md).

## Releases

Releases are automatic, through [release-please](https://github.com/googleapis/release-please):

1. Every push to `main` updates an open "chore(main): release x.y.z" pull request with the next version and its changelog.
2. Merging that pull request updates `CHANGELOG.md`, creates the `vx.y.z` tag and publishes the GitHub Release.
3. Packagist picks up the tag through its GitHub hook.

Nothing is tagged by hand. The changelog lists commit subjects, without authors or agent attribution.

One-time setup in the repository settings:

- *Actions > General > Workflow permissions*: allow GitHub Actions to create and approve pull requests.
- Optional: a `RELEASE_PLEASE_TOKEN` secret (a fine-grained token with contents and pull requests write). With the default token, CI does not run on the release pull request.
