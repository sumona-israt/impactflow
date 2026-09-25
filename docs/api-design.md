# ImpactFlow — API Design

## 1. Conventions

- Base path: `/api/v1/`. Auth bootstrap route `GET /sanctum/csrf-cookie` lives outside the versioned prefix (Sanctum convention).
- **Success envelope:**
  ```json
  { "data": { }, "meta": { "page": 1, "per_page": 25, "total": 100 } }
  ```
  `meta` is present on paginated list endpoints only; single-resource responses return `{"data": {...}}` with no `meta`.
- **Validation error envelope** (HTTP 422, Laravel's native `FormRequest` shape — not reinvented):
  ```json
  { "message": "The given data was invalid.", "errors": { "email": ["The email field is required."] } }
  ```
- **Other errors:** `401` unauthenticated, `403` forbidden (policy denial), `404` not found, `409` conflict (e.g. budget exceeded), `429` rate limited, `500` never exposes internals — a generic `{"message": "Server error."}` plus server-side logging.
- Pagination: `?page=&per_page=` (default 25, max 100). Filtering: `?filter[status]=active`. Sorting: `?sort=-created_at`. Search: `?q=`.
- All mutating endpoints require the `X-XSRF-TOKEN` header (from the `XSRF-TOKEN` cookie) per Sanctum SPA convention; unauthenticated requests to protected routes return `401` with no redirect (the frontend handles redirects).
- Authorization is enforced by Policies on every endpoint — a route existing does not imply the caller can use it a given way; 403s are expected and normal from the frontend's perspective.

## 2. Endpoint inventory

Status: ✅ implemented (Phase 1) · ⏳ designed, implemented in the noted phase.

### Auth — Phase 1 ✅
| Method | Path | Notes |
|---|---|---|
| GET | `/sanctum/csrf-cookie` | bootstrap CSRF cookie |
| POST | `/api/v1/login` | email+password → session cookie |
| POST | `/api/v1/logout` | invalidate session |
| GET | `/api/v1/user` | current user + roles/permissions |

### System — Phase 1 ✅ / Phase 2 ✅
| Method | Path | Notes |
|---|---|---|
| GET | `/api/health` | ✅ DB + Redis connectivity check |
| GET | `/api/v1/audit-logs` | ✅ filterable by `user_id`/`action`/`entity_type`/`date_from`/`date_to`; export deferred to Phase 6's reporting export |

### Users & roles — Phase 2 ✅
| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/users` | list, paginated, `?q=` search, `filter[role]`, `filter[is_active]` |
| POST | `/api/v1/users` | create + assign initial roles |
| GET/PUT | `/api/v1/users/{user}` | view / update profile fields |
| PUT | `/api/v1/users/{user}/roles` | sync roles |
| PATCH | `/api/v1/users/{user}/active` | activate/deactivate (self-deactivation blocked) |
| GET | `/api/v1/roles` | list roles with their permissions |
| PUT | `/api/v1/roles/{role}/permissions` | sync a role's permissions |
| GET | `/api/v1/permissions` | list all defined permissions |

All SuperAdmin-only today (via granular permissions, not a hardcoded role check — see `App\Enums\PermissionEnum`), policy-enforced server-side.

### Organization — Phase 3 ⏳
`/api/v1/departments`, `/api/v1/branches`, `/api/v1/program-categories`, `/api/v1/expense-categories` (admin CRUD).

### Programs — Phase 3 ⏳
`/api/v1/programs` (CRUD, search/filter/sort/paginate), `/api/v1/programs/{id}/beneficiaries`, `/api/v1/programs/{id}/activities`, `/api/v1/programs/{id}/expenses`, `/api/v1/programs/{id}/budget`, `/api/v1/programs/{id}/submit-for-approval`.

### Beneficiaries — Phase 3 ⏳
`/api/v1/beneficiaries` (CRUD, search/filter), `/api/v1/beneficiaries/{id}/enroll`, `/api/v1/beneficiaries/{id}/documents`, `/api/v1/beneficiaries/{id}/data-quality`.

### Employees & volunteers — Phase 3 ⏳
`/api/v1/employees`, `/api/v1/volunteers` (CRUD).

### Activities — Phase 3 ⏳
`/api/v1/activities`, `/api/v1/activities/{id}/attendance`.

### Expenses & assets — Phase 4 ⏳
`/api/v1/expenses` (CRUD + `/submit`, `/approve`, `/reject`), `/api/v1/assets` (CRUD + `/assign`, `/return`).

### Workflows — Phase 4 ⏳
`/api/v1/workflows` (admin config), `/api/v1/workflow-instances/{id}/actions` (approve/reject/return/request-changes/escalate).

### Data quality & imports — Phase 5 ⏳
`/api/v1/imports` (`POST` upload, `GET` history), `/api/v1/imports/{id}/mapping`, `/api/v1/imports/{id}/preview`, `/api/v1/imports/{id}/commit`, `/api/v1/data-quality/issues` (list/resolve/ignore), `/api/v1/data-quality/score`.

### Reports — Phase 6 ⏳
`/api/v1/reports/program-performance`, `/api/v1/reports/beneficiaries`, `/api/v1/reports/financial`, `/api/v1/reports/data-quality`, each with `?format=csv|xlsx|pdf`.

### Odoo — Phase 7 ⏳
`/api/v1/odoo/status`, `/api/v1/odoo/sync-logs`, `/api/v1/odoo/sync/{entity}/{id}/retry`, `/api/v1/odoo/config` (SuperAdmin).

### Search — Phase 6 ⏳
`GET /api/v1/search?q=` — federated search across programs/beneficiaries/employees/volunteers/expenses/assets, each result tagged with entity type.

## 3. OpenAPI documentation

Once endpoints beyond Phase 1 exist, they are documented via `laravel-openapi` (or hand-maintained `docs/openapi.yaml`) covering request/response schemas and examples for every route above. Not generated in Phase 1 since only 4 routes exist; introduced in Phase 3 once there's a real surface worth documenting.
