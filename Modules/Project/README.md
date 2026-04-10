# Project Module

Manages tenant-scoped projects with full CRUD, domain events, and policy-based authorization.

## Responsibility

Projects are the primary domain resource. Every project belongs to exactly one tenant, enforced by a global Eloquent scope. All mutations dispatch domain events that feed into the ActivityLog and AuditLog modules automatically — no instrumentation required in controllers.

## Key Files

| File                                                | Purpose                                                       |
| --------------------------------------------------- | ------------------------------------------------------------- |
| `app/Models/Project.php`                            | Project model with `TenantScope` and `HasMany` tasks relation |
| `app/Events/ProjectCreated.php`                     | Dispatched after a project is created                         |
| `app/Events/ProjectUpdated.php`                     | Dispatched after a project is updated                         |
| `app/Events/ProjectDeleted.php`                     | Dispatched after a project is deleted                         |
| `app/Http/Controllers/Api/V1/ProjectController.php` | Thin controller: request → service → resource                 |
| `app/Http/Requests/StoreProjectRequest.php`         | Validation and authorization for create                       |
| `app/Http/Requests/UpdateProjectRequest.php`        | Validation and authorization for update                       |
| `app/Policies/ProjectPolicy.php`                    | Role-based authorization per action                           |
| `app/Services/ProjectService.php`                   | Business logic: create, update, delete with event dispatch    |
| `app/Repositories/ProjectRepository.php`            | Query construction decoupled from service                     |
| `app/Transformers/ProjectResource.php`              | API response shape                                            |

## Endpoints

All routes require: `auth:sanctum`, `tenant`, `tenant.member`, `throttle:api`, `tenant.api.rate`

| Method      | Path                         | Required Role   | Middleware Extra             | Description                      |
| ----------- | ---------------------------- | --------------- | ---------------------------- | -------------------------------- |
| `GET`       | `/api/v1/projects`           | Any member      | —                            | List all projects for the tenant |
| `POST`      | `/api/v1/projects`           | `owner`/`admin` | `feature.limit:max_projects` | Create a project                 |
| `GET`       | `/api/v1/projects/{project}` | Any member      | —                            | View a project                   |
| `PUT/PATCH` | `/api/v1/projects/{project}` | `owner`/`admin` | —                            | Update a project                 |
| `DELETE`    | `/api/v1/projects/{project}` | `owner`         | —                            | Delete a project                 |

## Feature Limit Integration

`POST /api/v1/projects` is gated by `EnsureFeatureLimit:max_projects`. If the tenant has reached their plan's project limit, the request returns `402` before reaching the controller.

## Domain Events

| Event            | When dispatched           | Listeners                                                            |
| ---------------- | ------------------------- | -------------------------------------------------------------------- |
| `ProjectCreated` | After successful creation | `ActivityLog::LogProjectCreated`, `AuditLog::LogProjectCreatedAudit` |
| `ProjectUpdated` | After successful update   | `ActivityLog::LogProjectUpdated`, `AuditLog::LogProjectUpdatedAudit` |
| `ProjectDeleted` | After successful deletion | `ActivityLog::LogProjectDeleted`, `AuditLog::LogProjectDeletedAudit` |

## Data Isolation

`Project` applies `TenantScope` in `booted()`. Any query against `Project` automatically scopes to the tenant resolved by the current request — cross-tenant data access is structurally impossible.

## Database

| Column                     | Type         | Purpose              |
| -------------------------- | ------------ | -------------------- |
| `id`                       | bigint       | Primary key          |
| `tenant_id`                | FK → tenants | Tenant ownership     |
| `name`                     | string       | Project name         |
| `description`              | text\|null   | Optional description |
| `created_at`, `updated_at` | timestamps   | Audit timestamps     |
