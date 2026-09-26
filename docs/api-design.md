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

### Organization — Phase 3 ✅
`/api/v1/departments`, `/api/v1/branches`, `/api/v1/program-categories` — list (broadly readable, needed for form selects) + create/update (HR/Admin Officer or Super Admin only). `expense-categories` moves to Phase 4 (see below) — it belongs to the Expense entity, which doesn't exist yet.

### Programs — Phase 3 ✅
`/api/v1/programs` (CRUD, search/filter/sort/paginate), `PATCH /api/v1/programs/{id}/status` (permission-gated direct status change — see `docs/database-design.md` §3 for why this isn't the generic workflow engine), `/api/v1/programs/{id}/beneficiaries` (enrolled list + enroll/unenroll), `/api/v1/programs/{id}/activities` (list + create). `/expenses` and `/budget` sub-resources move to Phase 4 alongside the Expense entity itself.

### Beneficiaries — Phase 3 ✅
`/api/v1/beneficiaries` (CRUD, search/filter), `/api/v1/beneficiaries/{id}/enrollments` (list programs they're enrolled in). `/documents` stays deferred — `beneficiary_documents` still isn't built (`docs/database-design.md` §4). Data quality issues live at the top-level `/api/v1/data-quality/issues` (see below), not nested under a beneficiary — one flat, filterable list rather than a per-entity sub-resource, since duplicate detection is currently the only issue type and it always names its own matched entity in the description.

### Employees & volunteers — Phase 3 ✅
`/api/v1/employees`, `/api/v1/volunteers` (CRUD, HR/Admin Officer or Super Admin only).

### Activities — Phase 3 ✅
`/api/v1/activities` (CRUD, scoped under a program), `/api/v1/activities/{id}/attendance` (record beneficiary attendance).

### Expenses & assets — Phase 4 ✅
| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/expense-categories` | broadly readable lookup |
| POST/PUT | `/api/v1/expense-categories(/{id})` | Finance Officer or Super Admin |
| GET | `/api/v1/expenses` | list, `?q=`, `filter[status]`, `filter[program_id]` |
| POST | `/api/v1/expenses` | create as `draft` |
| GET/PUT | `/api/v1/expenses/{expense}` | view / update (draft only) |
| POST | `/api/v1/expenses/{expense}/submit` | starts the approval workflow (see below) |
| POST | `/api/v1/expenses/{expense}/attachments` | upload a receipt (multipart) |
| GET | `/api/v1/expenses/{expense}/attachments/{attachment}/download` | authenticated, authorized stream — never a public URL |
| GET | `/api/v1/assets` | list |
| POST | `/api/v1/assets` | create |
| PUT | `/api/v1/assets/{asset}` | update |
| POST | `/api/v1/assets/{asset}/assign` | assign to a user |
| POST | `/api/v1/assets/{asset}/return` | close the open assignment |

### Workflows — Phase 4 ✅
`GET /api/v1/workflows` (read-only visibility into seeded workflow definitions — no create/update endpoint yet, see `docs/database-design.md` §8), `POST /api/v1/workflow-instances/{instance}/actions` (body: `{action: "approve"|"reject"|"return", comment?}` — generic, works for any entity implementing `App\Contracts\Workflowable`, not just Expense).

### Data quality & imports — Phase 5 ✅
| Method | Path | Notes |
|---|---|---|
| POST | `/api/v1/imports` | upload a CSV/XLSX (multipart), detects and returns column headers |
| GET | `/api/v1/imports` | list past imports, paginated |
| GET | `/api/v1/imports/{id}` | view one import's status/counts |
| PUT | `/api/v1/imports/{id}/mapping` | save the column → Beneficiary field mapping |
| POST | `/api/v1/imports/{id}/preview` | validate + detect duplicates, staging `data_import_rows` |
| GET | `/api/v1/imports/{id}/preview` | paginated staged rows (`?filter[status]=`) |
| POST | `/api/v1/imports/{id}/commit` | queues `ProcessDataImportCommit`; only valid once previewed |
| GET | `/api/v1/data-quality/issues` | list/filter (`?filter[status]=`) |
| POST | `/api/v1/data-quality/issues/{id}/resolve` | mark resolved |
| POST | `/api/v1/data-quality/issues/{id}/ignore` | mark ignored |
| GET | `/api/v1/data-quality/score` | `{score, total_beneficiaries, open_issues}` — see `docs/database-design.md` §9 for the formula |

### Dashboard — Phase 6 ✅
| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/dashboard/kpis` | Executive KPIs + chart series, permission-gated per section — see `docs/database-design.md` §11 |

### Reports — Phase 6 ✅
| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/reports` | List past generated reports, paginated, filtered to the caller's permitted report types |
| GET | `/api/v1/reports/{type}?format=csv\|xlsx\|pdf` | Generate a report (`type` one of `program-performance`, `beneficiaries`, `financial`, `data-quality`) and stream the file back in the same request; also accepts type-specific filters (e.g. `program_id`, `date_from`/`date_to`) |
| GET | `/api/v1/reports/{report}/download` | Re-download a previously generated report |

### Odoo — Phase 7 ✅
| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/odoo/status` | Mode, `is_active`, connectivity probe, per-entity synced/failed counts — see `docs/database-design.md` §10 |
| GET | `/api/v1/odoo/sync-logs` | Paginated, `?filter[status]=` |
| POST | `/api/v1/odoo/sync/{entity}/{id}/retry` | `{entity}` is one of `program`/`beneficiary`/`employee`/`expense`; re-dispatches `OdooSyncJob` |
| GET | `/api/v1/odoo/config` | mode/base_url/database (read-only, from env) + `is_active` |
| PUT | `/api/v1/odoo/config` | `{is_active}` only — SuperAdmin (`odoo.manageConfig`, never assigned to any other role by default) |

### Notifications — Phase 8 ✅
Every action here is scoped to the requesting user's own notifications — no `PermissionEnum` gating, since there's nothing to permission-check beyond "is this yours."

| Method | Path | Notes |
|---|---|---|
| GET | `/api/v1/notifications` | Paginated, newest first |
| GET | `/api/v1/notifications/unread-count` | `{count}` |
| POST | `/api/v1/notifications/{notification}/read` | 404s if the notification isn't the caller's own |
| POST | `/api/v1/notifications/read-all` | Marks every unread notification for the caller |

### Search — deferred
`GET /api/v1/search?q=` — federated search across programs/beneficiaries/employees/volunteers/expenses/assets, each result tagged with entity type. Previously tracked under "Phase 6" here; deferred out of that phase by product decision (Phase 6 shipped as dashboard + reports only, per `docs/implementation-plan.md`) rather than built alongside it. Revisit scope/phase assignment separately.

## 3. OpenAPI documentation

Once endpoints beyond Phase 1 exist, they are documented via `laravel-openapi` (or hand-maintained `docs/openapi.yaml`) covering request/response schemas and examples for every route above. Not generated in Phase 1 since only 4 routes exist; introduced in Phase 3 once there's a real surface worth documenting.
