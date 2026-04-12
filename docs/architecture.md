# Architecture Notes

SaaS Starter follows a modular monolith structure using nWidart modules and Laravel service-layer patterns.

## Request Path

1. Middleware chain resolves auth and tenant context.
2. FormRequest handles validation and authorization.
3. Controller delegates to services.
4. Services dispatch domain events.
5. Listeners fan out to activity/audit/billing side effects.
6. Async side effects run through queues and Horizon.

## Key Principles

- Tenant isolation by default via global scopes.
- Thin controllers, business logic in services.
- Event-driven side effects for auditability.
- Middleware-enforced limits and tenant-aware rate limiting.

## Module Layout

Each module generally includes:

- `app/Http/Controllers`
- `app/Http/Requests`
- `app/Services`
- `app/Repositories`
- `app/Models`
- `app/Events` and `app/Listeners`
- `database/migrations`
- `routes/api.php`
- `tests`
