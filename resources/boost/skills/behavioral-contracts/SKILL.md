---
name: behavioral-contracts
description: "Use when creating or changing an interface in app/Domain/{Context}/Contracts, one of its implementations or fakes, or its contract test: how to document preconditions, postconditions, exceptions and events, and how to prove every implementation honors them."
license: MIT
metadata:
  author: rslanzi
---

# Behavioral Contracts Between Modules

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

The implementation in the providing context delegates its writes to that context's Actions, like any other delivery or async code.

Bind each contract in `AppServiceProvider` (or a dedicated provider) and provide a null implementation, which also passes the contract test, when the providing module is disabled.
