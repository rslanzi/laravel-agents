# Module Documentation

Every bounded context has a `README.md` at its root (`app/Domain/{Context}/README.md`). It is the module's contract with the rest of the application and the first file an agent reads before touching the module. This is an explicit exception to "only create documentation files if requested".

## Keep it current

- Create it when the module is created; update it **in the same change** whenever a public element in the template below is added, changed or removed.
- Keep it short (one or two screens). Link ADRs instead of repeating their reasoning. Do not list every class: only what other modules or developers need to know.

## Template

```markdown
# {Context}

One paragraph: the business capability this module owns and what it explicitly does not own.

## Ubiquitous language

- **Term**: meaning in this context (and how it differs from the same word elsewhere, if relevant).

## Aggregates and invariants

- `AggregateRoot`: the rules it protects (e.g. "stock can never go below zero").

## Use cases (Actions)

- `ConfirmOrderAction`: what it does, who can trigger it.

## Published events

- `OrderConfirmed`: when it fires, payload, known listeners in other modules.

## Consumed events

- `InvoiceIssued` (Billing): what this module does in response.

## Contracts

- Provided: `StockReservationContract` implemented here for Ordering.
- Required: `SmsGatewayContract`, bound in `AppServiceProvider`.

## Entry points

- HTTP routes (`Http/routes.php`), Livewire components, Filament resources in `app/Filament/*/{Context}`, scheduled jobs.

## Decisions

- ADR-0003, ADR-0007.
```
