# Safe Database Migrations

Every migration must be safe on a live database while the previous release is still serving requests.

- Never edit a migration that has run in a shared environment: write a new one.
- Nothing destructive without the user's explicit confirmation: dropping or renaming tables or columns, changing or narrowing a type, `NOT NULL` without a default, deleting or rewriting data.
- Breaking schema changes follow expand, migrate, contract across separate releases; backfills run chunked and idempotent outside the schema migration.
- Never run `migrate:fresh`, `migrate:rollback`, `migrate:reset` or `db:wipe` against anything but a local or test database.

Before writing or changing a migration, follow the `safe-migrations` skill: it has the step-by-step patterns, large-table rules and the PR checklist.
