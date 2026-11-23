# Laravel and PHP Conventions

## Do things the Laravel way

- After `php artisan make:*`, move domain classes into their context namespace under `app/Domain/{Context}`.
- Validate input with Form Requests in `app/Domain/{Context}/Http/Requests` (controllers) or `validate()` rules (Livewire). Validation rules are arrays, not pipe strings; reusable rules are Rule classes. A Form Request only validates (no `authorize()`: the controller calls `Gate::authorize()`) and exposes a `toData()` method that returns the DTO for the Action. Read input with `input()`, `string()`, `integer()`, never `get()`.
- Authorize with Policies and Gates, never with ad-hoc `if ($user->role === ...)` checks. No code path is "covered" by a missing check.
- Read configuration with `config()`. `env()` is only allowed inside `config/` files.
- Use queues for slow or external work (mail, webhooks, PDFs, third-party APIs). Jobs are idempotent, safe to retry and to run concurrently (or `ShouldBeUnique`), do not depend on order (or use `Bus::chain()`), and delegate writes to Actions.
- Dispatch events with `Event::dispatch(new OrderConfirmed(...))`; event classes are `final` (and `readonly` unless broadcast) and use only the traits they need (no `Dispatchable`).
- Routes: verb first, tuple or invokable syntax (`Route::post('/orders/{order}/confirm', ConfirmOrderController::class)->name(...)`), no `Route::resource`, kebab-case URLs, camelCase parameters and route names (`ordering.orderLines.show`). Always name HTML routes and build URLs with `route()`.
- Controllers are single-action and final, use a singular resource name, extend no base class, and take route parameters first, then the Request, then other dependencies. Redirect with `redirect()->route(...)`. `abort()` always gets a message.
- Policy abilities are camelCase CRUD verbs (`view`, not `show`).
- Use Laravel's HTTP client, Storage, Cache, Mail and Notification facades through their testable fakes; wrap external services behind a `Contract`.
- Do not create new top-level folders, verification scripts or documentation files unless asked.

## PHP style

- Every PHP file starts with `declare(strict_types=1);`.
- Full property, parameter and return types everywhere, closures included (`void`, `never` for methods that always throw). Avoid `mixed`. No PHPDoc on fully typed methods unless it adds a description; use it for generics and array shapes (`@return Collection<int, Order>`), on one line when it fits, with fully qualified class names.
- Classes are `final` by default (Actions, Services, Models, Listeners, Jobs). DTOs and value objects are `readonly`.
- Properties and methods are `private` unless they need wider visibility.
- Early returns, no `else` after `return`.
- `Foo::class`, never class names in strings; `self` inside the class; named constructors (`Money::fromCents()`) when an object has more than one way to be valid; intention-revealing methods instead of generic setters.
- Strings: interpolation with braces (`"Order {$order->id}"`), then `sprintf()`, then concatenation. Casts (`(int)`) instead of `intval()`.
- `assert()` only for states your own code makes impossible; validate external input and throw exceptions for it.
- Dates are immutable (`Date::use(CarbonImmutable::class)` in `AppServiceProvider`). Type against `CarbonInterface`.

## Eloquent

- Use PHP attributes on models where the Laravel version supports them (`#[Fillable([...])]`, `#[Hidden([...])]`) and declare casts in a `casts()` method.
- Always start queries with `Model::query()`; never use static magic like `Model::where()` or `whereEmail()`.
- Document magic with PHPDoc (`@property-read` for relations, `@method` for scopes), set safe defaults in `$attributes`, and move scopes into a custom Builder class once a model has three or more.
- Write models field by field from the DTO in Actions; never fill from `$request->all()`.
- Select the columns you need (`select([...])`) on list and report queries, and eager load relations (`with()`). Enable `Model::preventLazyLoading()` outside production.
- Wrap multi-step writes in `DB::transaction()`; lock rows (`lockForUpdate()`) when concurrent writes can break an invariant.
- Prefer database constraints (foreign keys, unique indexes) as the last line of defence for invariants.
- Migrations are forward-only once deployed: add a new migration instead of editing an old one. Every migration has a `down()`.

## SQL

- Business dates and times (`starts_at`, `due_on`) are `dateTime()`/`date()` columns; `TIMESTAMP` only for record metadata (`timestamps()`, `softDeletes()`): it ends in 2038 and shifts with the connection timezone. Store and read datetimes in UTC.
- Raw SQL keywords and functions in upper case (`SELECT MAX(sent_at)`), always with bindings.

Before writing routes, controllers, Eloquent models, scopes, factories, Artisan commands, events, jobs, Blade views or config files, follow the `laravel-conventions` skill.

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
