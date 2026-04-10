# AuditLog Module

Provides a compliance-grade, immutable audit trail covering all sensitive domain actions across the application.

## Responsibility

Records structured audit entries — with actor identity, action type, before/after values, IP address, and user agent — for every significant event: project and task mutations, billing subscription changes, payment failures, invoice payments, and API token issuance or revocation. All recording happens through event listeners with no manual instrumentation in controllers.

## Key Files

| File                                                    | Purpose                                                        |
| ------------------------------------------------------- | -------------------------------------------------------------- |
| `app/Models/AuditLog.php`                               | Audit record model (tenant-scoped)                             |
| `app/Enums/AuditAction.php`                             | Full typed enum of all auditable actions                       |
| `app/Services/AuditLogService.php`                      | Creates audit records with structured fields                   |
| `app/Interfaces/Contracts/AuditLogServiceInterface.php` | Service contract (injected wherever audit recording is needed) |
| `app/Policies/AuditLogPolicy.php`                       | Authorization for reading audit logs                           |
| `app/Transformers/AuditLogResource.php`                 | API response shape                                             |
| `app/Http/Controllers/AuditLogController.php`           | Paginated read-only endpoint                                   |
| `app/Providers/AuditLogServiceProvider.php`             | Binds service interface and registers gate policy              |
| `app/Providers/EventServiceProvider.php`                | Wires all domain events to audit listeners                     |

## Endpoint

| Method | Path                 | Auth                    | Required Role   | Description                      |
| ------ | -------------------- | ----------------------- | --------------- | -------------------------------- |
| `GET`  | `/api/v1/audit-logs` | `auth:sanctum` + tenant | `owner`/`admin` | Paginated compliance audit trail |

## Event → Listener Map

### Project Events

| Domain Event              | Listener                 | `AuditAction` recorded |
| ------------------------- | ------------------------ | ---------------------- |
| `Project::ProjectCreated` | `LogProjectCreatedAudit` | `ProjectCreated`       |
| `Project::ProjectUpdated` | `LogProjectUpdatedAudit` | `ProjectUpdated`       |
| `Project::ProjectDeleted` | `LogProjectDeletedAudit` | `ProjectDeleted`       |

### Task Events

| Domain Event          | Listener                | `AuditAction` recorded |
| --------------------- | ----------------------- | ---------------------- |
| `Task::TaskCreated`   | `LogTaskCreatedAudit`   | `TaskCreated`          |
| `Task::TaskUpdated`   | `LogTaskUpdatedAudit`   | `TaskUpdated`          |
| `Task::TaskCompleted` | `LogTaskCompletedAudit` | `TaskCompleted`        |

### Billing Events

| Domain Event                           | Listener                              | `AuditAction` recorded |
| -------------------------------------- | ------------------------------------- | ---------------------- |
| `Billing::BillingSubscriptionChanged`  | `LogBillingSubscriptionChangedAudit`  | `SubscriptionChanged`  |
| `Billing::BillingSubscriptionCanceled` | `LogBillingSubscriptionCanceledAudit` | `SubscriptionCanceled` |
| `Billing::BillingPaymentFailed`        | `LogBillingPaymentFailedAudit`        | `PaymentFailed`        |
| `Billing::BillingInvoicePaid`          | `LogBillingInvoicePaidAudit`          | `InvoicePaid`          |

### API Token Events (direct service call from `ApiTokenController`)

| Action        | `AuditAction` recorded |
| ------------- | ---------------------- |
| Token issued  | `ApiTokenCreated`      |
| Token revoked | `ApiTokenRevoked`      |

## Audit Record Structure

Each record captures:

| Field        | Purpose                           |
| ------------ | --------------------------------- |
| `tenant_id`  | Tenant the action occurred in     |
| `actor_id`   | User who performed the action     |
| `action`     | `AuditAction` enum value          |
| `old_values` | JSON snapshot before the change   |
| `new_values` | JSON snapshot after the change    |
| `ip_address` | Request IP                        |
| `user_agent` | Request user agent string         |
| `created_at` | Immutable timestamp of the action |

## Sample API Response

```json
{
    "data": [
        {
            "id": 42,
            "action": "project_created",
            "actor": { "id": 1, "name": "Jane Smith" },
            "new_values": { "name": "Acme Redesign" },
            "old_values": null,
            "ip_address": "203.0.113.5",
            "created_at": "2026-04-10T14:32:00Z"
        }
    ],
    "meta": { "current_page": 1, "total": 84 }
}
```

## Difference from ActivityLog

|            | AuditLog                                  | ActivityLog                  |
| ---------- | ----------------------------------------- | ---------------------------- |
| Audience   | Compliance / security / admins            | All team members             |
| Content    | Structured old/new values, IP, user agent | Human-readable description   |
| Scope      | Project, task, billing, API tokens        | Project and task events only |
| Mutability | Immutable (no update/delete endpoint)     | Append-only                  |

## Database

| Column       | Type             | Purpose                                    |
| ------------ | ---------------- | ------------------------------------------ |
| `id`         | bigint           | Primary key                                |
| `tenant_id`  | FK → tenants     | Tenant isolation                           |
| `actor_id`   | FK → users\|null | Who performed the action                   |
| `action`     | string           | `AuditAction` enum value                   |
| `old_values` | json\|null       | State before the change                    |
| `new_values` | json\|null       | State after the change                     |
| `ip_address` | string\|null     | Originating IP                             |
| `user_agent` | string\|null     | Originating client                         |
| `created_at` | timestamp        | When the action occurred (no `updated_at`) |
