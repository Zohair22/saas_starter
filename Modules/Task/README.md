# Task Module

Manages tasks nested under projects, with status/priority lifecycle, domain events, and policy authorization.

## Responsibility

Tasks are always scoped to a project, which is itself scoped to a tenant. Route model binding enforces this nesting, so a task ID from a different project cannot be resolved through the wrong project URL. All mutations dispatch domain events consumed by ActivityLog and AuditLog.

## Key Files

| File                                             | Purpose                                                 |
| ------------------------------------------------ | ------------------------------------------------------- |
| `app/Models/Task.php`                            | Task model with tenant + project scoping                |
| `app/Enums/TaskStatus.php`                       | `pending`, `in_progress`, `completed`                   |
| `app/Enums/TaskPriority.php`                     | `low`, `medium`, `high`                                 |
| `app/Events/TaskCreated.php`                     | Dispatched after task creation                          |
| `app/Events/TaskUpdated.php`                     | Dispatched after task update                            |
| `app/Events/TaskCompleted.php`                   | Dispatched when status transitions to `completed`       |
| `app/Http/Controllers/Api/V1/TaskController.php` | Thin controller: request → service → resource           |
| `app/Http/Requests/StoreTaskRequest.php`         | Validation for create                                   |
| `app/Http/Requests/UpdateTaskRequest.php`        | Validation for update                                   |
| `app/Policies/TaskPolicy.php`                    | Role-based authorization per action                     |
| `app/Services/TaskService.php`                   | Business logic including `TaskCompleted` event dispatch |
| `app/Repositories/TaskRepository.php`            | Query construction                                      |
| `app/Transformers/TaskResource.php`              | API response shape                                      |

## Endpoints

All routes require: `auth:sanctum`, `tenant`, `tenant.member`, `throttle:api`, `tenant.api.rate`

| Method      | Path                                      | Required Role            | Description                  |
| ----------- | ----------------------------------------- | ------------------------ | ---------------------------- |
| `GET`       | `/api/v1/projects/{project}/tasks`        | Any member               | List all tasks for a project |
| `POST`      | `/api/v1/projects/{project}/tasks`        | `owner`/`admin`/`member` | Create a task                |
| `GET`       | `/api/v1/projects/{project}/tasks/{task}` | Any member               | View a task                  |
| `PUT/PATCH` | `/api/v1/projects/{project}/tasks/{task}` | `owner`/`admin`/`member` | Update a task                |
| `DELETE`    | `/api/v1/projects/{project}/tasks/{task}` | `owner`/`admin`          | Delete a task                |

## Scoped Route Binding

The `{task}` route parameter is resolved through the `{project}` relationship. If the task does not belong to the resolved project, Laravel returns `404` — preventing cross-project data access even within the same tenant.

## Task Status Lifecycle

```bash
pending  →  in_progress  →  completed
                              ↓
                       TaskCompleted event
                       (recorded in activity + audit logs)
```

A `TaskCompleted` event is dispatched only when the status transitions to `completed`, not on every update.

## Domain Events

| Event           | When dispatched           | Listeners                                                          |
| --------------- | ------------------------- | ------------------------------------------------------------------ |
| `TaskCreated`   | After creation            | `ActivityLog::LogTaskCreated`, `AuditLog::LogTaskCreatedAudit`     |
| `TaskUpdated`   | After update              | `ActivityLog::LogTaskUpdated`, `AuditLog::LogTaskUpdatedAudit`     |
| `TaskCompleted` | On `completed` transition | `ActivityLog::LogTaskCompleted`, `AuditLog::LogTaskCompletedAudit` |

## Database

| Column                     | Type             | Purpose                               |
| -------------------------- | ---------------- | ------------------------------------- |
| `id`                       | bigint           | Primary key                           |
| `tenant_id`                | FK → tenants     | Tenant isolation                      |
| `project_id`               | FK → projects    | Parent project                        |
| `title`                    | string           | Task name                             |
| `description`              | text\|null       | Optional detail                       |
| `status`                   | enum             | `pending`, `in_progress`, `completed` |
| `priority`                 | enum             | `low`, `medium`, `high`               |
| `assigned_to`              | FK → users\|null | Optional assignee                     |
| `due_at`                   | timestamp\|null  | Optional due date                     |
| `created_at`, `updated_at` | timestamps       | Audit timestamps                      |
