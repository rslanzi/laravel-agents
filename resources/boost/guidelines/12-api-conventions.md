# API Conventions

JSON APIs live in the module's `Routes/api.php` (loaded with the `api` middleware and the `/api` prefix) and are versioned (`/api/v1/...`). Requests go through a Form Request that builds a DTO; responses are always Eloquent API Resources; errors use one format with a stable `code`. A breaking change needs a new version.

Before adding or changing an endpoint, follow the `api-conventions` skill: it has the routing, response, pagination, error and testing rules. Follow existing project conventions if an API already exists.
