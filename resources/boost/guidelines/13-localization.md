# Localization

No user-facing text is hardcoded. Source strings are English, translations live in `lang/`.

## Rules

- Every user-facing string (Blade, Livewire, Filament labels and helper texts, notifications, mail, validation messages, enum `label()`, exception messages shown to users, API error messages) is written in English and wrapped in `__('...')` (or `trans_choice()` for plurals).
- English source text is the key, stored in `lang/{locale}.json` for each supported locale. Add the translation for every new key **in the same change**, for every locale, even when the text is identical.
- Keep JSON files sorted case-insensitively and encoded with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`.
- One English key maps to exactly one meaning. If the same English word needs different translations in different contexts, use a more specific English key, never a suffix hack like `Open (f)`.
- Reuse an existing key when it has the right meaning; never change an existing key's translation as a side effect.
- Use placeholders, never concatenation: `__('Link sent to :email.', ['email' => $email])`.
- Stable machine codes (API `code`, enum backed values, permission names, log messages) are **not** translated.
- Domain enums translate inside `label()`: `return __(match ($this) { self::Confirmed => 'Confirmed', ... });`.
- Filament static label properties cannot call `__()`: override the getter (`getNavigationLabel()`, `getModelLabel()`, ...).
- Dates and numbers follow the app locale (`Number::format()`, `Number::currency()`, Carbon `isoFormat()`); never force a locale in code. Jobs and commands set the locale explicitly from the recipient or the default.
- Notifications use the recipient's locale (`HasLocalePreference` on the notifiable, or `->locale()`).

## Enforcement

Add a test that scans the source (Blade, Filament, Livewire, Domain) for `__('...')` keys and asserts that each one exists in every `lang/{locale}.json`. Run it with the rest of the suite.
