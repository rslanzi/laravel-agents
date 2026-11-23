# Security

## Application defaults

Configure once in `AppServiceProvider` and never bypass elsewhere:

- `DB::prohibitDestructiveCommands(app()->isProduction())`.
- Strong `Password::defaults()` in production (min length, mixed case, numbers, symbols, `uncompromised()`).
- `Model::shouldBeStrict(! app()->isProduction())` (lazy loading, silently discarded attributes, missing attributes).
- HTTPS forced in production (`URL::forceHttps()`), secure and `SameSite` cookies in `config/session.php`.

## Checklist for every change

- **Authorization**: every controller action, Livewire action and Filament resource is covered by a Policy or Gate (`authorize()` in Form Requests, `$this->authorize()` in Livewire, policies on Filament resources). Test the forbidden case, not only the allowed one. Scope queries to what the user may see (tenant, team, owner), never only hide it in the UI.
- **Never trust ids from the client.** Resolve models through route model binding scoped to the user (`scopeBindings()`, relation-scoped queries) and authorize them.
- **Mass assignment**: list fillable attributes explicitly (allow-list, never `$guarded = []`); never pass `$request->all()` to `create()`/`update()`. Build DTOs from `validated()` data only.
- **Validation**: validate every input, including query parameters, file uploads and webhook payloads. Prefer allow-lists (`Rule::in(Enum::cases())`, `Rule::enum()`). Never build rules from user input (`'unique:users,email,'.$request->id`).
- **Output**: Blade escapes with `{{ }}`; use `{!! !!}` only for HTML cleaned with an HTML purifier, and say why in a comment. Custom Blade directives escape with `e()`. User-supplied links allow only `http`/`https`.
- **CSRF**: every state-changing web route keeps CSRF protection; only API routes (token-authenticated) and signed webhooks are exempt.
- **Raw SQL**: only with bindings (`whereRaw('x = ?', [$value])`), never string interpolation. Column names from user input (`orderBy`, filters) must come from an allow-list.
- **File uploads**: validate type (`mimes`, `mimetypes`) and size, store on a private disk with generated names, serve through an authorized route or temporary signed URLs.
- **Rate limiting**: apply `throttle` to login, registration, password reset, OTP, public forms and public APIs.
- **Signed URLs** for links sent by email or that grant access without login (`URL::temporarySignedRoute()`), and verify them with the `signed` middleware.
- **Webhooks**: verify the provider's signature before processing, and make handlers idempotent.
- **Secrets**: only in `.env`/secret managers, read through `config()`. Never hardcode, never commit, never log.
- **Personal and sensitive data**: collect only what is needed, encrypt sensitive columns with the `encrypted` cast, keep it out of logs, exception context, URLs and analytics.
- **Dependencies**: run `composer audit` and `npm audit` when you add or update packages, and report findings.
