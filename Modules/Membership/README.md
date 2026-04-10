# Membership Module

Manages team membership within a tenant: roles, permissions, and the full invitation workflow.

## Responsibility

Defines who belongs to a tenant and at what role level. Enforces role-based authorization on all tenant-protected routes via the `tenant.member` middleware. Handles the complete invitation lifecycle from sending to acceptance.

## Roles

| Role     | Capabilities                                                         |
| -------- | -------------------------------------------------------------------- |
| `owner`  | Full access; can manage all members and billing                      |
| `admin`  | Can manage members and invitations; cannot delete tenant             |
| `member` | Read access to tenant resources; cannot mutate membership or billing |

## Key Files

| File                                                   | Purpose                                               |
| ------------------------------------------------------ | ----------------------------------------------------- |
| `app/Models/Membership.php`                            | Tenant ↔ User join with `role` column                 |
| `app/Models/Invitation.php`                            | Pending invite with signed token and expiry           |
| `app/Http/Controllers/Api/V1/MembershipController.php` | CRUD for memberships                                  |
| `app/Http/Controllers/Api/V1/InvitationController.php` | Send, view, accept, revoke invitations                |
| `app/Policies/MembershipPolicy.php`                    | Role-based authorization rules                        |
| `app/Policies/InvitationPolicy.php`                    | Invitation management authorization                   |
| `app/Services/MembershipService.php`                   | Business logic for role changes and membership limits |
| `app/Services/InvitationService.php`                   | Token generation, acceptance, expiry validation       |

## Endpoints

### Memberships

| Method      | Path                               | Auth                    | Required Role   | Description                         |
| ----------- | ---------------------------------- | ----------------------- | --------------- | ----------------------------------- |
| `GET`       | `/api/v1/memberships`              | `auth:sanctum` + tenant | Any member      | List all memberships in tenant      |
| `POST`      | `/api/v1/memberships`              | `auth:sanctum` + tenant | `owner`/`admin` | Add a member (`max_users` enforced) |
| `GET`       | `/api/v1/memberships/{membership}` | `auth:sanctum` + tenant | Any member      | View a membership                   |
| `PUT/PATCH` | `/api/v1/memberships/{membership}` | `auth:sanctum` + tenant | `owner`/`admin` | Update role                         |
| `DELETE`    | `/api/v1/memberships/{membership}` | `auth:sanctum` + tenant | `owner`/`admin` | Remove a member                     |

### Invitations

| Method   | Path                                 | Auth                    | Required Role   | Description                            |
| -------- | ------------------------------------ | ----------------------- | --------------- | -------------------------------------- |
| `POST`   | `/api/v1/invitations`                | `auth:sanctum` + tenant | `owner`/`admin` | Send invitation                        |
| `GET`    | `/api/v1/invitations/{token}`        | Public                  | —               | View invitation by token               |
| `POST`   | `/api/v1/invitations/{token}/accept` | Public                  | —               | Accept invitation (creates membership) |
| `DELETE` | `/api/v1/invitations/{invitation}`   | `auth:sanctum` + tenant | `owner`/`admin` | Revoke invitation                      |

## Invitation Flow

```bash
Owner/Admin  →  POST /api/v1/invitations  →  Invitation stored with token
                                            ↓
                                  Invitee receives link (email or out-of-band)
                                            ↓
                               GET /api/v1/invitations/{token}  (preview)
                                            ↓
                              POST /api/v1/invitations/{token}/accept
                                            ↓
                              Membership created, invitation invalidated
```

## Feature Limit Integration

`POST /api/v1/memberships` passes through the `EnsureFeatureLimit:max_users` middleware. If the tenant is at or above their plan's `max_users` limit, the request is rejected with `402`.

## Database

| Table         | Key Columns                                                        |
| ------------- | ------------------------------------------------------------------ |
| `memberships` | `tenant_id`, `user_id`, `role`                                     |
| `invitations` | `tenant_id`, `email`, `role`, `token`, `accepted_at`, `expires_at` |
