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

            // OrderConfirmed implements ShouldDispatchAfterCommit: listeners run only if the transaction commits.
            OrderConfirmed::dispatch($order->id);

            return $order;
        });
    }
}
```

- `final readonly class`, dependencies injected through the constructor, a single public `handle()` method with full type hints.
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
        return $this->user()?->can('confirm', $this->order()) ?? false;
    }

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

The Form Request turns HTTP input into a DTO, so the Action never sees a `Request`. Single-action controllers (`__invoke`) are preferred; use resource controllers only for plain CRUD.

## Query

A Query class reads and returns data (a model, a collection, a number, a DTO). It never writes and has no side effects. Use `query()`, select the columns you need, and eager load relations.
