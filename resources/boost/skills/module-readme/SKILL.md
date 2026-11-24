---
name: module-readme
description: "Use when creating a bounded context in app/Domain/{Context}, or when a change adds, changes or removes one of its Actions, events, contracts, aggregates or entry points: the module README template and what to keep in it."
license: MIT
metadata:
  author: rslanzi
---

# Module README

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

- Provided: `StockReservationContract` for Ordering: reserves stock atomically, returns null without side effects when stock is short.
- Required: `SmsGatewayContract` (sends one message or throws `SmsNotSentException`), bound in `OrderingServiceProvider`.

## Entry points

- HTTP routes (`Routes/web.php`, `Routes/api.php`), Livewire components, Filament resources in `app/Filament/*/{Context}`, scheduled jobs.

## Decisions

- ADR-0003, ADR-0007.
```
