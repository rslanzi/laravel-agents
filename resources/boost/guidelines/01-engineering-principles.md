# Engineering Principles

These principles decide every design choice in this project. When two rules seem to conflict, prefer the simplest solution that keeps the tests green and the architecture tests passing.

## KISS: keep it simple

- Write the most direct code that solves today's requirement. A readable `if` beats a clever abstraction.
- Prefer Laravel's built-in features (validation, policies, events, queues, casts, Form Requests) over custom infrastructure.
- One class, one reason to exist. If a class name needs "And" or "Manager", split it.
- Keep methods short (rule of thumb: under ~20 lines) and use early returns instead of nested conditionals.
- No magic: avoid dynamic method calls, `__call`, string-based class resolution and hidden side effects in accessors.

## YAGNI: you aren't gonna need it

- Do not add interfaces, base classes, configuration options, events or abstraction layers "for later". Add them when a second real use case or a test needs them.
- Plain CRUD entities without cross-record invariants stay plain Eloquent models. Do not wrap them in repositories, Actions or DTOs until a real invariant or a second caller appears.
- Do not write code paths, parameters or settings that no test exercises.
- Delete dead code instead of commenting it out.

## SOLID, applied to Laravel

- **Single Responsibility**: controllers, Livewire components and Filament resources only translate input and output; Actions hold one use case each; Queries only read; Services hold reusable domain computations without persistence.
- **Open/Closed**: extend behaviour with new Actions, new enum cases, new listeners or new contract implementations, not by adding flags and `switch` branches to existing classes.
- **Liskov Substitution**: every implementation of a `Contract` (real, null or fake) honors its documented behavior: preconditions, postconditions, exceptions and events (see "behavioral contract"). A shared contract test proves it for each implementation.
- **Interface Segregation**: contracts are small and owned by the consumer (the module that needs the capability defines the interface it needs, nothing more).
- **Dependency Inversion**: depend on contracts for external systems (payment gateways, SMS, mail providers, other modules) and bind implementations in a service provider. Inject dependencies through the constructor; never call `app()`/`resolve()` inside domain code.

## DRY, with care

- Remove duplication of knowledge (business rules, state transitions, validation rules), not duplication of shape. Two similar-looking snippets that change for different reasons are not duplication.
- Reuse existing components, Actions, Queries and factory states before writing new ones. Search the codebase first.

## Clean code

- Names are meaningful, pronounceable and searchable: no magic numbers (use named constants or enums), no abbreviations, one word per concept (`getUser`, not `getUserInfo`/`getUserData`), no context the class already gives (`Car::$make`, not `Car::$carMake`).
- Functions do one thing at one level of abstraction, and their name says what they do, side effects included (`createOrReturnProfile()`, not `getProfile()`). Two arguments or fewer; with more, group them in a DTO or value object.
- No boolean flag parameters: write two methods (`createFile()`, `createTempFile()`).
- Encapsulate conditionals in named methods (`$article->isPublished()`), avoid negative names (`isPresent()`, not `isNotMissing()`), and replace `switch`/`instanceof` on a type with polymorphism or an enum method.
- No global state, no singletons, no mutable data shared between calls: pass dependencies in.
- Use `===`/`!==`, `??` for defaults, and typed default arguments instead of `?:` inside the body.
- No fluent setters returning `$this`, except in builders.
- Comments explain *why*. If a comment explains *what*, rename or extract instead.

## Naming

- Name Actions as verb + noun + `Action` (`ConfirmOrderAction`), Events in past tense (`OrderConfirmed`), Listeners as what they do (`SendConfirmationEmail`), Queries as what they return (`OverdueInvoices`), Exceptions as what went wrong (`ProductOutOfStockException`), enums in the singular (`OrderStatus`) with PascalCase cases.
