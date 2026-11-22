# Working Agreement and Agent Boundaries

## Working agreement (read first)

Before writing code:

0. Respect the agent boundaries below: know what needs the user's confirmation before you start.
1. Read this file and any `.ai/rules/` file whose globs match the paths you will touch.
2. Find the bounded context the change belongs to (`app/Domain/{Context}`) and read its `README.md`. If none fits, propose a new context to the user before creating it.
3. Look at sibling files and reuse existing Actions, Queries, factories and components.
4. Use Laravel Boost `search-docs` for version-specific APIs, and `database-schema` before writing migrations or models.

While writing code:

5. Follow TDD: failing test, minimum code, refactor.
6. Keep it simple (KISS), build only what is needed now (YAGNI), and respect SOLID and the module boundaries.

Before finishing:

7. Run Rector, Pint, PHPStan and Pest in this order (`composer fix` if defined), including `tests/Unit/ArchitectureTest.php`. All must be green, without ignores or skipped tests.
8. If the change involves an architectural decision (see "Architecture Decision Records"), write or update the ADR in `docs/adr/` in the same change.
   Update the module `README.md`, translations and API docs when the change touches what they describe.
9. End with the final summary described below.

## Agent boundaries

What you may do on your own, what needs the user's confirmation, and how to report back.

### Stop and ask before you

- change dependencies (`composer require/remove`, `npm install <pkg>`) or upgrade a major version;
- create a new bounded context, a new top-level folder, or move models between contexts;
- relax, skip or delete a test or an architecture rule;
- edit an existing migration, or write a migration that drops or renames columns/tables or rewrites data;
- run anything destructive or with external effects: `migrate:fresh`, `migrate:rollback`, `db:wipe`, deleting files outside your change, sending real emails/SMS/payments, calling production APIs;
- touch `.env`, credentials, CI/CD configuration, deployment scripts or production data;
- change authentication, authorization (policies, gates, roles) or anything security-sensitive beyond the task;
- implement something when the requirement is ambiguous or two reasonable readings lead to different designs.

When you ask, give the options and your recommendation in one or two sentences.

### Never

- commit secrets, tokens or real personal data (including in tests, seeders and fixtures);
- disable or weaken a check (`@phpstan-ignore`, `--no-verify`, `markTestSkipped`, lowering PHPStan level) to get green;
- push, merge, tag or deploy unless the user asked you to;
- invent APIs: if you are unsure a method exists in the installed version, check with `search-docs` or the vendor source.

### Scope discipline

- Do the task that was asked. Note unrelated problems you find in the final summary instead of fixing them in the same change.
- Keep changes small and reviewable. If the task turns out much bigger than expected, stop and propose a plan.
- Leave the code at least as clean as you found it, inside the files you touch.

### Final summary

End every task with a short report:

1. **What changed** and why, grouped by context.
2. **Tests**: which tests were added or changed and the result of the quality gates (Pint, Rector, PHPStan, Pest).
3. **Decisions**: the ADR written or updated, if any.
4. **Open points**: assumptions you made, things you could not verify, follow-ups you recommend.
