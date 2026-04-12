# API Overview

All primary endpoints are versioned under `/api/v1`.

## Auth

- `POST /api/v1/register`
- `POST /api/v1/login`
- `POST /api/v1/logout`
- `GET /api/v1/me`

## Tenants and Membership

- `GET /api/v1/tenants`
- `POST /api/v1/tenants`
- `GET /api/v1/memberships`
- `POST /api/v1/memberships`
- `POST /api/v1/invitations`

## Projects and Tasks

- `GET /api/v1/projects`
- `POST /api/v1/projects`
- `GET /api/v1/projects/{project}/tasks`
- `POST /api/v1/projects/{project}/tasks`

## Billing

- `GET /api/v1/billing/plans`
- `POST /api/v1/billing/subscribe`
- `PATCH /api/v1/billing/subscription`
- `DELETE /api/v1/billing/subscription`
- `POST /api/v1/billing/webhook`

## Observability

- `GET /api/v1/activity-logs`
- `GET /api/v1/audit-logs`
- `GET /api/v1/billing/usage`

For full endpoint details and behavior, check the main README and module-level READMEs.
