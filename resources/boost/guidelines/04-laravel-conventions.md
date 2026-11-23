# Laravel and PHP Conventions

## Do things the Laravel way

- After `php artisan make:*`, move domain classes into their context namespace under `app/Domain/{Context}`.
- Validate input with Form Requests in `app/Domain/{Context}/Http/Requests` (controllers) or `validate()` rules (Livewire). Validation rules are arrays, not pipe strings. A Form Request exposes a `toData()` method that returns the DTO for the Action.
- Authorize with Policies and Gates, never with ad-hoc `if ($user->role === ...)` checks. No code path is "covered" by a missing check.
- Read configuration with `config()`. `env()` is only allowed inside `config/` files.
- Use queues for slow or external work (mail, webhooks, PDFs, third-party APIs). Jobs are idempotent and delegate writes to Actions.
- Use Laravel's HTTP client, Storage, Cache, Mail and Notification facades through their testable fakes; wrap external services behind a `Contract`.
- Do not create new top-level folders, verification scripts or documentation files unless asked.

## PHP style

- Every PHP file starts with `declare(strict_types=1);`.
- Full parameter and return types everywhere, closures included. Avoid `mixed`. PHPDoc only for generics and array shapes (`@return Collection<int, Order>`).
- Classes are `final` by default (Actions, Services, Models, Listeners, Jobs). DTOs and value objects are `readonly`.
- Properties and methods are `private` unless they need wider visibility.
- Early returns, no `else` after `return`.
- Dates are immutable (`Date::use(CarbonImmutable::class)` in `AppServiceProvider`). Type against `CarbonInterface`.

## Eloquent

- Use PHP attributes on models where the Laravel version supports them (`#[Fillable([...])]`, `#[Hidden([...])]`) and declare casts in a `casts()` method.
- Always start queries with `Model::query()`; never use static magic like `Model::where()`.
- Select the columns you need (`select([...])`) on list and report queries, and eager load relations (`with()`). Enable `Model::preventLazyLoading()` outside production.
- Wrap multi-step writes in `DB::transaction()`; lock rows (`lockForUpdate()`) when concurrent writes can break an invariant.
- Prefer database constraints (foreign keys, unique indexes) as the last line of defence for invariants.
- Migrations are forward-only once deployed: add a new migration instead of editing an old one.

## Finite states are enums

- Any column representing a finite set of states (`status`, `priority`, `type`) is a backed enum under `App\Domain\{Context}\Enums`, cast in `casts()`. Never compare raw strings (`=== 'active'`).
- Boolean flags (`is_active`) stay boolean casts.
- Domain enums stay framework-agnostic: an optional `label()` method is fine, UI concerns (colors, icons, Filament `HasLabel`/`HasColor`) live in the presentation layer.
- Allowed transitions between states live in a `StateMachines/` class, not scattered across Actions.

## Filament (only if installed)

- Filament stays in `app/Filament`, organized by context: `Resources/{Context}/`, `Pages/{Context}/`, `Widgets/{Context}/`. They are delivery code: they call Actions for anything with invariants and may use plain CRUD only for reference entities.
- Panel providers live in `app/Providers/Filament`; navigation groups mirror the contexts; dashboards that combine several modules sit at the root of `app/Filament/Pages`.
- Put each chained method of a Filament component on its own line, and leave a blank line between components.
- Build `Select`/`SelectFilter` options from `Enum::cases()`, never hand-typed arrays.
