---
name: domain-building-blocks
description: "Use when writing or changing an Action, a DTO, a module controller, a Form Request or a Query class in app/Domain/{Context}: annotated reference implementations and the rules each building block follows."
license: MIT
metadata:
  author: rslanzi
---

# Domain Building Blocks

## Action

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
use Illuminate\Support\Facades\Event;

final readonly class ConfirmOrderAction
{
    public function __construct(
        private OrderStateMachine $stateMachine,
    ) {}

    public function execute(ConfirmOrderData $data): Order
    {
        return DB::transaction(function () use ($data): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($data->orderId);

            $this->stateMachine->transition($order, OrderStatus::Confirmed);

            // OrderConfirmed implements ShouldDispatchAfterCommit: listeners run only if the transaction commits.
            Event::dispatch(new OrderConfirmed($order->id));

            return $order;
        });
    }
}
```

- `final readonly class`, dependencies injected through the constructor, a single public `execute()` method with full type hints. (`handle()` stays the method of jobs, listeners and commands.)
- Accept a DTO (or scalar ids) instead of a `Request` or an array of mixed input.
- Throw a named domain exception when a business rule is violated; never return `false` or `null` to signal failure.

## DTO

```php
final readonly class ConfirmOrderData
{
    public function __construct(
        public int $orderId,
        public ?string $note = null,
    ) {}
}
```

## Controller and Form Request (inside the module)

```php
namespace App\Domain\Ordering\Http\Controllers;

final class ConfirmOrderController
{
    // Route parameters first, then the Request, then other dependencies.
    public function __invoke(
        Order $order,
        ConfirmOrderRequest $request,
        ConfirmOrderAction $confirmOrder,
    ): RedirectResponse {
        Gate::authorize('confirm', $order);

        $confirmOrder->execute($request->toData());

        return redirect()->route('ordering.orders.show', ['order' => $order]);
    }
}
```

```php
namespace App\Domain\Ordering\Http\Requests;

final class ConfirmOrderRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:500']];
    }

    public function toData(): ConfirmOrderData
    {
        return new ConfirmOrderData(
            orderId: $this->order()->id,
            note: $this->string('note')->trim()->toString() ?: null,
        );
    }

    private function order(): Order
    {
        $order = $this->route('order');
        assert($order instanceof Order);

        return $order;
    }
}
```

The Form Request only validates and turns HTTP input into a DTO, so the Action never sees a `Request`. It has no `authorize()` method: the controller authorizes with `Gate::authorize()` against a Policy, before calling the Action. The Form Request validates before the controller runs, so a forbidden user can see validation errors first; when that matters, authorize on the route instead (`->can('confirm', 'order')`), which runs before validation. Controllers are single-action (`__invoke`), final, named after the singular resource, and extend no base class. Routes are declared one by one, never with `Route::resource`.

## Event

```php
final readonly class OrderConfirmed implements ShouldDispatchAfterCommit
{
    public function __construct(public int $orderId) {}
}
```

Dispatch with `Event::dispatch(new OrderConfirmed(...))`. Add `SerializesModels` only when the event carries models to queued listeners, `InteractsWithSockets` only for broadcasting (a broadcast event cannot be `readonly`, because that trait declares a writable property).

## Query

A Query class reads and returns data (a model, a collection, a number, a DTO). It never writes and has no side effects. Use `query()`, select the columns you need, and eager load relations.
