# SaaS Starter

Production-grade modular multi-tenant SaaS backend built with Laravel 13 and nWidart modules.

## Overview

A complete tenant-first SaaS engine covering identity, billing, domain logic, compliance, and developer platform features — all wired through an event-driven, service-layered architecture.

## Project Brief

SaaS Starter is a backend foundation for building multi-tenant SaaS products where each customer account (tenant) has isolated data, controlled member access, subscription billing, and usage limits.

It is designed for teams that want to ship SaaS APIs faster without rebuilding core platform concerns like tenant isolation, role-based access, billing flows, audit/compliance trails, and token-based integrations.

## What It Serves

- Startups building B2B SaaS platforms with tenant-aware APIs
- Product teams that need Stripe subscription lifecycle + webhook handling
- Engineering teams requiring compliance-grade audit history and activity feeds
- SaaS apps with plan-based limits (users/projects/features) and tenant-level throttling
- Teams that prefer a modular monolith architecture with clear service boundaries

### Core SaaS Engine

- Multi-tenant data isolation via global Eloquent scopes
- Team membership with `owner / admin / member` roles
- Invitation workflow with token-based acceptance
- Stripe billing + webhooks with idempotent event processing
- Plan-based feature limits enforced at the middleware layer
- Per-tenant API rate limiting tied to plan tier

### Product Modules

- Projects and Tasks (tenant-scoped, policy-guarded)
- Activity logs — human-readable feed driven by domain events
- Queued jobs via Laravel Horizon for async billing side effects

### Production Features

- Audit logs — compliance-grade immutable trail across all sensitive actions
- API token management — Sanctum personal access tokens with named abilities
- Usage metering dashboard — real-time limits, utilization, and history per feature
- Standardized API exception responses — consistent JSON error payloads and status codes

## Modules

| Module          | Responsibility                                                 | README                                                         |
| --------------- | -------------------------------------------------------------- | -------------------------------------------------------------- |
| **Tenant**      | Tenant resolution, isolation, global scoping                   | [Modules/Tenant/README.md](Modules/Tenant/README.md)           |
| **User**        | Auth, profile, API token management                            | [Modules/User/README.md](Modules/User/README.md)               |
| **Membership**  | Roles, permissions, invitations                                | [Modules/Membership/README.md](Modules/Membership/README.md)   |
| **Billing**     | Stripe subscriptions, webhooks, feature limits, usage counters | [Modules/Billing/README.md](Modules/Billing/README.md)         |
| **Project**     | Tenant-scoped project CRUD with events                         | [Modules/Project/README.md](Modules/Project/README.md)         |
| **Task**        | Tasks nested under projects with events                        | [Modules/Task/README.md](Modules/Task/README.md)               |
| **ActivityLog** | Event-driven human-readable activity feed                      | [Modules/ActivityLog/README.md](Modules/ActivityLog/README.md) |
| **AuditLog**    | Compliance audit trail for all sensitive domain actions        | [Modules/AuditLog/README.md](Modules/AuditLog/README.md)       |

## Tech Stack

- PHP 8.3 / Laravel 13
- MySQL (primary store)
- Redis (queues, cache, sessions, rate limiting)
- Laravel Sanctum (API authentication + personal access tokens)
- Laravel Cashier Stripe (tenant-level billing)
- Laravel Horizon (queue monitoring and worker management)
- nWidart/laravel-modules (modular monolith structure)
- PHPUnit 12

## Architecture

### Request Lifecycle

```s
HTTP Request
   ↓
auth:sanctum → tenant → tenant.member → throttle:api → tenant.api.rate
   ↓
FormRequest (validation + authorization)
   ↓
Controller → DTO → Service → Repository → Model
   ↓
Domain Event (ProjectCreated / TaskCompleted / BillingSubscriptionChanged / …)
   ↓
    ├─ ActivityLog Listener  →  activity_logs table  (human feed)
    ├─ AuditLog Listener     →  audit_logs table     (compliance)
    └─ Billing Listener      →  Queue Job → Horizon  (async side effects)
```

### Module Conventions

Each module follows this internal structure:

```t
Modules/{Name}/
├── app/
│   ├── Classes/DTOs/       # Typed data transfer objects
│   ├── Enums/              # Status, action, and type enumerations
│   ├── Events/             # Domain events dispatched from services
│   ├── Http/
│   │   ├── Controllers/    # Thin: resolve request → call service → return resource
│   │   ├── Middleware/     # Route-level guards
│   │   └── Requests/       # FormRequest validation + authorization
│   ├── Interfaces/Contracts/  # Service and repository interfaces
│   ├── Jobs/               # Queued async work
│   ├── Listeners/          # Event → side effect wiring
│   ├── Models/             # Eloquent models (global scopes applied here)
│   ├── Policies/           # Laravel Gate policies
│   ├── Providers/          # ServiceProvider, EventServiceProvider, RouteServiceProvider
│   ├── Repositories/       # Query construction, decoupled from services
│   ├── Services/           # Business logic
│   └── Transformers/       # Eloquent API Resources
├── database/migrations/
├── routes/api.php
└── tests/
```

## Implemented Features

### Tenant Isolation

- Tenant resolved from subdomain (primary) or `X-Tenant-ID` header (fallback)
- `TenantScope` global scope on all tenant-owned models — no manual `where('tenant_id')` in controllers
- Fail-closed: missing or mismatched tenant context returns 403

Key files: `Modules/Tenant/app/Http/Middleware/IdentifyTenant.php`, `Modules/Tenant/app/Models/Scopes/TenantScope.php`

### Membership & Roles

- Roles: `owner`, `admin`, `member`
- `tenant.member` middleware enforces minimum role on protected routes
- Full CRUD with policy checks per role level

### Invitations

- Owner/Admin can invite by email with role assignment
- Invitation stored with signed token
- Accept endpoint creates membership and invalidates token
- Duplicate membership and role enforcement on acceptance

### Stripe Billing

- Billing attached to Tenant (not User) via Cashier customer model
- Subscribe, swap plan, cancel subscription endpoints
- `StripeWebhookController` handles: `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`
- Idempotent processing via `WebhookReceipt` deduplication table
- Billing state columns on tenants: `billing_status`, `grace_period_ends_at`, `delinquent_since`

### Feature Limits & Rate Limiting

- `EnsureFeatureLimit` middleware blocks requests when plan hard limit is reached (e.g. `max_projects`, `max_users`)
- `EnsureTenantApiRateLimit` middleware enforces per-plan request rate (Free: 60/min, Pro: 300/min)
- `TenantUsageCounter` tracks monthly usage per feature per tenant period
- `UsageCountersRolloverCommand` resets counters on plan period renewal

### Projects

- Full CRUD, tenant-scoped via global scope
- Domain events: `ProjectCreated`, `ProjectUpdated`, `ProjectDeleted`
- Policy guards all mutations by membership role

### Tasks

- Nested under projects: `/api/v1/projects/{project}/tasks`
- Status (`pending`, `in_progress`, `completed`) and priority (`low`, `medium`, `high`) enums
- Domain events: `TaskCreated`, `TaskUpdated`, `TaskCompleted`
- Scoped route binding: task must belong to the resolved project

### Activity Logs

- Automatic human-readable feed — zero manual instrumentation in controllers
- 6 listeners fan out from Project/Task events to `activity_logs` table
- Tenant-scoped paginated endpoint: `GET /api/v1/activity-logs`

### Audit Logs

- Immutable compliance trail: actor, action, old/new values, IP, user agent, timestamp
- 10 listeners cover Project, Task, Billing events, and API token create/revoke
- Tenant-scoped paginated endpoint: `GET /api/v1/audit-logs`

### API Token Management

- Issue named Sanctum personal access tokens with optional scoped abilities
- List and revoke tokens; cross-user deletion blocked at query level
- Every issuance and revocation recorded in the audit log

### Usage Metering Dashboard

- `GET /api/v1/billing/usage` returns per-feature: limit, current usage, utilization %, and rolling history
- Powered by `FeatureLimitService` + `UsageCounterService`

## Stripe Webhook Event Flow

```bash
POST /api/v1/billing/webhook  (Stripe signature verified)
   ↓
StripeWebhookController  →  WebhookReceipt deduplication check
   ↓
Domain Event dispatched (BillingSubscriptionChanged, BillingPaymentFailed, …)
   ↓
    ├─ Billing Listener   →  Queued Job (HandleSubscriptionChangedJob, …)
    └─ AuditLog Listener  →  audit_logs record
```

## Database Schema

| Table                    | Module            | Purpose                                |
| ------------------------ | ----------------- | -------------------------------------- |
| `tenants`                | Tenant            | Tenant records + billing columns       |
| `users`                  | User              | User accounts                          |
| `memberships`            | Membership        | Tenant ↔ User role join table          |
| `invitations`            | Membership        | Pending invitation tokens              |
| `plans`                  | Billing           | Available subscription plans           |
| `subscriptions`          | Billing (Cashier) | Active tenant subscriptions            |
| `subscription_items`     | Billing (Cashier) | Per-item subscription lines            |
| `webhook_receipts`       | Billing           | Idempotency receipts for Stripe events |
| `tenant_usage_counters`  | Billing           | Per-feature monthly usage tracking     |
| `projects`               | Project           | Tenant-scoped projects                 |
| `tasks`                  | Task              | Project-scoped tasks                   |
| `activity_logs`          | ActivityLog       | Human-readable event feed              |
| `audit_logs`             | AuditLog          | Compliance-grade audit trail           |
| `personal_access_tokens` | User (Sanctum)    | API tokens                             |

## Environment Variables

```env
# Stripe / Cashier
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PRICE_FREE=
STRIPE_PRICE_PRO=
STRIPE_PRICE_ENTERPRISE=
CASHIER_CURRENCY=usd

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# App
APP_URL=http://localhost:8000
DB_DATABASE=saas_starter
```

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install && npm run build
```

## Daily Commands

```bash
composer run dev
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

## API Reference

### Public

| Method | Path                                 | Description                    |
| ------ | ------------------------------------ | ------------------------------ |
| `POST` | `/api/v1/register`                   | Register a new user            |
| `POST` | `/api/v1/login`                      | Authenticate and receive token |
| `GET`  | `/api/v1/invitations/{token}`        | View invitation details        |
| `POST` | `/api/v1/invitations/{token}/accept` | Accept an invitation           |
| `POST` | `/api/v1/billing/webhook`            | Stripe webhook receiver        |

### User & Auth (`auth:sanctum`)

| Method   | Path                       | Description                 |
| -------- | -------------------------- | --------------------------- |
| `POST`   | `/api/v1/logout`           | Revoke current token        |
| `GET`    | `/api/v1/me`               | Authenticated user profile  |
| `GET`    | `/api/v1/users/{user}`     | View user                   |
| `DELETE` | `/api/v1/users/{user}`     | Delete user                 |
| `GET`    | `/api/v1/tokens`           | List personal access tokens |
| `POST`   | `/api/v1/tokens`           | Create a named token        |
| `DELETE` | `/api/v1/tokens/{tokenId}` | Revoke a token              |

### Tenants (`auth:sanctum`)

| Method                 | Path                       | Description                         |
| ---------------------- | -------------------------- | ----------------------------------- |
| `GET`                  | `/api/v1/tenants`          | List tenants for authenticated user |
| `POST`                 | `/api/v1/tenants`          | Create tenant                       |
| `GET/PUT/PATCH/DELETE` | `/api/v1/tenants/{tenant}` | Manage tenant                       |

### Tenant-Protected (`auth:sanctum` + `tenant` + `tenant.member` + rate limit)

| Method                 | Path                                      | Description                                    |
| ---------------------- | ----------------------------------------- | ---------------------------------------------- |
| `GET`                  | `/api/v1/memberships`                     | List tenant memberships                        |
| `POST`                 | `/api/v1/memberships`                     | Add member (`max_users` limit enforced)        |
| `GET/PUT/PATCH/DELETE` | `/api/v1/memberships/{membership}`        | Manage member                                  |
| `POST`                 | `/api/v1/invitations`                     | Send invitation                                |
| `DELETE`               | `/api/v1/invitations/{invitation}`        | Revoke invitation                              |
| `GET`                  | `/api/v1/projects`                        | List projects                                  |
| `POST`                 | `/api/v1/projects`                        | Create project (`max_projects` limit enforced) |
| `GET/PUT/PATCH/DELETE` | `/api/v1/projects/{project}`              | Manage project                                 |
| `GET`                  | `/api/v1/projects/{project}/tasks`        | List tasks                                     |
| `POST`                 | `/api/v1/projects/{project}/tasks`        | Create task                                    |
| `GET/PUT/PATCH/DELETE` | `/api/v1/projects/{project}/tasks/{task}` | Manage task                                    |
| `GET`                  | `/api/v1/billing/plans`                   | List available plans                           |
| `POST`                 | `/api/v1/billing/subscribe`               | Subscribe tenant to plan                       |
| `PATCH`                | `/api/v1/billing/subscription`            | Swap plan                                      |
| `DELETE`               | `/api/v1/billing/subscription`            | Cancel subscription                            |
| `GET`                  | `/api/v1/billing/usage`                   | Usage metering dashboard                       |
| `GET`                  | `/api/v1/activity-logs`                   | Tenant activity feed                           |
| `GET`                  | `/api/v1/audit-logs`                      | Compliance audit trail                         |

## Test Coverage

**41 tests · 115 assertions · 0 failures:**

| File                                                         | Coverage                                   |
| ------------------------------------------------------------ | ------------------------------------------ |
| `tests/Feature/SaaS/MembershipPolicyTest.php`                | Role-based access, member limits           |
| `tests/Feature/SaaS/InvitationWorkflowTest.php`              | Full invite → accept flow, edge cases      |
| `tests/Feature/SaaS/ProjectTenantIsolationTest.php`          | Cross-tenant isolation, CRUD authorization |
| `tests/Feature/SaaS/BillingFlowTest.php`                     | Subscribe, swap, cancel, Stripe webhooks   |
| `tests/Feature/SaaS/BillingAuthorizationConstraintsTest.php` | Plan constraints, downgrade blocking       |
| `tests/Feature/SaaS/UsageCounterTrackingTest.php`            | Counter increments, rate limit enforcement |
| `tests/Feature/SaaS/ApiTokenManagementTest.php`              | Token CRUD, cross-user revoke protection   |
| `tests/Feature/SaaS/BillingUsageDashboardTest.php`           | Usage endpoint response structure, auth    |
| `tests/Feature/SaaS/AuditLogFlowTest.php`                    | Audit record creation from domain events   |

## License

This project follows the Laravel ecosystem package licenses used in `composer.lock`.
