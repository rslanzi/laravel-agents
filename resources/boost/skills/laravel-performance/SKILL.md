---
name: laravel-performance
description: "Use when writing list, report, export or import code, queries over tables that grow, queued jobs, caching, or heavy Livewire and Filament screens: database, queue, cache and frontend performance rules and how to test them."
license: MIT
metadata:
  author: rslanzi
---

# Performance

Measure before optimizing (KISS, YAGNI), but never ship the well-known mistakes below.

## Database

- **No N+1.** Eager load with `with()`/`load()`; use `withCount()`, `withSum()`, `withExists()` instead of counting relations in loops. `Model::preventLazyLoading()` is on outside production, so a lazy load fails the tests.
- **Select only what you need** on lists, reports and exports (`select([...])`).
- **Paginate** every list that can grow (`paginate()`, `cursorPaginate()` for large or infinite lists). Never `->get()` an unbounded table.
- **Process large sets in chunks**: `chunkById()`, `lazyById()` or `cursor()`, never `all()` on tables that grow.
- **Index** every foreign key and every column used in `where`, `orderBy` or unique checks on large tables; add composite indexes that match real queries. Add the index in the same migration as the column.
- **Let the database do the work**: aggregate with SQL (`sum`, `count`, `groupBy`) instead of loading models into collections.
- **Bulk writes**: use `insert()`/`upsert()` or a single query for many rows, inside an Action, and remember that they skip model events.
- **Avoid `whereHas` on huge tables** when a join or `whereIn` subquery is clearer and faster; check with `explain()` when in doubt.

## Work outside the request

- Send mail, notifications, PDFs, exports, imports and third-party API calls through queued Jobs (`ShouldQueue`). The request only dispatches.
- Jobs are idempotent, set `$tries`, `$backoff` and `$timeout`, and use `ShouldBeUnique` when duplicates would cause harm.
- Use `Bus::batch()` or chains for multi-step processing, and `afterCommit` dispatch when the job reads data written in the same transaction.

## Caching

- Cache expensive, read-mostly computations behind a Query class (`Cache::remember()` with an explicit TTL and a key that includes every parameter, tenant and locale).
- Invalidate in the Action that changes the data (or in a listener of its event), never rely only on TTL for correctness.
- Do not cache per-user sensitive data in shared keys.
- In production run `php artisan optimize` (config, routes, views, events cached); this is why `env()` is only read in `config/`.

## Frontend and Livewire

- Keep Livewire public properties small (ids and scalars, not big collections); use computed properties (`#[Computed]`) for derived data, and lazy loading (`#[Lazy]`) for heavy components.
- Debounce inputs that trigger queries (`wire:model.live.debounce.300ms`).
- Use Filament table pagination and deferred loading; avoid computed columns that run a query per row.

## Testing performance

- Assert query counts for critical lists when a regression would hurt (`DB::enableQueryLog()` or `expect(fn () => ...)->toQueryCount(n)` if available).
- Seed realistic volumes locally before optimizing; do not optimize based on an empty database.
