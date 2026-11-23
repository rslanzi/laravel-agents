# Modular Architecture (DDD-style bounded contexts)

The application is organized by business capability, not by file type. Each module is a bounded context under `app/Domain/{Context}`. Each module also owns its delivery layer: HTTP (controllers, Form Requests, API Resources, routes) in `app/Domain/{Context}/Http`, and Livewire components in `app/Domain/{Context}/Livewire`. Everything about a capability lives in one folder.

Filament is the exception: it stays in `app/Filament` so that panel discovery and the `make:filament-*` generators keep working unchanged, but its content is organized by the same contexts (`app/Filament/Resources/{Context}/...`).

Inside a module there are two rings:

- **Delivery** (`Http/`, `Livewire/`, plus the context's folder in `app/Filament`): the outer shell. It validates, authorizes, builds DTOs and calls Actions and Queries.
- **Core** (everything else: Actions, Models, Services, Queries, Events, Listeners, Jobs, DTO, Enums, Contracts...): the business logic. It never depends on the delivery ring, of its own module or of any other.

Only app-wide wiring stays outside `app/Domain`: panel providers, global middleware, layouts and screens that combine several modules.

## Layout

```
app/
├── Domain/
│   ├── {Context}/                 # e.g. Ordering, Billing, Customers, Catalog, Inventory
│   │   ├── Actions/               # one use case per class, the only place that writes
│   │   ├── Contracts/             # behavioral contracts this context needs (ports), implemented elsewhere
│   │   ├── DTO/                   # readonly input/output data objects
│   │   ├── Database/
│   │   │   ├── Factories/         # model factories for this context
│   │   │   └── Seeders/
│   │   ├── Enums/                 # backed enums for finite states
│   │   ├── Events/                # domain events, past tense
│   │   ├── Exceptions/            # domain exceptions with meaningful names
│   │   ├── Http/                  # the module's delivery layer (outer shell)
│   │   │   ├── Controllers/       # thin controllers, one Action or Query per method
│   │   │   ├── Requests/          # Form Requests: validation only, build the DTO
│   │   │   ├── Resources/         # Eloquent API Resources
│   │   │   ├── Middleware/        # only middleware specific to this module
│   │   │   ├── routes.php         # this module's web routes (web middleware)
│   │   │   └── api.php            # this module's API routes, if any (api middleware, /api prefix)
│   │   ├── Jobs/                  # queued work, delegates writes to Actions
│   │   ├── Listeners/             # react to other contexts' events, delegate writes to Actions
│   │   ├── Livewire/              # delivery: this module's Livewire components
│   │   ├── Models/                # Eloquent models owned by this context
│   │   ├── Queries/               # read-only query objects
│   │   ├── Services/              # stateless domain computations, no persistence
│   │   ├── Settings/              # typed settings for this context (if used)
│   │   ├── StateMachines/         # allowed state transitions
│   │   └── {AggregateRoot}.php    # only when the context has real invariants
│   └── Shared/                    # shared kernel: value objects, enums and services used by many contexts
├── Http/                          # only app-wide pieces: global middleware
├── Livewire/                      # only components that span several modules (layout, global search)
├── Filament/                      # admin panel, organized by context
│   ├── Resources/{Context}/       # e.g. Resources/Ordering/Orders/OrderResource.php
│   ├── Pages/{Context}/           # context pages; cross-module pages (Dashboard) at the root
│   ├── Widgets/{Context}/         # context widgets; cross-module widgets at the root
│   └── Navigation/                # navigation groups, shared panel helpers
├── Models/User.php                # the only model outside app/Domain
└── Providers/                     # bindings of contracts and event listeners
routes/
├── web.php                        # app-wide routes only (home, locale switch, redirects)
└── console.php
```

Register module routes once in `bootstrap/app.php`; a new module's routes are then picked up with no extra wiring. Web and API routes are separate files, so API routes never get the session and CSRF of the `web` group:

```php
use Illuminate\Support\Facades\Route;

->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function (): void {
        foreach (glob(base_path('app/Domain/*/Http/routes.php')) ?: [] as $routes) {
            Route::middleware('web')->group($routes);
        }

        foreach (glob(base_path('app/Domain/*/Http/api.php')) ?: [] as $routes) {
            Route::middleware('api')->prefix('api')->name('api.')->group($routes);
        }
    },
)
```

Use a route name prefix per module (`ordering.orders.show`). In `api.php` declare the version: `Route::prefix('v1')->name('v1.')->group(...)` (see "API Conventions").

Create only the sub-folders a context actually needs (YAGNI). An empty `StateMachines/` folder is noise.

## Rules

- **Models belong to a context.** `App\Models` contains only `User`. Every new Eloquent model goes into `app/Domain/{Context}/Models`.
- **The module core does not know about delivery.** The core never uses `Request`, sessions, responses, Filament or Livewire, and never uses its own or another module's `Http`, `Livewire` or `Filament` namespace. The dependency goes one way: `Http/Livewire/Filament -> Actions/Queries -> Models`, never the opposite. Eloquent, events, queues and other Laravel core features are allowed everywhere.
- **Presentation is thin.** Module controllers, Livewire components, Filament resources and console commands validate input (Form Requests or `validate()`), authorize (policies), build a DTO, call one Action or Query, and return a response. No business rules there.
- **Delivery serves its own module.** A controller or Livewire component in `Ordering`, or a Filament resource in `app/Filament/Resources/Ordering`, calls Ordering's Actions and Queries. If a screen needs data from several modules, read it through the other modules' Queries; never call another module's Actions from delivery code. A screen that is really about several modules (e.g. the admin dashboard) lives at the root of `app/Filament/Pages` or in the app-wide `app/Livewire`.
- **Filament follows the contexts without leaving `app/Filament`.** Put every resource, page and widget under the subfolder of its context (`Resources/Ordering/`, `Widgets/Billing/`); the panel's standard `discoverResources()`/`discoverPages()`/`discoverWidgets()` scan subfolders, so nothing else changes. When you generate with `make:filament-*`, give the class its context namespace (or move it right after) so it never stays at the root by mistake. Navigation groups mirror the contexts.
- **Livewire components live in their module.** Class-based components go in `app/Domain/{Context}/Livewire` (generators write to `app/Livewire`: move the class right after) and are registered in a service provider. Livewire 4 single-file or multi-file components live with the views, in `resources/views/{context}/`, under a per-context namespace. The registration API differs between Livewire 3 and 4: check the installed version with `search-docs`. Blade views stay in `resources/views/{context}/` so Vite and Tailwind keep scanning one place.
- **Only Actions write.** Calls such as `create()`, `save()`, `update()`, `delete()`, `updateOrCreate()` happen inside Actions (or aggregate root methods). Jobs and Listeners delegate writes to Actions. Presentation code never calls Eloquent mutators directly.
- **Aggregates protect invariants.** When a group of models has cross-record rules (e.g. a stock ledger whose reservations and movements must never take stock below zero), expose a single aggregate root class with intention-revealing methods (`reserve()`, `release()`, `receive()`) and forbid direct writes to its models from outside the context with an architecture test.
- **Plain CRUD stays plain.** Lookup or reference entities without invariants (e.g. `Country`, `Currency`, `TaxRate`, `Role`) may be edited through Filament's standard create and edit pages, with no Action. Code you write that saves or deletes them (a custom table action, a listener) still goes through an Action, as the architecture test requires. Introduce richer Actions only when an invariant appears.
- **Contexts talk through events and contracts.**
  - To react to something that happened elsewhere, listen to that context's domain event; register listeners in a service provider.
  - To ask another context for something synchronously, define a small interface in the consuming context's `Contracts/` and implement it in the providing context; bind it in a service provider.
  - Never call another context's Actions directly. Reading another context's Models or Queries is allowed only when the architecture test allows it.
- **Shared kernel is small and explicit.** Put in `Domain/Shared` only things truly used by many contexts (money/value objects, common enums, plan/module registry). A cross-cutting service that must run inside the same transaction (e.g. an audit log) may be called directly by every context, but must itself not depend on any other context.
- **Transactions live in Actions.** Wrap multi-step writes in `DB::transaction()` inside the Action. Dispatch events that other contexts react to after commit (`ShouldDispatchAfterCommit` or `DB::afterCommit`) unless the reaction must be atomic with the change.
- **Factories live with their models.** Factories go in `app/Domain/{Context}/Database/Factories`, declare `protected $model = Model::class;` explicitly, and are called directly: `CustomerFactory::new()->create()`. Models do not use the `HasFactory` trait, so `Model::factory()` is not available: when a package or generator needs it (some Filament and seeding tools do), add `HasFactory` with `newFactory()` to that model and note the exception in the module README.

## Building blocks

- **Action**: `final readonly class`, constructor injection, one public `execute()` with full types; takes a DTO or scalar ids, never a `Request`; wraps multi-step writes in `DB::transaction()`; throws a named domain exception instead of returning `false`/`null`.
- **DTO**: `final readonly class` with promoted public properties.
- **Controller**: single-action (`__invoke`), final, in `Http/Controllers`; calls one Action or Query.
- **Form Request**: validation only, no `authorize()` (the controller calls `Gate::authorize()`); `toData()` builds the DTO, so the Action never sees a `Request`.
- **Query**: reads and returns data, never writes; `query()`, selected columns, eager loading.

Follow the `domain-building-blocks` skill for complete, annotated examples of each.

### Contract (port) between contexts: a behavioral contract

A contract between modules is an interface in the consuming context's `Contracts/` (suffix `Contract`) whose PHPDoc states the **behavior** every implementation must honor: `@pre`, `@post`, `@throws` and `@event` on every public method. A shared contract test (`tests/Feature/Contracts/{Context}/{ContractName}Test.php`) runs the same expectations on the real implementation and on every fake through a Pest dataset; fakes live in `tests/Fakes/{Context}/`. Changing behavior is a breaking change that needs an ADR.

Before writing or changing a contract, its implementations or its fakes, follow the `behavioral-contracts` skill: it has the full rules, an annotated example and the contract test template.

## Architecture tests are the source of truth

`tests/Unit/ArchitectureTest.php` (published by `php artisan agents:install`) enforces these rules: module core (every folder but `Http` and `Livewire`, plus `Domain/Shared`) independent from delivery, thin final controllers that extend no base class, Form Requests in the module `Http` layer without `authorize()`, final readonly Actions with `execute()`, readonly DTOs, final events, enums, exceptions, contracts with `@post` and a contract test, Filament grouped by context, no Eloquent writes in delivery code, Jobs or Listeners, at most `User` in `App\Models`.

Keep it up to date. When you add a context, a contract or an invariant, extend the arch rules in the same change, and run it before moving models or adding contexts:

```php
arch('Inventory models are only mutated inside Inventory')
    ->expect('App\Domain\Inventory\Models')
    ->toOnlyBeUsedIn('App\Domain\Inventory');

arch('Billing never calls Ordering actions, it reacts to its events')
    ->expect('App\Domain\Billing')
    ->not->toUse('App\Domain\Ordering\Actions');
```

Give each `arch()` rule a single target namespace: with a list of targets, `not->toUse()` can pass even when one of them violates the rule. To apply a rule to the same layer of every module, loop over `domainLayer('Actions')` as the published test does.
