---
name: laravel-conventions
description: "Use when writing or changing routes, controllers, Eloquent models, scopes or query builders, factories, Artisan commands, events, jobs, Blade views or config files: the project's Laravel conventions with examples."
license: MIT
metadata:
  author: rslanzi
---

# Laravel Conventions

These conventions follow the IxDF Laravel, PHP and SQL handbook, adapted to the modular layout of "Modular Architecture". Where the two differ, the guidelines win.

## Routes

```php
Route::get('/order-lines/{orderLine}', ShowOrderLineController::class)->name('ordering.orderLines.show');
Route::post('/orders/{order}/confirm', ConfirmOrderController::class)
    ->middleware(['auth', 'throttle:orders'])
    ->name('ordering.orders.confirm');
```

- The HTTP verb comes first; name, middleware and other options follow it.
- Controllers are referenced by class: `Controller::class` for an invokable one, `[Controller::class, 'index']` otherwise. Never `'Controller@index'`.
- No `Route::resource()` or `Route::apiResource()`: declare each route, so unused routes do not exist and every URL is searchable.
- URLs are kebab-case, route parameters and route names camelCase, middleware lists are arrays.
- Every HTML route has a name. Build URLs with `route()`: one parameter as a model or id (`route('ordering.orders.show', $order)`), several as a keyed array in the route's order (`['team' => $team, 'member' => $member]`).

## Controllers

- Single-action (`__invoke`), `final`, named after the singular resource (`ShowOrderController`), extending no base class. Shared behavior goes in middleware or injected services.
- Parameter order: route parameters or bound models, then the Request, then other dependencies.
- Return explicit responses: `redirect()->route(...)`, `redirect()->to($url)`, never `redirect($url)`.
- `abort()` always gets a message: `abort(404, "Order {$id} not found.")`.
- Read input with `$request->input()`, `string()`, `integer()`, `boolean()`, `enum()`; never `get()`.
- Return a small, consistent set of status codes and let the exception handler map domain exceptions to them.

## Eloquent models

```php
/**
 * @property-read Customer $customer
 * @property-read Collection<int, OrderLine> $lines
 */
#[Fillable(['note'])]
final class Order extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'lines_count' => 0,
        'status' => OrderStatus::Draft->value,
    ];

    public function newEloquentBuilder($query): OrderBuilder
    {
        return new OrderBuilder($query);
    }
}

/** @extends Builder<Order> */
final class OrderBuilder extends Builder
{
    public function confirmed(): self
    {
        return $this->where('status', OrderStatus::Confirmed);
    }
}
```

- Start every query with `Model::query()`. No `whereEmail()`-style magic: use `where('email', ...)`.
- Give attributes safe defaults in `$attributes`, so a new model is valid before it is saved.
- Document relations with `@property-read` and scopes with `@method`. With three or more scopes, move them into a custom Builder class like the one above.
- A scope reused across models is an invokable class applied with `tap()`: `Order::query()->tap(new CreatedBetween($from, $to))`.
- In Actions, assign attributes one by one from the DTO (`$order->note = $data->note;`). `fill()` only takes validated, listed fields; never `forceFill($request->all())`.
- For large datasets use `chunkById()` or `lazyById()`, eager load relations, index the columns you filter and sort on, and cache expensive, frequent queries with an explicit TTL. For heavy reports the query builder may be clearer than Eloquent.

## Factories

- Factories exist for tests and seeders only, and are called directly: `OrderFactory::new()->confirmed()->create()`.
- `definition()` returns a valid default state.
- Each meaningful state is a method using `state()`: `draft()`, `confirmed()`, `cancelled()`.

## Artisan commands

- Names are kebab-case with a context prefix: `ordering:cancel-expired-orders`.
- Inject dependencies into `handle()`, not the constructor: Laravel builds every command on each `artisan` call.
- Use verbosity levels: plain `info()` for the final summary, `info(..., 'v')` or `'vvv'` for details, so scheduled runs produce output only when something matters.
- Return `self::FAILURE` (or throw) when the command fails, so the scheduler and error handlers notice.
- Commands are thin like controllers: they parse options and call Actions or Queries.

## Events and jobs

- Events are `final` and `readonly` (unless broadcast), dispatched with `Event::dispatch(new OrderConfirmed(...))`, and use only the traits they need.
- Jobs are reentrant (a restarted job completes), idempotent (running twice changes nothing more), safe under concurrency (or `ShouldBeUnique`) and independent of order (or chained with `Bus::chain()`).
- Dispatch jobs with `dispatch(new SendInvoiceJob(...))`.

## Blade

- Pass variables to partials explicitly: `@include('ordering.partials.summary', ['order' => $order])`.
- Every component, and every view that receives data, starts with a PHPDoc block typing its variables:

```blade
<?php
/** @var \App\Domain\Ordering\Models\Order $order */
/** @var \Illuminate\Support\Collection<int, \App\Domain\Ordering\Models\OrderLine> $lines */
?>
```

- Prefer `<?php ?>` to `@php`/`@endphp`.
- `@vite` for scripts and styles, `Vite::asset()` for other assets.

## Config

- One config file per module when it needs settings, prefixed with the application's name (`config/acme_ordering.php`), so custom config never collides with Laravel's or a package's.
- Read it with `config()` (or `Config::integer()` and similar for typed values); `env()` only inside `config/`.

## SQL

- `dateTime()` (or `date()`) for business dates and times: delivery dates, appointments, deadlines. `TIMESTAMP` (`timestamps()`, `softDeletes()`, `timestamp()`) only for record metadata such as created, updated or deleted at.
- `TIMESTAMP` stops at 2038-01-19 and converts with the connection timezone. Never use it for dates that can be far in the future or that the user enters.
- Keep the application and the database connection in UTC, and convert to the user's timezone only for display. With local times, the same wall-clock time happens twice when daylight saving ends.
- In raw SQL, keywords and functions are upper case, and values are always bound:

```php
DB::select('SELECT notification_class, MAX(sent_at) AS last_sent_at FROM notification_logs WHERE user_id = ? GROUP BY notification_class', [$userId]);
```
