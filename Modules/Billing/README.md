# Billing Module

Manages Stripe subscriptions, webhook processing, plan-based feature limits, and usage metering for tenants.

## Responsibility

Billing is attached to the **Tenant**, not the User. This module owns the complete lifecycle: subscribing to a plan, swapping plans, canceling, processing Stripe webhooks idempotently, enforcing feature limits at the middleware layer, tracking monthly usage per feature, and exposing a usage dashboard endpoint.

## Key Files

| File                                                | Purpose                                          |
| --------------------------------------------------- | ------------------------------------------------ |
| `app/Models/Plan.php`                               | Available subscription plans with limits config  |
| `app/Models/TenantUsageCounter.php`                 | Per-feature monthly usage tracking per tenant    |
| `app/Models/WebhookReceipt.php`                     | Idempotency store for processed Stripe event IDs |
| `app/Http/Controllers/BillingController.php`        | Subscribe, swap, cancel endpoints                |
| `app/Http/Controllers/StripeWebhookController.php`  | Stripe webhook receiver and dispatcher           |
| `app/Http/Controllers/UsageDashboardController.php` | Usage metering dashboard endpoint                |
| `app/Http/Middleware/EnsureFeatureLimit.php`        | Blocks requests when plan hard limit reached     |
| `app/Http/Middleware/EnsureTenantApiRateLimit.php`  | Per-plan API rate limiting via Redis             |
| `app/Services/BillingService.php`                   | Subscription business logic                      |
| `app/Services/FeatureLimitService.php`              | Plan limit lookups and enforcement checks        |
| `app/Services/UsageCounterService.php`              | Counter increment and retrieval                  |
| `app/Jobs/Handle*Job.php`                           | Queued async handlers for billing events         |
| `app/Console/UsageCountersRolloverCommand.php`      | Monthly counter reset on plan period renewal     |

## Endpoints

| Method   | Path                           | Auth                    | Description                               |
| -------- | ------------------------------ | ----------------------- | ----------------------------------------- |
| `GET`    | `/api/v1/billing/plans`        | `auth:sanctum` + tenant | List all active plans                     |
| `POST`   | `/api/v1/billing/subscribe`    | `auth:sanctum` + tenant | Subscribe tenant to a plan                |
| `PATCH`  | `/api/v1/billing/subscription` | `auth:sanctum` + tenant | Swap to a different plan                  |
| `DELETE` | `/api/v1/billing/subscription` | `auth:sanctum` + tenant | Cancel the active subscription            |
| `GET`    | `/api/v1/billing/usage`        | `auth:sanctum` + tenant | Usage metering dashboard                  |
| `POST`   | `/api/v1/billing/webhook`      | Stripe signature        | Receive and process Stripe webhook events |

## Webhook Flow

```bash
POST /api/v1/billing/webhook
   ↓
Stripe signature verification (403 if invalid)
   ↓
WebhookReceipt deduplication check (skip if already processed)
   ↓
Domain event dispatched:
  BillingSubscriptionChanged  →  HandleSubscriptionChangedJob (queued)
  BillingSubscriptionCanceled →  HandleSubscriptionCanceledJob (queued)
  BillingPaymentFailed        →  HandlePaymentFailedJob (queued)
  BillingInvoicePaid          →  HandleInvoicePaidJob (queued)
   ↓
AuditLog listeners record each event to audit_logs
```

Supported Stripe events: `invoice.paid`, `invoice.payment_failed`, `customer.subscription.updated`, `customer.subscription.deleted`

## Feature Limits

Plans define hard limits via JSON config on the `plans` table:

```json
{
    "max_projects": 10,
    "max_users": 5,
    "max_api_calls": 10000
}
```

Routes that create resources use the middleware:

```bash
feature.limit:max_projects   →  POST /api/v1/projects
feature.limit:max_users      →  POST /api/v1/memberships
```

If the tenant's current usage meets or exceeds the limit, the request receives `402 Payment Required`.

## Rate Limiting

`EnsureTenantApiRateLimit` applies a per-plan rate ceiling using Redis:

| Plan       | Requests/minute |
| ---------- | --------------- |
| Free       | 60              |
| Pro        | 300             |
| Enterprise | 1000            |

## Usage Dashboard — Sample Response

```json
{
    "plan": "pro",
    "features": {
        "projects": { "limit": 10, "used": 4, "utilization": 40 },
        "users": { "limit": 5, "used": 2, "utilization": 40 },
        "api_calls": { "limit": 10000, "used": 1230, "utilization": 12 }
    },
    "history": [{ "period": "2026-03", "feature": "api_calls", "count": 8900 }]
}
```

## Tenant Billing State

Columns added to `tenants` by this module:

| Column                 | Values                                      | Purpose                                              |
| ---------------------- | ------------------------------------------- | ---------------------------------------------------- |
| `billing_status`       | `active`, `grace`, `delinquent`, `canceled` | Current billing health                               |
| `grace_period_ends_at` | timestamp                                   | How long until downgrade enforced after cancellation |
| `delinquent_since`     | timestamp                                   | When payment first failed                            |
| `plan_id`              | FK to `plans`                               | Currently active plan                                |

## Environment Variables

```env
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PRICE_FREE=
STRIPE_PRICE_PRO=
STRIPE_PRICE_ENTERPRISE=
CASHIER_CURRENCY=usd
```
