# ImpactFlow — Implementation Plan

## 1. What this document is

ImpactFlow is being built as a portfolio-grade **NGO Operations & ERP Integration platform**: a Laravel + Next.js system that digitizes core NGO processes (programs, beneficiaries, staff/volunteers, expenses, approvals) and integrates them with Odoo ERP, with data quality management, reporting, and audit logging layered on top.

This document is the single source of truth for **build order, current status, and scope boundaries**. It exists so that at any point in the project, it's clear what is real, what is documented-but-not-built, and what comes next. Status is updated at the end of every phase.

## 2. Ground rules carried through every phase

- No phase is declared done until: migrations run clean, seeders run clean, automated tests pass, lint/type-check pass, and the feature has been exercised end-to-end (via API call or browser).
- No TODO placeholders for core functionality. If something can't be finished in a phase, it is left out of that phase entirely rather than half-wired.
- No fabricated data presented as real. Mock/demo data is always labeled. Odoo mock mode is always visibly distinguishable from live mode.
- No credentials, tokens, or secrets committed. `.env.example` documents every variable; real values stay local/CI-secret only.
- Every phase ends with a documentation update (this file's status table, plus any doc whose subject changed).

## 3. Phase status

| Phase | Name | Status | Notes |
|---|---|---|---|
| 0 | Planning docs | ✅ Done | This doc + architecture/database/api/odoo-integration docs |
| 1 | Foundation | ✅ Done | Repo scaffolding, Laravel + Next.js skeletons, Docker (verified end-to-end via `docker compose up`), Sanctum auth (login/logout/user, tested), RBAC data model + seeded roles/demo admin, CI |
| 2 | RBAC | ✅ Done | Policies/Gates on users, roles & permissions; `AuditLogger` foundation used by every mutation in this phase; permission-driven admin UI (Users, Roles & Permissions, Audit Logs), tested end-to-end incl. a real cross-service auth bug found and fixed (see architecture.md §3) |
| 3 | Core NGO Operations | ✅ Done | Programs (with permission-gated status transitions), beneficiaries (list/detail with sensitive-field redaction), employees, volunteers, activities + attendance, org lookups (departments/branches/categories) — full CRUD UI, 41 backend tests, verified end-to-end via `docker compose`. See `docs/database-design.md` §§2-6 for scope simplifications made while implementing (program_budgets/program_locations/beneficiary_contacts/beneficiary_documents deferred, `odoo_*_id` columns deferred to Phase 7) |
| 4 | Finance & Assets | ✅ Done | Generic approval workflow engine (first used by Expense), expenses with receipt attachments + two-step approval chain, budget-vs-actual enforcement at final approval, assets + assignment/return tracking — full CRUD UI, 13 new backend tests (55 total), migrations/seeders verified clean, lint clean. See `docs/database-design.md` §§7-8 for scope decisions |
| 5 | Data Management | ✅ Done | CSV/Excel import pipeline (upload → map columns → preview → queued commit), duplicate detection (shared by manual entry and bulk import), data quality dashboard (list/resolve/ignore, score) — scoped to Beneficiaries, 15 new backend tests (70 total), migrations/seeders verified clean, lint + type-check clean. See `docs/database-design.md` §9 for scope decisions |
| 6 | Analytics | ✅ Done | Executive dashboard (7 permission-gated KPI sections — programs, beneficiaries, staffing, activities, finance, data quality, workflows — with 3 recharts charts, all derived live from Phase 1-5 data, zero fabricated values), 4 report types (Program Performance, Beneficiaries, Financial, Data Quality) exportable as CSV/XLSX/PDF via a shared builder/writer pipeline, synchronous generation (no queue — demo-scale row counts). Federated search stays deferred (see `docs/api-design.md`) — explicitly out of this phase's scope. 20 new backend tests (90 total), migrations/seeders verified clean, lint + type-check clean. See `docs/database-design.md` §11 for scope decisions |
| 7 | Odoo Integration | ✅ Done | Config-driven OdooClientInterface (FakeOdooClient mock / OdooJsonRpcClient live via JSON-RPC 2), OdooAuthService/OdooMappingService/OdooSyncService/OdooHealthService, `odoo_connections`/`odoo_mappings`/`odoo_sync_logs` tables, first domain events in this codebase (`ProgramApproved`, `ExpenseApproved`, `BeneficiaryRegistered`, `EmployeeCreated`) each dispatching a queued `OdooSyncJob` (5 tries, exponential backoff, `failed()` marks the sync log), a SuperAdmin-mutable `is_active` pause/resume toggle, a scheduled `odoo:poll` connectivity/change-detection command (no inbound field reconciliation — a materially separate feature, not attempted), and an Odoo Integration dashboard (status, sync log, retry, config). No custom Odoo module (`docs/odoo-integration.md` §8) — an explicitly optional stretch item. 14 new backend tests (103 total), migrations/seeders verified clean, lint + type-check clean. See `docs/database-design.md` §10 for scope decisions |
| 8 | Notifications | ✅ Done | Laravel's polymorphic `notifications` table; two new generic workflow-engine events (`WorkflowInstanceStarted`, `WorkflowInstanceActed`, dispatched from `WorkflowService` itself — entity-agnostic, unlike the Expense-specific `ExpenseApproved`) plus `ProgramSubmittedForApproval`; 4 queued notification classes (mail + in-app) covering "action needed" (reviewer), "decision recorded" (submitter, via a new `Workflowable::workflowOwner()` contract method), a program awaiting Management's approval, and an Odoo sync exhausting retries; notification center (topbar bell + `/notifications` page, unread count polling). Mail uses the existing `log` driver (already-honest "mock mode", nothing new to build there). 10 new backend tests (113 total), migrations/seeders verified clean, lint + type-check clean. See `docs/database-design.md` §11 for scope decisions |
| 9 | Security & Hardening | ✅ Done | Two adversarial audits (authorization; rate-limiting/storage/logging/CORS/secrets/docker) run before any code changed — found the authorization layer already solid (every one of ~90 controller actions across 24 controllers has a real check, no mass-assignment holes) and fixed only the confirmed gaps: `AuditLogger` now redacts PII fields (phone/DOB/address/emergency contacts), not just password fields, closing a real bypass via the separately-grantable `audit-logs.viewAny` permission; login (`throttle:login`, 5/min keyed by email+IP) and the whole API (`throttle:api`, 120/min keyed by user/IP) are now rate limited (previously zero limiting existed anywhere, confirmed by reading Laravel's actual middleware source, not assumed); the unmiddlewared `storage/{path}` signed-URL route is disabled (`serve: false` on the `local` disk) since nothing in the app ever used it and it sat underneath every controller's file-download authorization check; structured JSON logging + request/user correlation via Laravel's `Context` facade (`AddRequestContext` middleware, first file in `app/Http/Middleware/`); nginx security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy) and an explicit `config/cors.php`; a stale `/livewire` nginx route (Phase 8 already fixed the equivalent doc mention) removed. Explicitly accepted, not fixed: no per-branch/per-program record scoping anywhere (confirmed consistent by design, never documented otherwise), `Beneficiary`/`Volunteer`/`Asset`/`Activity` status sharing their general update permission (unlike `Program`/`Expense`'s dedicated gating), upload MIME-sniffing allowing a renamed script past `mimes:` validation (moot once the storage route above is disabled — never web-served or executed), CSP/HSTS deferred (need real tuning / TLS termination respectively, not a one-line default), and `APP_DEBUG`/prod-compose-file hygiene (a deployment-process gap, natural fit for Phase 11/CI-CD instead). 5 new backend tests (118 total), lint clean. See `docs/database-design.md` §1 for the redaction note |
| 10 | Testing | ⏳ Not started | Full unit/feature/API/workflow/integration/E2E coverage |
| 11 | CI/CD | ⏳ Not started | Full pipeline: lint, type-check, test, build, docker build/push |
| 12 | Documentation & Demo | ⏳ Not started | SOPs, admin/developer guides, demo scripts, CV description |

Phases are largely sequential but not strictly waterfall — e.g., audit logging (Phase 2) and Odoo groundwork (Phase 7 design) are referenced early because later phases depend on their shape.

## 4. Priority order if time is constrained

Per the original product brief, if scope must be cut, this order is preserved:

1. Odoo integration
2. RBAC
3. Programs
4. Beneficiaries
5. Approval workflows
6. Expense management
7. Data import/quality
8. Executive dashboard
9. Audit logs
10. Documentation

Decorative UI work never takes priority over this list.

## 5. Phase 1 (this iteration) — exact scope

**Included:**
- Monorepo layout (`backend/`, `frontend/`, `docker/`, `docs/`, `.github/workflows/`)
- Laravel 12 app: Postgres + Redis wired, Sanctum SPA auth, `spatie/laravel-permission` installed with a `RoleEnum` + seeder for the six roles, login/logout/user endpoints, `/api/health`, Pest tests for the above
- Next.js app: TypeScript, Tailwind, shadcn/ui, TanStack Query, typed API client with CSRF handling, login page, protected dashboard shell with placeholder (explicitly labeled, non-fabricated) KPI row
- Docker Compose: nginx (path-based reverse proxy), laravel(php-fpm), nextjs, postgres, redis, worker, scheduler — all with health checks
- GitHub Actions CI: backend lint+test, frontend lint+type-check+build, docker compose smoke test
- Root README (initial version) and this planning doc set

**Explicitly excluded from Phase 1** (tracked as Phase 2+ above): permission-based authorization enforcement beyond authentication, any business entity beyond `users`/roles, audit logging, notifications, Odoo integration code, data import/quality, reporting, dashboards with real data, full test coverage, SOPs/guides.

## 6. Demo scenarios (target state, built out progressively)

These are the demonstrations the finished system should support (see `docs/demo/demo-script.md` once Phase 12 lands):

1. Program Manager creates a program → submits for approval → Management approves → dashboard updates.
2. Field Officer registers a beneficiary → duplicate detection runs → data quality validation → program enrollment.
3. Field Officer submits an expense → Program Manager approves → Finance approves → Odoo sync fires → status visible on dashboard.
4. Admin uploads an Excel beneficiary list → column mapping → validation/duplicate report → commit → data quality dashboard updates.
5. Admin opens the Odoo Integration dashboard → reviews sync logs → simulates an Odoo outage → retries → sync recovers.

Scenarios 2 (duplicate detection on registration) and 4 (bulk import) are functional as of Phase 5. Scenario 1's "dashboard updates" clause is functional as of Phase 6 — the executive dashboard reflects real program/beneficiary/finance state, permission-gated per viewer. Scenarios 3 and 5 are fully functional as of Phase 7: an expense's final approval fires `ExpenseApproved`, queuing a real (mock-mode-by-default) Odoo sync visible on the Odoo Integration dashboard; setting `ODOO_MOCK_FAILURE_RATE=1` simulates an outage, and the dashboard's Retry action recovers a failed sync.
