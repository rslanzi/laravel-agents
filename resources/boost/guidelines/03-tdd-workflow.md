# Test-Driven Development

Every behaviour change starts with a failing test. Tests are the specification; code exists to make them pass.

## The loop (Red, Green, Refactor)

1. **Red**: write the smallest test that describes the next piece of behaviour. Run it and confirm it fails for the expected reason (not a typo or a missing import).
2. **Green**: write the minimum production code that makes the test pass. No extra branches, options or abstractions.
3. **Refactor**: with the test green, remove duplication, improve names and move code to the right layer. Re-run the tests after each step.
4. Repeat for the next behaviour, including failure modes (invalid input, forbidden user, violated business rule, concurrent change).

When fixing a bug, first write a test that reproduces it, watch it fail, then fix it.

Pure copy, styling or layout-only changes do not need new tests.

## What to test where

| Layer | Test type | Location | Focus |
| --- | --- | --- | --- |
| Actions, aggregates, state machines | Feature (with database) | `tests/Feature/Domain/{Context}/` | business rules, emitted events, thrown exceptions, persisted state |
| Services, value objects, DTOs, enums | Unit | `tests/Unit/Domain/{Context}/` | pure logic, no database, no container |
| Queries | Feature | `tests/Feature/Domain/{Context}/` | returned data, filters, no N+1 |
| Module controllers, Form Requests, Livewire | Feature | `tests/Feature/Domain/{Context}/{Http,Livewire}/` | routes, authorization, validation, that the right Action is called, response |
| Filament resources, pages, widgets | Feature | `tests/Feature/Filament/{Context}/` | routes, authorization, validation, that the right Action is called, response |
| Cross-module screens (dashboard, layout) | Feature | `tests/Feature/{Livewire,Filament}/` | authorization, validation, that the right Action is called, response |
| Cross-context flows | Feature | `tests/Feature/Domain/` | event chains between contexts |
| Critical user journeys | Browser (Pest browser plugin) | `tests/Browser/` | a few end-to-end smoke paths only |
| Contracts between modules | Contract (Pest dataset over every implementation and fake) | `tests/Feature/Contracts/{Context}/` | documented pre/postconditions, exceptions, events |
| Architecture | Arch | `tests/Unit/ArchitectureTest.php` | module boundaries and conventions |

Most tests are feature tests. Do not test Laravel itself (that a `belongsTo` works, that validation rule `required` works).

## How to write tests

- Use Pest. Create tests with `php artisan make:test --pest {Name}` (add `--unit` for unit tests). Do not include the suite folder in the name.
- Describe behaviour in the test name: `it('rejects an order when a product is out of stock')`.
- Follow Arrange, Act, Assert, separated by a blank line. One behaviour per test.
- Build data with factories and their states (`OrderFactory::new()->confirmed()->create()`). Check existing states before setting attributes by hand. Add a factory state when the same setup repeats.
- Freeze time with `Carbon::setTestNow()` (or `$this->travelTo()`) whenever behaviour depends on dates, and reset it after.
- Fake the edges, not the domain: `Mail::fake()`, `Notification::fake()`, `Queue::fake()`, `Event::fake([OnlyThisEvent::class])`, `Http::fake()`, and fake implementations of `Contracts` that pass the same contract test as the real one. Never mock the Action under test or Eloquent models.
- Assert outcomes, not implementation: database state (`assertDatabaseHas`), dispatched events, thrown exceptions (`->throws(ProductOutOfStockException::class)`), HTTP status and redirects.
- Tests must be independent and runnable in parallel. No reliance on test order, shared static state or real external services.
- Do not delete or skip existing tests without the user's approval. A red test is a signal, not an obstacle.

## Running tests

- During the loop, run the narrowest set: `php artisan test --compact --filter=testName` or a single file path.
- Before finishing, run the affected directory, then the architecture tests.
- Re-run only the failing tests while fixing them; run the full suite once at the end.

## Coverage and mutation testing

Coverage says which lines ran; mutation testing says whether the tests would notice if they were wrong. Both have minimums enforced in CI.

- **Coverage**: `vendor/bin/pest --coverage --min=80` (needs PCOV or Xdebug). The threshold is a floor for the whole suite, not a target: new code in the domain core (Actions, aggregates, Services, StateMachines) should be close to 100%.
- **Mutation testing**: `vendor/bin/pest --mutate --min=70`. Pest mutates only code that tests declare they cover, so every test file for a core class declares it at the top:

  ```php
  covers(ConfirmOrderAction::class);
  // or, for tests that exercise a class through other entry points:
  mutates(OrderStateMachine::class);
  ```

- When a mutant survives, add or sharpen an assertion (boundary values, the exact exception, the exact persisted state). Do not delete the `covers()` to make it go away.
- Exclude only code that cannot be meaningfully tested (generated config, framework glue) with `@codeCoverageIgnore` and a one-line reason; never exclude domain logic.
- Thresholds only go up. Raise them when the suite is comfortably above.

## Quality gates (Definition of Done)

A change is done only when Rector, Pint, PHPStan and Pest all pass. Commands, order and rules are in "Quality Tooling".
