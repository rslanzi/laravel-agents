---
name: project-documentation
description: "Use when a change is visible to users, operators or other developers, or adds, removes or rewires a module, a cross-module event or contract, or an external service: how to write CHANGELOG.md entries and keep docs/architecture.md current."
license: MIT
metadata:
  author: rslanzi
---

# Project Documentation

## CHANGELOG.md

The project follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Every pull request with a noticeable change adds its lines under `## [Unreleased]`, in the same pull request.

```markdown
## [Unreleased]

### Added

- Customers can download their invoices as PDF from the order page.

### Fixed

- Confirming an order twice no longer sends two confirmation emails.
```

- Sections, in this order, only when they have entries: Added, Changed, Deprecated, Removed, Fixed, Security.
- One line per change, written for the reader of the release notes: what they can do now or what behaves differently. No class names unless the reader is a developer using them, no ticket-only lines, no agent or tool names.
- Breaking changes for other developers or operators (a removed endpoint, a new required env variable, a migration that needs a manual step) start with **BREAKING** and say what to do.
- Skip entries for refactors, tests, CI and dependency bumps with no visible effect.
- Never edit released sections. If the project has no `CHANGELOG.md`, ask before creating one.
- Releasing (when the user asks): rename `[Unreleased]` to `[x.y.z] - YYYY-MM-DD`, add a new empty `[Unreleased]` above it, and pick the version with Semantic Versioning (breaking: major, Added: minor, otherwise patch).

## docs/architecture.md

The map of modules for people; `.ai/guidelines/20-project-contexts.md` is the short version for agents. Update both when the structure changes.

- **Modules table**: every context in `app/Domain`, one line on what it owns, a link to its README. Add the row with the module; remove it with the module.
- **How modules talk**: a Mermaid `flowchart LR` with one arrow per dependency, from the module that depends to the one it depends on, labeled with the event it listens to or the contract it uses. Every listener of another module's event and every cross-module contract binding appears here. When the architecture test forbids a dependency, the diagram must not show it.
- **Shared kernel**: what lives in `app/Domain/Shared` and why.
- **External services**: each service, the modules that use it and the contract it sits behind.

Before finishing a change that adds a listener, a contract binding, a module or an external service, check the diagram and the tables against the code: `grep -rn "Event::listen\|->bind(\|->singleton(" app/Providers app/Domain/*/Providers` finds the wiring.
