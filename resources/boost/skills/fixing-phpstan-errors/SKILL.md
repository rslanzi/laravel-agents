---
name: fixing-phpstan-errors
description: "Use when PHPStan or Larastan reports errors in a Laravel project: the usual causes (collection and relation generics, nullable users, config values, __() return types, model attributes) and how to fix them without ignores or casts."
license: MIT
metadata:
  author: rslanzi
---

# Fixing PHPStan Errors in Laravel

Fix the cause; never add `@phpstan-ignore`, baseline entries or a lower level (see "Quality Tooling").

- Collections and relations: add generics in PHPDoc (`@return Collection<int, Order>`, `@return BelongsTo<Customer, $this>`).
- `__()` returns `array|string`: return it from a method typed `: string` instead of mixing it with `null` in a ternary.
- `$request->user()` can be null: authorize first, or use `$request->user()` behind a route with `auth` and assert with a typed helper.
- Config values: cast at the edge (`(int) config('x.y')`) or use `Config::integer()`, `Config::string()`.
- Model attributes: document them with `@property` PHPDoc only when Larastan cannot infer them from migrations.
