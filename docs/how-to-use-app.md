# How to Use the App

This guide covers the full day-to-day usage flow for SaaS Starter from signup to tenant operations, billing, and admin features.

## Before You Start

Run the app locally:

```bash
composer run dev
```

Use this base URL in examples:

```text
http://localhost:8000
```

Optional shell variables:

```bash
export BASE_URL="http://localhost:8000"
export TOKEN="<sanctum-token>"
export TENANT_ID="<tenant-id>"
export PROJECT_ID="<project-id>"
```

## 1) Create Account and Sign In

Register:

```bash
curl -X POST "$BASE_URL/api/v1/register" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Demo User",
    "email": "demo@example.com",
    "password": "password",
    "password_confirmation": "password"
  }'
```

Login and capture token:

```bash
curl -X POST "$BASE_URL/api/v1/login" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "demo@example.com",
    "password": "password"
  }'
```

Verify authenticated session:

```bash
curl -X GET "$BASE_URL/api/v1/me" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 2) Create and Manage Tenant

Create tenant:

```bash
curl -X POST "$BASE_URL/api/v1/tenants" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Acme Inc"}'
```

List your tenants:

```bash
curl -X GET "$BASE_URL/api/v1/tenants" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Show one tenant:

```bash
curl -X GET "$BASE_URL/api/v1/tenants/$TENANT_ID" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Update tenant:

```bash
curl -X PATCH "$BASE_URL/api/v1/tenants/$TENANT_ID" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Acme Renamed"}'
```

Transfer ownership:

```bash
curl -X POST "$BASE_URL/api/v1/tenants/$TENANT_ID/transfer-ownership" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{"user_id":2}'
```

## 3) Use Tenant-Protected Endpoints

Tenant routes require:

- `Authorization: Bearer <token>`
- `X-Tenant-ID: <tenant-id>`

Example:

```bash
curl -X GET "$BASE_URL/api/v1/projects" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID"
```

## 4) Membership and Invitations

List memberships:

```bash
curl -X GET "$BASE_URL/api/v1/memberships" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID"
```

Add member directly:

```bash
curl -X POST "$BASE_URL/api/v1/memberships" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{"user_id":2,"role":"member"}'
```

Invite by email:

```bash
curl -X POST "$BASE_URL/api/v1/invitations" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{"email":"member@example.com","role":"member"}'
```

Public invitation flow:

```bash
curl -X GET "$BASE_URL/api/v1/invitations/<token>" -H "Accept: application/json"
curl -X POST "$BASE_URL/api/v1/invitations/<token>/accept" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 5) Projects and Tasks

Create project:

```bash
curl -X POST "$BASE_URL/api/v1/projects" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{"name":"Initial Platform Build"}'
```

Project list with filters and pagination:

```bash
curl -X GET "$BASE_URL/api/v1/projects?q=platform&sort=updated_desc&per_page=10&page=1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID"
```

Create task:

```bash
curl -X POST "$BASE_URL/api/v1/projects/$PROJECT_ID/tasks" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{"title":"Set up onboarding flow","status":"open","priority":"medium"}'
```

Task list with filters:

```bash
curl -X GET "$BASE_URL/api/v1/projects/$PROJECT_ID/tasks?status=open&priority=medium&sort=due_asc" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID"
```

## 6) Billing and Payment Operations

List plans:

```bash
curl -X GET "$BASE_URL/api/v1/billing/plans" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID"
```

Subscribe:

```bash
curl -X POST "$BASE_URL/api/v1/billing/subscribe" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{"plan":"pro"}'
```

Swap or cancel subscription:

```bash
curl -X PATCH "$BASE_URL/api/v1/billing/subscription" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{"plan":"enterprise"}'

curl -X DELETE "$BASE_URL/api/v1/billing/subscription" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID"
```

Payment methods and invoices:

```bash
curl -X GET "$BASE_URL/api/v1/billing/payment-methods" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID"

curl -X GET "$BASE_URL/api/v1/billing/invoices" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID"
```

Usage dashboard:

```bash
curl -X GET "$BASE_URL/api/v1/billing/usage?months=3" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Tenant-ID: $TENANT_ID"
```

Webhook endpoint for Stripe:

```text
POST /api/v1/billing/webhook
```

## 7) Notifications and Profile

List notifications:

```bash
curl -X GET "$BASE_URL/api/v1/notifications" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Mark all as read:

```bash
curl -X PATCH "$BASE_URL/api/v1/notifications/read-all" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Update profile and password:

```bash
curl -X PATCH "$BASE_URL/api/v1/profile" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Updated Name"}'

curl -X PATCH "$BASE_URL/api/v1/profile/password" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"current_password":"password","password":"new-password","password_confirmation":"new-password"}'
```

## 8) API Tokens and Session Bootstrap

List and create personal access tokens:

```bash
curl -X GET "$BASE_URL/api/v1/tokens" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

curl -X POST "$BASE_URL/api/v1/tokens" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"postman","abilities":["*"]}'
```

Session bootstrap endpoint:

```bash
curl -X GET "$BASE_URL/api/v1/session/bootstrap" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

Logout:

```bash
curl -X POST "$BASE_URL/api/v1/logout" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"
```

## 9) Admin and Observability Endpoints

Admin routes:

```text
GET /api/v1/admin/dashboard
POST /api/v1/admin/impersonate/{user}
```

Observability routes:

```text
GET /api/v1/activity-logs
GET /api/v1/audit-logs
```

Landing analytics routes:

```text
POST /track/landing-event
GET /track/landing-report
GET /app/analytics
```

## Troubleshooting

- 401 Unauthorized: token missing, expired, or invalid.
- 403 Forbidden: user not in tenant or missing role.
- 404 Not Found: ID not in current tenant scope.
- 422 Validation Error: payload does not match FormRequest rules.
- 429 Too Many Requests: tenant plan throttle exceeded.

If frontend changes are not visible:

```bash
npm run dev
npm run build
composer run dev
```
