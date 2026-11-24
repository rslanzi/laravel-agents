---
name: writing-adrs
description: "Use when writing, updating, superseding or indexing an Architecture Decision Record in docs/adr: file naming, statuses, the index table and the ADR template."
license: MIT
metadata:
  author: rslanzi
---

# Writing Architecture Decision Records

Write an ADR when the "Architecture Decision Records" guideline says one is needed.

## Where and how

- One file per decision: `docs/adr/NNNN-short-title-in-kebab-case.md`, with `NNNN` the next free four-digit number (`0001`, `0002`, ...). Numbers are never reused.
- Write in the project's documentation language (ask the user once if unclear) and keep it short: one or two screens.
- Keep `docs/adr/README.md` as an index table (number, title, status, date), updated in the same change. The index status is a copy of the file's `Status` line: never write a status there that the file does not have.
- ADRs are immutable once `Accepted`. To change a decision, write a new ADR that supersedes the old one, and only edit the old one's status line (`Superseded by ADR-NNNN`).
- An ADR you write starts as `Proposed`, and stays `Proposed` in the file and in the index until the user marks it `Accepted`. Never mark an ADR `Accepted` (or `Rejected`) yourself, not even when the code that implements it is merged: if you think it is ready, ask the user. When they confirm in the conversation, update the file and the index together.
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
