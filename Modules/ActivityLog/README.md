# ActivityLog Module

Provides a human-readable, tenant-scoped activity feed driven entirely by domain events.

## Responsibility

Records a plain-English description of every significant action taken within a tenant — project and task lifecycle events. Zero instrumentation is required in controllers or services: all logging happens in event listeners that subscribe to domain events dispatched by the Project and Task modules.

## Key Files

| File                                                       | Purpose                             |
| ---------------------------------------------------------- | ----------------------------------- |
| `app/Models/ActivityLog.php`                               | Activity record with tenant scope   |
| `app/Enums/ActivityAction.php`                             | Typed enum for all loggable actions |
| `app/Services/ActivityLogService.php`                      | Creates activity records            |
| `app/Interfaces/Contracts/ActivityLogServiceInterface.php` | Service contract                    |
| `app/Listeners/LogProjectCreated.php`                      | Handles `ProjectCreated` event      |
| `app/Listeners/LogProjectUpdated.php`                      | Handles `ProjectUpdated` event      |
| `app/Listeners/LogProjectDeleted.php`                      | Handles `ProjectDeleted` event      |
| `app/Listeners/LogTaskCreated.php`                         | Handles `TaskCreated` event         |
| `app/Listeners/LogTaskUpdated.php`                         | Handles `TaskUpdated` event         |
| `app/Listeners/LogTaskCompleted.php`                       | Handles `TaskCompleted` event       |
| `app/Providers/EventServiceProvider.php`                   | Wires events to listeners           |
| `app/Transformers/ActivityLogResource.php`                 | API response shape                  |
| `app/Http/Controllers/ActivityLogController.php`           | Paginated read endpoint             |

## Endpoint

| Method | Path                    | Auth                    | Description                         |
| ------ | ----------------------- | ----------------------- | ----------------------------------- |
| `GET`  | `/api/v1/activity-logs` | `auth:sanctum` + tenant | List recent activity for the tenant |

The response is paginated and ordered by most recent first. Every member of the tenant can read the activity feed.

## Event → Listener Map

| Domain Event              | Listener            | Description recorded                        |
| ------------------------- | ------------------- | ------------------------------------------- |
| `Project::ProjectCreated` | `LogProjectCreated` | "Project '{name}' was created"              |
| `Project::ProjectUpdated` | `LogProjectUpdated` | "Project '{name}' was updated"              |
| `Project::ProjectDeleted` | `LogProjectDeleted` | "Project '{name}' was deleted"              |
| `Task::TaskCreated`       | `LogTaskCreated`    | "Task '{title}' was created in '{project}'" |
| `Task::TaskUpdated`       | `LogTaskUpdated`    | "Task '{title}' was updated"                |
| `Task::TaskCompleted`     | `LogTaskCompleted`  | "Task '{title}' was marked complete"        |

## Difference from AuditLog

|          | ActivityLog                | AuditLog                                     |
| -------- | -------------------------- | -------------------------------------------- |
| Audience | Team members (UX feed)     | Compliance and security teams                |
| Content  | Human-readable description | Structured old/new values + actor metadata   |
| Covers   | Project and task events    | Project, task, billing, and API token events |
| Endpoint | `/api/v1/activity-logs`    | `/api/v1/audit-logs`                         |

## Database

| Column         | Type             | Purpose                         |
| -------------- | ---------------- | ------------------------------- |
| `id`           | bigint           | Primary key                     |
| `tenant_id`    | FK → tenants     | Tenant scoping                  |
| `user_id`      | FK → users\|null | Actor (null for system actions) |
| `action`       | string           | `ActivityAction` enum value     |
| `description`  | string           | Human-readable summary          |
| `subject_type` | string\|null     | Morphable subject class         |
| `subject_id`   | bigint\|null     | Morphable subject ID            |
| `created_at`   | timestamp        | When the action occurred        |
