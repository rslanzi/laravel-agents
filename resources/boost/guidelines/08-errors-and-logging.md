# Errors and Logging

## Exceptions

- Business rule violations throw **named domain exceptions** from the module's `Exceptions/` folder (`ProductOutOfStockException`), extending a common base per module or `App\Domain\Shared\Exceptions\DomainException`. Never return `false`/`null` to signal failure, never throw generic `Exception` or `RuntimeException` for business rules.
- Domain exceptions carry a user-safe message (translated with `__()`) and the data needed to explain the failure, never secrets or personal data.
- Map exceptions to HTTP responses in one place, `bootstrap/app.php` `->withExceptions()`:
  - domain exceptions to `422` (or `409` for conflicts) with the safe message, JSON for API requests and a flash/validation error for web;
  - `ModelNotFoundException` to `404`, `AuthorizationException` to `403`, handled by Laravel by default.
- Controllers and Livewire components do not catch domain exceptions just to rethrow or log them. Catch only when you can recover or show a specific message (Livewire: `addError()` or a notification; Filament: `Notification::make()->danger()` and `halt()`).
- Never swallow exceptions (`catch (Throwable) {}`). If you really must continue, `report($e)` and explain why in a comment.
- External services: wrap their client errors in a module exception (`PaymentGatewayUnavailableException`) at the `Contract` implementation, so the core never depends on vendor exception classes. Use timeouts and retries (`Http::timeout()->retry()`) on every outbound call.
- Use `report()` for errors you handle but want to know about, `abort()`/`abort_if()` only in the delivery layer, never in the core.

## Logging

- Log through the `Log` facade with a short, constant message and structured context: `Log::warning('Payment webhook rejected', ['provider' => 'stripe', 'reason' => $reason, 'order_id' => $id])`. Do not interpolate values into the message.
- Choose the level deliberately: `debug` for diagnostic detail, `info` for business events worth tracing, `warning` for recoverable anomalies, `error` for failures that need attention, `critical` and above for outages.
- Add shared context once (`Log::withContext()` or `Context::add()` in middleware/jobs: request id, user id, tenant) instead of repeating it on every line.
- Never log passwords, tokens, API keys, full card data, sensitive personal data (health, financial, biometric) or whole request payloads. Log ids, not records.
- Use a dedicated channel for noisy integrations (`Log::channel('payments')`) when it helps operations.
- Business audit trails (who changed what) are domain data stored by an Action in the database, not log lines.

## Testing errors

- Test every domain exception path (`->throws(SomeException::class)`), and that the delivery layer turns it into the right status code or message.
- Use `Exceptions::fake()` / `Exceptions::assertReported()` to verify that handled errors are reported, and `Log::spy()` only when the log line itself is a requirement.
