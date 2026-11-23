# Quality Tooling

Four tools guard every change. They are not optional and they are not negotiable: if one fails, the work is not done.

| Tool | Role | Config |
| --- | --- | --- |
| **Rector** (+ `driftingly/rector-laravel`) | automated refactors: modern PHP, type declarations, dead code, early returns, Laravel idioms | `rector.php` |
| **Pint** | code style (Laravel preset + project rules) | `pint.json` |
| **PHPStan** (Larastan) | static analysis and type safety | `phpstan.neon` |
| **Pest** | unit, feature, browser and architecture tests | `tests/Pest.php`, `phpunit.xml` |

## Order and commands

Run them in this order, because each step can change what the next one sees:

1. **Rector** rewrites code: `vendor/bin/rector process` (only on the files you touched when the project is large: `vendor/bin/rector process app/Domain/Ordering`).
2. **Pint** formats the result: `vendor/bin/pint --dirty --format agent`.
3. **PHPStan** checks types: `vendor/bin/phpstan analyse --memory-limit=2G`.
4. **Pest** runs the tests: during the TDD loop the narrowest filter, at the end the affected folders plus `tests/Unit/ArchitectureTest.php`, and the full suite with `--parallel` before handing over.

Prefer the composer scripts when they exist:

- `composer fix`: Rector, Pint, PHPStan, tests (use it before finishing a task);
- `composer check`: the same checks in read-only mode (`--dry-run`, `--test`) with the coverage threshold, plus `composer audit`, as CI runs them;
- `composer test:mutate`: mutation testing with its minimum score (run it on the module you changed before opening a PR: `vendor/bin/pest --mutate --parallel tests/Feature/Domain/Ordering`);
- `composer types:check`, `composer test:arch`, `composer test:coverage` for single steps.

## Continuous integration

`.github/workflows/ci.yml` runs on every pull request and on `main`: `lint:check` (Rector dry-run, Pint test), `types:check` (PHPStan), `test:coverage` (Pest with minimum coverage), `test:mutate` (minimum mutation score), `deps:check` (`composer audit`). A PR is mergeable only when CI is green; make the job a required status check in the repository's branch protection.

## Rules

- **Never silence a tool to get green.** No `@phpstan-ignore`, no new `ignoreErrors` entries, no new baseline lines, no lowering the PHPStan level, no `skip()` / `todo()` on failing tests, no excluding paths from Rector or Pint. If you think a finding is a false positive, explain it and ask the user.
- **The PHPStan baseline only shrinks.** When you touch a file with baseline entries, fix them and regenerate the baseline.
- **The PHPStan level only goes up.** Target 8+ on new projects; on existing ones raise it when the codebase is clean at the current level.
- **Do not fight Rector.** If a Rector rule produces code you disagree with, report it; skip a rule in `rector.php` only with the user's approval and an ADR if it is a lasting choice.
- **Read the tool's output, fix the cause.** A PHPStan error about `mixed` or a possibly-null value is usually a missing type, a missing guard or a wrong PHPDoc generic, not a reason for a cast.
- **Keep tests fast.** In-memory SQLite (or a dedicated test database), `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, `MAIL_MAILER=array`, low `BCRYPT_ROUNDS` in `phpunit.xml`. Tests must pass with `--parallel`.
- **CI runs the same commands.** What passes locally with `composer check` must pass in CI; do not add CI-only exceptions.

## Common PHPStan fixes in Laravel

- Collections and relations: add generics in PHPDoc (`@return Collection<int, Order>`, `@return BelongsTo<Customer, $this>`).
- `__()` returns `array|string`: return it from a method typed `: string` instead of mixing it with `null` in a ternary.
- `$request->user()` can be null: authorize first, or use `$request->user()` behind a route with `auth` and assert with a typed helper.
- Config values: cast at the edge (`(int) config('x.y')`) or use `Config::integer()`, `Config::string()`.
- Model attributes: document them with `@property` PHPDoc only when Larastan cannot infer them from migrations.
