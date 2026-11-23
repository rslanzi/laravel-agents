---
name: api-conventions
description: "Use when adding or changing a JSON API endpoint, an API Resource, API error handling or pagination, filtering and sorting parameters: routes and versioning, request and response shapes, status codes, the error format and the required tests."
license: MIT
metadata:
  author: rslanzi
---

# API Conventions

Applies to JSON APIs exposed by modules (`app/Domain/{Context}/Http`). Follow existing project conventions if an API already exists.

## Routes and versioning

- API routes live in the module's `Http/api.php`, which `bootstrap/app.php` loads with the `api` middleware, the `/api` prefix and the `api.` name prefix (see "Modular Architecture"). Inside it, version every route: `Route::prefix('v1')->name('v1.')->group(...)`, giving `/api/v1/...` and names `api.v1.{context}.{resource}.{action}`.
- Resources are plural nouns in kebab-case (`/api/v1/orders`, `/api/v1/customers/{customer}/addresses`). Nest only one level.
- Use HTTP verbs for actions on resources; for domain operations that are not CRUD use an explicit sub-resource verb: `POST /api/v1/orders/{order}/confirm`.
- A breaking change (removed field, changed type or meaning, new required input) requires a new version (`v2`) living side by side; additive changes (new optional field, new endpoint) stay in the same version.
- Authenticate with Sanctum (tokens or SPA cookies). Apply `throttle:api` (or a named limiter) to every API route.

## Requests

- Validation in a Form Request that builds the DTO (`toData()`), like any other controller.
- Accept and return `snake_case` keys. Dates in ISO 8601 with timezone (`2026-11-22T14:00:00+01:00`), money as integer minor units plus currency (`{"amount": 1250, "currency": "EUR"}`), enums as their backed values.

## Responses

- Always return Eloquent **API Resources** (`JsonResource` / `ResourceCollection`) from the module's `Http/Resources`; never return models or arrays directly. Resources expose an explicit list of fields; hide internal columns.
- Use `whenLoaded()` for relations and `whenCounted()` for counts, so a Resource never triggers a lazy load.
- Status codes: `200` read/update, `201` create (with the Resource body), `202` accepted for queued work, `204` delete or action without body.
- Single resources are wrapped in `data`; collections use Laravel's `data` + `links` + `meta`.

## Pagination, filtering, sorting

- Every collection endpoint is paginated: `?page[number]=` / `?page[size]=` or Laravel's `?page=&per_page=` (pick one per project and keep it). Cap `per_page` (e.g. max 100). Use cursor pagination for large or fast-changing lists.
- Filters: `?filter[status]=confirmed&filter[from]=2026-11-01`, validated against an allow-list.
- Sorting: `?sort=-starts_at,customer_name` (minus for descending), allow-listed columns only.
- Includes: `?include=customer,items`, allow-listed relations only, mapped to `with()`.

## Errors

One error format for the whole API, produced in `bootstrap/app.php` `->withExceptions()`:

```json
{
  "message": "The product is out of stock.",
  "code": "ordering.product_out_of_stock",
  "errors": { "items.0.quantity": ["The product is out of stock."] }
}
```

- `message`: human-readable and translated. `code`: stable, machine-readable, `{context}.{snake_case_reason}`, documented. `errors`: field errors (validation, `422`), omitted otherwise.
- `401` unauthenticated, `403` forbidden, `404` not found (also for resources the user may not see), `409` conflict with current state, `422` validation or domain rule violation, `429` rate limited, `500` never with internal details or stack traces.

## Documentation and tests

- Each endpoint has feature tests for: success shape (`assertJsonStructure` / `assertExactJson` on key fields), authorization (`401`/`403`), validation (`422`), pagination limits.
- Keep the API documented (OpenAPI generated from code, e.g. Scramble, if the project uses it) and update it in the same PR.
