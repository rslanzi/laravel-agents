# Localization

No user-facing text is hardcoded. Every string is written in English and wrapped in `__()` (or `trans_choice()`), with its translation added to every `lang/{locale}.json` in the same change. Use placeholders, never concatenation. Machine codes (API `code`, enum values, permission names, log messages) are not translated. `tests/Unit/TranslationsTest.php` fails when a key is missing.

Follow the `localization` skill when you add or change user-facing text.
