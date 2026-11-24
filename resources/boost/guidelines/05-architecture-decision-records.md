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

## How

One file per decision in `docs/adr/NNNN-short-title.md`, indexed in `docs/adr/README.md`, in the same change as the code. An ADR you write starts as `Proposed`; only the user marks it `Accepted`, never the agent, and the index in `docs/adr/README.md` shows the same status as the file. Follow the `writing-adrs` skill for numbering, statuses and the template.
