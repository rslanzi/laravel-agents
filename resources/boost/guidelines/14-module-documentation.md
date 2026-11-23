# Module Documentation

Every bounded context has a `README.md` at its root (`app/Domain/{Context}/README.md`). It is the module's contract with the rest of the application and the first file an agent reads before touching the module. This is an explicit exception to "only create documentation files if requested".

## Keep it current

- Create it when the module is created; update it **in the same change** whenever a public element listed in its template is added, changed or removed.
- Keep it short (one or two screens). Link ADRs instead of repeating their reasoning. Do not list every class: only what other modules or developers need to know.

Use the `module-readme` skill for the template.
