# Modular Architecture (DDD-style bounded contexts)

The application is organized by business capability, not by file type. Each module is a bounded context under `app/Domain/{Context}`. Each module also owns its delivery layer: HTTP (controllers, Form Requests, API Resources, routes) in `app/Domain/{Context}/Http`, and Livewire components in `app/Domain/{Context}/Livewire`. Everything about a capability lives in one folder.

Filament is the exception: it stays in `app/Filament` so that panel discovery and the `make:filament-*` generators keep working unchanged, but its content is organized by the same contexts (`app/Filament/Resources/{Context}/...`).

Inside a module there are two rings:

- **Delivery** (`Http/`, `Livewire/`, plus the context's folder in `app/Filament`): the outer shell. It validates, authorizes, builds DTOs and calls Actions and Queries.
- **Core** (everything else: Actions, Models, Services, Queries, Events, Listeners, Jobs, DTO, Enums, Contracts...): the business logic. It never depends on the delivery ring, of its own module or of any other.

Only app-wide wiring stays outside `app/Domain`: panel providers, the base controller, global middleware, layouts and screens that combine several modules.

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
│   │   │   ├── Requests/          # Form Requests: validation + authorization, build the DTO
│   │   │   ├── Resources/         # Eloquent API Resources
│   │   │   ├── Middleware/        # only middleware specific to this module
│   │   │   └── routes.php         # this module's routes
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
├── Http/                          # only app-wide pieces: base Controller, global middleware
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

Register module routes in `bootstrap/app.php`, so adding a module means adding one line:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    then: function (): void {
        foreach (glob(dirname(__DIR__, 2) . '/app/Domain/*/Http/routes.php') as $routes) {
            Route::middleware('web')->group($routes);
        }
    },
)
```

Use a route name prefix per module (`ordering.orders.show`) and API routes under `Route::prefix('api/v1')->middleware('api')` inside the module's `routes.php` when needed.

Create only the sub-folders a context actually needs (YAGNI). An empty `StateMachines/` folder is noise.

## Rules

- **Models belong to a context.** `App\Models` contains only `User`. Every new Eloquent model goes into `app/Domain/{Context}/Models`.
- **The module core does not know about delivery.** The core never uses `Request`, sessions, responses, Filament or Livewire, and never uses its own or another module's `Http`, `Livewire` or `Filament` namespace. The dependency goes one way: `Http/Livewire/Filament -> Actions/Queries -> Models`, never the opposite. Eloquent, events, queues and other Laravel core features are allowed everywhere.
- **Presentation is thin.** Module controllers, Livewire components, Filament resources and console commands validate input (Form Requests or `validate()`), authorize (policies), build a DTO, call one Action or Query, and return a response. No business rules there.
- **Delivery serves its own module.** A controller or Livewire component in `Ordering`, or a Filament resource in `app/Filament/Resources/Ordering`, calls Ordering's Actions and Queries. If a screen needs data from several modules, read it through the other modules' Queries; never call another module's Actions from delivery code. A screen that is really about several modules (e.g. the admin dashboard) lives at the root of `app/Filament/Pages` or in the app-wide `app/Livewire`.
- **Filament follows the contexts without leaving `app/Filament`.** Put every resource, page and widget under the subfolder of its context (`Resources/Ordering/`, `Widgets/Billing/`); the panel's standard `discoverResources()`/`discoverPages()`/`discoverWidgets()` scan subfolders, so nothing else changes. When you generate with `make:filament-*`, give the class its context namespace (or move it right after) so it never stays at the root by mistake. Navigation groups mirror the contexts.
- **Livewire components live in their module.** Generators write to `app/Livewire`: after generating, move the class into `app/Domain/{Context}/Livewire` and register the module's components in a service provider (namespace or explicit `Livewire::component()`); check the installed Livewire version with `search-docs` for the right API. Blade views stay in `resources/views/{context}/` so Vite and Tailwind keep scanning one place.
- **Only Actions write.** Calls such as `create()`, `save()`, `update()`, `delete()`, `updateOrCreate()` happen inside Actions (or aggregate root methods). Jobs and Listeners delegate writes to Actions. Presentation code never calls Eloquent mutators directly.
- **Aggregates protect invariants.** When a group of models has cross-record rules (e.g. a stock ledger whose reservations and movements must never take stock below zero), expose a single aggregate root class with intention-revealing methods (`reserve()`, `release()`, `receive()`) and forbid direct writes to its models from outside the context with an architecture test.
- **Plain CRUD stays plain.** Lookup or reference entities without invariants (e.g. `Country`, `Currency`, `TaxRate`, `Role`) may be edited directly by admin resources. Introduce Actions only when an invariant appears.
- **Contexts talk through events and contracts.**
  - To react to something that happened elsewhere, listen to that context's domain event; register listeners in a service provider.
  - To ask another context for something synchronously, define a small interface in the consuming context's `Contracts/` and implement it in the providing context; bind it in a service provider.
  - Never call another context's Actions directly. Reading another context's Models or Queries is allowed only when the architecture test allows it.
- **Shared kernel is small and explicit.** Put in `Domain/Shared` only things truly used by many contexts (money/value objects, common enums, plan/module registry). A cross-cutting service that must run inside the same transaction (e.g. an audit log) may be called directly by every context, but must itself not depend on any other context.
- **Transactions live in Actions.** Wrap multi-step writes in `DB::transaction()` inside the Action. Dispatch events that other contexts react to after commit (`ShouldDispatchAfterCommit` or `DB::afterCommit`) unless the reaction must be atomic with the change.
- **Factories live with their models.** Factories go in `app/Domain/{Context}/Database/Factories`, declare `protected $model = Model::class;` explicitly, and are called directly: `CustomerFactory::new()->create()`. Models do not use the `HasFactory` trait.

## Building blocks

### Action

```php
<?php

declare(strict_types=1);

namespace App\Domain\Ordering\Actions;

use App\Domain\Ordering\DTO\ConfirmOrderData;
use App\Domain\Ordering\Enums\OrderStatus;
use App\Domain\Ordering\Events\OrderConfirmed;
use App\Domain\Ordering\Models\Order;
use App\Domain\Ordering\StateMachines\OrderStateMachine;
use Illuminate\Support\Facades\DB;

final readonly class ConfirmOrderAction
{
    public function __construct(
        private OrderStateMachine $stateMachine,
    ) {}

    public function handle(ConfirmOrderData $data): Order
    {
        return DB::transaction(function () use ($data): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($data->orderId);

            $this->stateMachine->transition($order, OrderStatus::Confirmed);

            OrderConfirmed::dispatch($order->id);

            return $order;
        });
    }
}
```

- `final readonly class`, dependencies injected through the constructor, a single public `handle()` method with full type hints.
- Accept a DTO (or scalar ids) instead of a `Request` or an array of mixed input.
- Throw a named domain exception when a business rule is violated; never return `false` or `null` to signal failure.

### DTO

```php
final readonly class ConfirmOrderData
{
    public function __construct(
        public int $orderId,
        public ?string $note = null,
    ) {}
}
```

### Controller and Form Request (inside the module)

```php
namespace App\Domain\Ordering\Http\Controllers;

final class ConfirmOrderController
{
    public function __invoke(
        ConfirmOrderRequest $request,
        ConfirmOrderAction $confirmOrder,
    ): RedirectResponse {
        $order = $confirmOrder->handle($request->toData());

        return to_route('ordering.orders.show', $order);
    }
}
```

```php
namespace App\Domain\Ordering\Http\Requests;

final class ConfirmOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('confirm', $this->route('order'));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:500']];
    }

    public function toData(): ConfirmOrderData
    {
        return new ConfirmOrderData(
            orderId: $this->route('order')->id,
            note: $this->validated('note'),
        );
    }
}
```

The Form Request turns HTTP input into a DTO, so the Action never sees a `Request`. Single-action controllers (`__invoke`) are preferred; use resource controllers only for plain CRUD.

### Query

A Query class reads and returns data (a model, a collection, a number, a DTO). It never writes and has no side effects. Use `query()`, select the columns you need, and eager load relations.

### Contract (port) between contexts: a behavioral contract

A contract between modules is more than a method signature. Its PHPDoc states the **behavior** every implementation must honor, and a shared contract test proves it. Callers depend on that behavior, so it is part of the module's public API.

```php
namespace App\Domain\Ordering\Contracts;

use App\Domain\Ordering\Exceptions\UnknownProductException;

/**
 * What Ordering needs from stock management. Implemented by the Inventory context.
 */
interface StockReservationContract
{
    /**
     * Reserves units of a product for an order.
     *
     * @pre  $quantity > 0. Called inside the caller's database transaction.
     * @post On success, available stock is lower by $quantity until release() is called.
     * @post On null, nothing has changed (no partial reservation).
     * @return int|null the reservation id, or null when available stock is lower than $quantity
     *
     * @throws UnknownProductException when the product does not exist
     *
     * @event StockReserved dispatched after commit, on success only
     */
    public function reserve(int $productId, int $quantity): ?int;

    /**
     * Gives the reserved units back to available stock.
     *
     * @pre  $reservationId was returned by reserve().
     * @post Available stock is higher by the reserved quantity. Calling it twice has no further effect (idempotent).
     *
     * @event StockReleased dispatched after commit, the first time only
     */
    public function release(int $reservationId): void;
}
```

Every public method of a `Contract` documents:

- **Preconditions** (`@pre`): what the caller must guarantee (valid ranges, existing records, running inside a transaction).
- **Postconditions** (`@post`, `@return`): what is true afterwards, including what does *not* change on failure, idempotency, and ordering guarantees.
- **Exceptions** (`@throws`): every domain exception the caller must handle, and when. Implementations never throw vendor or infrastructure exceptions through a contract: they wrap them in a module exception.
- **Side effects** (`@event`): events dispatched, and whether before or after commit; any other observable effect (mail, queue, external call).

Rules:

- **One contract test per contract, run against every implementation.** Put it in `tests/Feature/Contracts/{Context}/{ContractName}Test.php` and run the same expectations on the real implementation and on every fake through a Pest dataset. A fake that does not pass the contract test is a bug: tests that use it prove nothing.

  ```php
  dataset('stock reservation implementations', [
      'inventory' => fn (): StockReservationContract => app(InventoryStockReservation::class),
      'fake' => fn (): StockReservationContract => new FakeStockReservation(),
  ]);

  it('returns null and changes nothing when stock is insufficient', function (StockReservationContract $stock): void {
      $product = ProductFactory::new()->withStock(2)->create();

      expect($stock->reserve($product->id, 3))->toBeNull()
          ->and(StockLevel::for($product->id))->toBe(2);
  })->with('stock reservation implementations');

  it('releases a reservation only once', function (StockReservationContract $stock): void {
      // ...
  })->with('stock reservation implementations');
  ```

- **Fakes live in `tests/Fakes/{Context}/`** and implement the contract, never the concrete class. Use them in other modules' tests instead of mocks of the interface.
- **Changing behavior is a breaking change.** Strengthening a precondition, weakening a postcondition, adding an exception or changing which events fire requires an ADR and updating every implementation, fake and caller in the same change. Adding a new method or relaxing a precondition is backward compatible but still updates the contract test.
- **The module README lists its contracts** (provided and required) with a one-line summary of their behavior.

Bind each contract in `AppServiceProvider` (or a dedicated provider) and provide a null implementation, which also passes the contract test, when the providing module is disabled.

## Architecture tests are the source of truth

`tests/Unit/ArchitectureTest.php` (published by `php artisan agents:install`) enforces these rules: module core independent from delivery, thin final controllers, Form Requests in the module `Http` layer, final readonly Actions with `handle()`, readonly DTOs, enums, exceptions, Filament grouped by context, no Eloquent writes in delivery code, Jobs or Listeners, only `User` in `App\Models`.

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
