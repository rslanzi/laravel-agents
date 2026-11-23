# Documentation

Documentation lives next to the code and changes **in the same pull request** as the code it describes. These files are an explicit exception to "only create documentation files if requested":

- `CHANGELOG.md`: add a line to `## [Unreleased]` for every change users, operators or other developers notice, under Added, Changed, Deprecated, Removed, Fixed or Security. Write what changed for them, not which files moved. Internal refactors and test-only changes get no entry.
- `app/Domain/{Context}/README.md`: every module has one, the first file to read before touching the module. Create it with the module; update it when a public element in its template is added, changed or removed.
- `docs/architecture.md`: update the module map when a module, a cross-module event or contract, or an external service is added or removed.
- `docs/adr/`: a new ADR for each significant decision (see "Architecture Decision Records").

Keep each one short. Link ADRs instead of repeating their reasoning, and never name the agent as an author.

Follow the `project-documentation` skill for the changelog and architecture map, and the `module-readme` skill for module READMEs.
