# Development Workflow

## Daily Commands

```bash
composer run dev
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

## Recommended Loop

1. Pull latest changes.
2. Create a focused branch for one feature/fix.
3. Implement changes module-first.
4. Add or update tests.
5. Run focused tests, then broader suite as needed.
6. Run formatter before commit.

## Useful Commands

```bash
php artisan route:list --except-vendor
php artisan config:show app.name
php artisan horizon
```

## Testing Guidance

- Prefer feature tests for HTTP and module integration behavior.
- Keep tests tenant-aware to verify isolation behavior.
- Cover happy path, failure path, and permission checks.
