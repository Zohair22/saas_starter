# User Module

Handles user registration, authentication, profile management, and API token management.

## Responsibility

Owns everything related to a user's identity: how they register, log in, and interact with the API. Also manages personal Sanctum access tokens (the developer platform layer), with full audit trail integration on token issuance and revocation.

## Key Files

| File                                                 | Purpose                                         |
| ---------------------------------------------------- | ----------------------------------------------- |
| `app/Models/User.php`                                | Core user model with `HasApiTokens` (Sanctum)   |
| `app/Http/Controllers/Api/V1/UserController.php`     | Register, login, logout, profile, delete        |
| `app/Http/Controllers/Api/V1/ApiTokenController.php` | List, create, and revoke personal access tokens |
| `app/Http/Requests/CreateApiTokenRequest.php`        | Validates token name and abilities              |
| `app/Providers/UserServiceProvider.php`              | Binds service interfaces                        |
| `routes/api.php`                                     | All user and token routes                       |

## Endpoints

### Authentication

| Method   | Path                   | Auth           | Description                              |
| -------- | ---------------------- | -------------- | ---------------------------------------- |
| `POST`   | `/api/v1/register`     | Public         | Create a new user account                |
| `POST`   | `/api/v1/login`        | Public         | Authenticate; returns Sanctum token      |
| `POST`   | `/api/v1/logout`       | `auth:sanctum` | Revoke the current token                 |
| `GET`    | `/api/v1/me`           | `auth:sanctum` | Return authenticated user profile        |
| `GET`    | `/api/v1/users/{user}` | `auth:sanctum` | View a user                              |
| `DELETE` | `/api/v1/users/{user}` | `auth:sanctum` | Delete a user (self or policy-permitted) |

### API Token Management

| Method   | Path                       | Auth           | Description                                     |
| -------- | -------------------------- | -------------- | ----------------------------------------------- |
| `GET`    | `/api/v1/tokens`           | `auth:sanctum` | List all personal access tokens                 |
| `POST`   | `/api/v1/tokens`           | `auth:sanctum` | Issue a new named token with optional abilities |
| `DELETE` | `/api/v1/tokens/{tokenId}` | `auth:sanctum` | Revoke a specific token                         |

## API Token Security

- Tokens are scoped to the owning user — deletion is resolved via `PersonalAccessToken` with a `tokenable_id` constraint, preventing cross-user revocation
- Every `store` and `destroy` action records an entry in the `audit_logs` table via `AuditLogServiceInterface`
- Abilities default to `['*']` (full access) unless specified in the request body

## Request Body — Create Token

```json
{
    "name": "CI Deploy Token",
    "abilities": ["projects:read", "tasks:read"]
}
```

## Response — Create Token

```json
{
    "message": "API token created.",
    "token": "1|plaintext_token_here"
}
```

The plain-text token is only returned once. Store it securely.
