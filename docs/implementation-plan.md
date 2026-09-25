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
| 4 | Finance & Assets | 🚧 In progress | Generic approval workflow engine (first used by Expense), expenses with receipt attachments, budget-vs-actual enforcement, assets + assignment tracking — see `docs/database-design.md` §§7-8 for scope decisions |
| 5 | Data Management | ⏳ Not started | CSV/Excel import pipeline, duplicate detection, data quality engine |
| 6 | Analytics | ⏳ Not started | Executive dashboard, KPIs, charts, report generation/export |
| 7 | Odoo Integration | ⏳ Not started | OdooClient/Service/Sync, mock mode, sync dashboard, retry handling |
| 8 | Notifications | ⏳ Not started | Email + in-app notifications, workflow alerts |
| 9 | Security & Hardening | ⏳ Not started | Authorization audit, rate limiting, secure file storage, structured logging |
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

None of these are functional yet beyond their Phase 1 prerequisites (auth, roles).
