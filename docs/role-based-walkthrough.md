# Role-Based Walkthrough (Owner, Admin, Member)

This guide uses one realistic tenant scenario from setup through billing and audit verification, with end-to-end requests you can run in Postman.

## Scenario

Company: Acme Inc

People:

- Owner: creates tenant, subscribes billing, verifies audit trail.
- Admin: manages projects/tasks and team operations.
- Member: executes assigned work and views allowed data.

Goal:

- Create tenant and team.
- Run project/task operations by role.
- Execute billing actions as owner.
- Verify activity and audit records.

## Postman Setup

Create a Postman environment with these variables:

| Variable | Example | Notes |
| --- | --- | --- |
| `base_url` | `http://localhost:8000` | Local app URL |
| `owner_email` | `owner@acme.test` | Owner login email |
| `owner_password` | `password` | Owner login password |
| `admin_email` | `admin@acme.test` | Admin login email |
| `admin_password` | `password` | Admin login password |
| `member_email` | `member@acme.test` | Member login email |
| `member_password` | `password` | Member login password |
| `owner_token` | (set from login response) | Bearer token |
| `admin_token` | (set from login response) | Bearer token |
| `member_token` | (set from login response) | Bearer token |
| `tenant_id` | (set from create/list tenant) | Tenant context |
| `project_id` | (set from create project) | Project context |
| `task_id` | (set from create task) | Task context |
| `invitation_token` | (set from invitation response) | Invitation flow |

Recommended Postman test script after login request:

```javascript
const data = pm.response.json();
pm.environment.set('owner_token', data.token || data.access_token || '');
```

Use equivalent scripts for `admin_token` and `member_token`.

## End-to-End Flow

## 1) Owner Registers and Logs In

Request: Register owner

```http
POST {{base_url}}/api/v1/register
Accept: application/json
Content-Type: application/json

{
  "name": "Acme Owner",
  "email": "{{owner_email}}",
  "password": "{{owner_password}}",
  "password_confirmation": "{{owner_password}}"
}
```

Request: Login owner

```http
POST {{base_url}}/api/v1/login
Accept: application/json
Content-Type: application/json

{
  "email": "{{owner_email}}",
  "password": "{{owner_password}}"
}
```

Expected:

- Owner token is returned and stored in `owner_token`.

## 2) Owner Creates Tenant

```http
POST {{base_url}}/api/v1/tenants
Accept: application/json
Authorization: Bearer {{owner_token}}
Content-Type: application/json

{
  "name": "Acme Inc"
}
```

Expected:

- Tenant record is created.
- Save returned id to `tenant_id`.

## 3) Owner Invites Admin and Member

Invite admin:

```http
POST {{base_url}}/api/v1/invitations
Accept: application/json
Authorization: Bearer {{owner_token}}
X-Tenant-ID: {{tenant_id}}
Content-Type: application/json

{
  "email": "{{admin_email}}",
  "role": "admin"
}
```

Invite member:

```http
POST {{base_url}}/api/v1/invitations
Accept: application/json
Authorization: Bearer {{owner_token}}
X-Tenant-ID: {{tenant_id}}
Content-Type: application/json

{
  "email": "{{member_email}}",
  "role": "member"
}
```

Expected:

- Invitation entries are created.
- Save each invitation token if returned in API response.

## 4) Admin and Member Accept Invitation

For each user:

1. Register/login user account.
2. Accept invitation.

Accept invitation:

```http
POST {{base_url}}/api/v1/invitations/{{invitation_token}}/accept
Accept: application/json
Authorization: Bearer {{admin_token}}
```

Expected:

- Membership created with role from invitation.

## 5) Admin Manages Delivery Work

Admin creates project:

```http
POST {{base_url}}/api/v1/projects
Accept: application/json
Authorization: Bearer {{admin_token}}
X-Tenant-ID: {{tenant_id}}
Content-Type: application/json

{
  "name": "Q2 Onboarding Revamp"
}
```

Admin creates task:

```http
POST {{base_url}}/api/v1/projects/{{project_id}}/tasks
Accept: application/json
Authorization: Bearer {{admin_token}}
X-Tenant-ID: {{tenant_id}}
Content-Type: application/json

{
  "title": "Implement onboarding checklist",
  "status": "open",
  "priority": "high"
}
```

Admin views membership list:

```http
GET {{base_url}}/api/v1/memberships
Accept: application/json
Authorization: Bearer {{admin_token}}
X-Tenant-ID: {{tenant_id}}
```

Expected:

- Admin can manage project/task lifecycle under tenant.

## 6) Member Executes Assigned Work

Member lists tasks:

```http
GET {{base_url}}/api/v1/projects/{{project_id}}/tasks?status=open&sort=updated_desc
Accept: application/json
Authorization: Bearer {{member_token}}
X-Tenant-ID: {{tenant_id}}
```

Member updates task status:

```http
PATCH {{base_url}}/api/v1/projects/{{project_id}}/tasks/{{task_id}}
Accept: application/json
Authorization: Bearer {{member_token}}
X-Tenant-ID: {{tenant_id}}
Content-Type: application/json

{
  "status": "in_progress"
}
```

Expected:

- Allowed member actions succeed.
- Restricted actions (for example billing management) return authorization errors.

## 7) Owner Handles Billing

Owner lists plans:

```http
GET {{base_url}}/api/v1/billing/plans
Accept: application/json
Authorization: Bearer {{owner_token}}
X-Tenant-ID: {{tenant_id}}
```

Owner subscribes tenant:

```http
POST {{base_url}}/api/v1/billing/subscribe
Accept: application/json
Authorization: Bearer {{owner_token}}
X-Tenant-ID: {{tenant_id}}
Content-Type: application/json

{
  "plan": "pro"
}
```

Owner checks usage and invoices:

```http
GET {{base_url}}/api/v1/billing/usage?months=3
Accept: application/json
Authorization: Bearer {{owner_token}}
X-Tenant-ID: {{tenant_id}}
```

```http
GET {{base_url}}/api/v1/billing/invoices
Accept: application/json
Authorization: Bearer {{owner_token}}
X-Tenant-ID: {{tenant_id}}
```

Expected:

- Billing state updates for tenant.
- Usage metrics and invoice listing available.

## 8) Verify Activity and Audit Records

Owner checks activity feed:

```http
GET {{base_url}}/api/v1/activity-logs
Accept: application/json
Authorization: Bearer {{owner_token}}
X-Tenant-ID: {{tenant_id}}
```

Owner checks audit trail:

```http
GET {{base_url}}/api/v1/audit-logs
Accept: application/json
Authorization: Bearer {{owner_token}}
X-Tenant-ID: {{tenant_id}}
```

What to verify:

- Invitation creation and acceptance entries.
- Project/task create/update actions.
- Billing subscribe/swap/cancel events.
- Actor identity and tenant scope on each record.

## 9) Optional Negative Authorization Checks

Use these checks to confirm role boundaries:

- Member calling `POST /api/v1/billing/subscribe` should fail (typically 403).
- Admin or member calling owner-only tenant ownership transfer should fail.
- Any token with wrong `X-Tenant-ID` should fail tenant membership checks.

## Postman-Ready Request Map

Use this as a quick request checklist in a Postman collection:

| Flow | Method | Path | Auth Token | Tenant Header |
| --- | --- | --- | --- | --- |
| Register | `POST` | `/api/v1/register` | None | No |
| Login | `POST` | `/api/v1/login` | None | No |
| Create tenant | `POST` | `/api/v1/tenants` | owner | No |
| Invite user | `POST` | `/api/v1/invitations` | owner/admin | Yes |
| Accept invite | `POST` | `/api/v1/invitations/{token}/accept` | invited user | No |
| Create project | `POST` | `/api/v1/projects` | owner/admin | Yes |
| Create task | `POST` | `/api/v1/projects/{project}/tasks` | owner/admin/member by policy | Yes |
| Subscribe | `POST` | `/api/v1/billing/subscribe` | owner | Yes |
| Usage | `GET` | `/api/v1/billing/usage` | owner/admin per policy | Yes |
| Activity logs | `GET` | `/api/v1/activity-logs` | tenant member | Yes |
| Audit logs | `GET` | `/api/v1/audit-logs` | authorized member | Yes |

## Common Errors and Fixes Matrix

| Status | Typical Cause | Quick Fix |
| --- | --- | --- |
| `401 Unauthorized` | Missing/invalid bearer token | Re-login and set correct token in Postman auth tab |
| `403 Forbidden` | Role or tenant membership mismatch | Use correct role token and ensure user belongs to `tenant_id` |
| `404 Not Found` | Resource exists in different tenant scope | Confirm `X-Tenant-ID` matches resource ownership |
| `422 Unprocessable Entity` | Validation failure in request body | Match payload to endpoint required fields and enum values |
| `429 Too Many Requests` | Tenant plan API throttling reached | Retry later or upgrade plan limits |
| `400/402 Billing Errors` | Missing payment setup or plan payload issue | Check billing payload, payment method setup, and Stripe test configuration |

## Fast Debug Checklist

1. Confirm request uses correct token for the intended role.
2. Confirm `X-Tenant-ID` is present on tenant-protected routes.
3. Check response body `message` and validation `errors` keys.
4. Re-test with owner token to isolate role-policy issues.
5. Verify event side effects in activity and audit endpoints.
