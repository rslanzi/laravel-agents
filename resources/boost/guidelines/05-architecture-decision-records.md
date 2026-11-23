# Architecture Decision Records (ADR)

Significant decisions are recorded as ADRs in `docs/adr/`. This rule is an explicit exception to "only create documentation files if requested": writing the ADR is part of the change, not optional documentation.

## When to write an ADR

Write one, in the same change as the code, when you:

- add, split, merge or rename a bounded context, or move a model between contexts;
- introduce a new cross-context mechanism (a `Contract`, a shared kernel element, an event other contexts depend on) or relax an architecture test;
- change the behavior of an existing `Contract` (stronger precondition, weaker postcondition, new exception, different events);
- add or replace a dependency, an external service or an infrastructure piece (queue driver, cache, search, storage, payment or mail provider);
- choose between real alternatives on persistence, consistency or transactions (e.g. sync vs after-commit events, locking strategy, soft deletes);
- deviate from a rule in these guidelines (the ADR is where the exception is justified);
- make a decision that is costly to reverse or that a future developer would ask "why did we do this?" about.

Do **not** write an ADR for routine work that simply follows the existing rules (a new Action, a new resource, a bug fix). When in doubt, ask the user.

## Where and how

- One file per decision: `docs/adr/NNNN-short-title-in-kebab-case.md`, with `NNNN` the next free four-digit number (`0001`, `0002`, ...). Numbers are never reused.
- Write in the project's documentation language (ask the user once if unclear) and keep it short: one or two screens.
- Keep `docs/adr/README.md` as an index table (number, title, status, date), updated in the same change.
- ADRs are immutable once `Accepted`. To change a decision, write a new ADR that supersedes the old one, and only edit the old one's status line (`Superseded by ADR-NNNN`).
- An ADR you write on your own starts as `Proposed`. Mark it `Accepted` only when the user confirms the decision.
- Link the ADR from the code where the decision is visible (a PHPDoc `@see docs/adr/0007-...md` on the contract, the arch rule or the provider binding) and from the pull request description.

## Statuses

`Proposed` → `Accepted` → (`Deprecated` | `Superseded by ADR-NNNN`). `Rejected` is also valid for a proposal the user declined: keep the file, it records why.

## Template

```markdown
# ADR-NNNN: Short decision title

- Status: Proposed
- Date: YYYY-MM-DD
- Deciders: names or roles
- Context(s): affected bounded contexts

## Context

What problem or force makes a decision necessary now. Facts, constraints, requirements. No solution yet.

## Decision

What we decided, stated in active voice ("We will ..."). Include the concrete rule the code must follow.

## Alternatives considered

- **Option A**: one line on what it is, why it was not chosen.
- **Option B**: ...

## Consequences

- Positive: what gets easier.
- Negative: what gets harder, the costs we accept.
- Follow-ups: tests, arch rules, migrations or tasks this decision requires.
```
