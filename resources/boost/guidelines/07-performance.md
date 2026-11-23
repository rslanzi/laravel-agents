# Performance

Measure before optimizing, but never ship the well-known mistakes: N+1 queries (lazy loading fails the tests), unbounded `->get()`/`all()` on tables that grow (paginate or chunk), missing indexes on foreign keys and filtered columns, slow or external work inside the request (queue it), caches without explicit TTL, invalidation and tenant/locale in the key.

Before writing lists, reports, exports, imports, jobs, caching or heavy Livewire/Filament screens, follow the `laravel-performance` skill.
