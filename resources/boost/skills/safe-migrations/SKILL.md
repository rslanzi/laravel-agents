---
name: safe-migrations
description: "Use when creating or changing a database migration, a backfill job or command, or an index on a large table: zero-downtime rules (expand, migrate, contract), large-table locking, down() and the migration PR checklist."
license: MIT
metadata:
  author: rslanzi
---

# Safe Database Migrations

Every migration must be safe to run on a live production database while the previous version of the code is still serving requests (zero downtime).

## Golden rules

- **Never edit a migration that has run in any shared environment.** Write a new one.
- **Nothing destructive without the user's explicit confirmation**: dropping or renaming tables/columns, changing a column type, narrowing a column, adding `NOT NULL` without a default, deleting or rewriting data. Ask first and explain the rollout plan.
- **Expand, migrate, contract.** Breaking schema changes are split across separate deployments:
  1. **Expand**: add the new column/table (nullable or with a default). Code writes to both old and new, reads from old.
  2. **Migrate**: backfill existing rows in a queued, chunked job or a separate command (not inside the schema migration). Switch reads to the new column.
  3. **Contract**: in a later release, once nothing reads the old column, drop it (with confirmation).
- **Rename = add + copy + drop**, never `renameColumn` on a table that live code uses.
- **Separate schema and data changes.** Schema migrations do not loop over models. Large data updates run in chunks (`chunkById`) from a job or an artisan command that is idempotent and resumable.
- **Do not use Eloquent models in migrations** (they change over time); use the query builder if data must be touched.

## Large tables

- Adding an index on a big table can lock it: on MySQL use `ALGORITHM=INPLACE, LOCK=NONE` where supported; on PostgreSQL create indexes `CONCURRENTLY` (in a migration with `$withinTransaction = false`).
- Adding a column with a non-null default can rewrite the table on some engines and versions: check and prefer nullable + backfill + constraint.
- Foreign keys on large tables: add the column first, backfill, then add the constraint.

## Every migration

- Has a working `down()` when reversal is possible; when it is not (data loss), make `down()` throw a clear exception and say so in the PR.
- Adds indexes and foreign keys in the same migration as the column (except on large tables, see above). Choose `cascadeOnDelete()` / `restrictOnDelete()` / `nullOnDelete()` deliberately, based on the domain rule.
- Uses the module's naming: table names in plural snake_case, prefixed by context only when names would collide.
- Is tested: the test suite runs all migrations from scratch; for backfills add a feature test of the job/command.
- Never run `migrate:fresh`, `migrate:rollback`, `migrate:reset` or `db:wipe` against anything but a local or test database.

## PR checklist for migrations

- [ ] Backward compatible with the code currently in production.
- [ ] No destructive operation, or explicit confirmation obtained and noted in the PR.
- [ ] Backfill runs outside the schema migration, chunked and idempotent.
- [ ] Indexes created without long locks on large tables.
- [ ] `down()` works or is explicitly irreversible.
