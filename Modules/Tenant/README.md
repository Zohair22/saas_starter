# Tenant Module

Handles tenant creation, resolution, and data isolation across the entire application.

## Responsibility

Every API request that operates within a tenant's context must pass through this module's middleware stack. It resolves which tenant owns the request, binds that tenant to the application container, and enforces isolation via a global Eloquent scope so no query can ever leak across tenant boundaries.

## Key Files

| File                                               | Purpose                                                            |
| -------------------------------------------------- | ------------------------------------------------------------------ |
| `app/Models/Tenant.php`                            | Core tenant model; implements Cashier's `Billable` interface       |
| `app/Models/Scopes/TenantScope.php`                | Global scope applied to all tenant-owned models                    |
| `app/Http/Middleware/IdentifyTenant.php`           | Resolves tenant from subdomain or `X-Tenant-ID` header             |
| `app/Http/Middleware/EnsureTenantMember.php`       | Verifies the authenticated user is a member of the resolved tenant |
| `app/Http/Controllers/Api/V1/TenantController.php` | CRUD for tenant management                                         |
| `app/Providers/TenantServiceProvider.php`          | Binds middleware aliases and service interfaces                    |

## Tenant Resolution

Tenant is resolved in this priority order on every request:

1. **Subdomain** — `{slug}.yourdomain.com`
2. **`X-Tenant-ID` header** — for API clients that cannot use subdomains

If neither resolves to a known tenant, the middleware returns `403`.

## Middleware Aliases

| Alias           | Class                | Applied to                         |
| --------------- | -------------------- | ---------------------------------- |
| `tenant`        | `IdentifyTenant`     | All tenant-protected routes        |
| `tenant.member` | `EnsureTenantMember` | Routes requiring tenant membership |

## Endpoints

| Method      | Path                       | Auth           | Description                             |
| ----------- | -------------------------- | -------------- | --------------------------------------- |
| `GET`       | `/api/v1/tenants`          | `auth:sanctum` | List tenants for the authenticated user |
| `POST`      | `/api/v1/tenants`          | `auth:sanctum` | Create a new tenant                     |
| `GET`       | `/api/v1/tenants/{tenant}` | `auth:sanctum` | View a tenant                           |
| `PUT/PATCH` | `/api/v1/tenants/{tenant}` | `auth:sanctum` | Update a tenant                         |
| `DELETE`    | `/api/v1/tenants/{tenant}` | `auth:sanctum` | Delete a tenant                         |

## Data Isolation Pattern

All tenant-owned models apply `TenantScope` in their `booted()` method:

```php
protected static function booted(): void
{
    static::addGlobalScope(new TenantScope);
}
```

This means `Project::all()` automatically becomes `SELECT * FROM projects WHERE tenant_id = ?` — no manual filtering needed anywhere in the codebase.

## Database

| Column                                 | Table     | Purpose                                     |
| -------------------------------------- | --------- | ------------------------------------------- |
| `id`, `name`, `slug`                   | `tenants` | Core identity                               |
| `plan_id`                              | `tenants` | Active billing plan                         |
| `stripe_id`, `pm_type`, `pm_last_four` | `tenants` | Cashier payment info                        |
| `billing_status`                       | `tenants` | `active`, `grace`, `delinquent`, `canceled` |
| `grace_period_ends_at`                 | `tenants` | Downgrade grace window end                  |
| `delinquent_since`                     | `tenants` | Payment failure timestamp                   |
