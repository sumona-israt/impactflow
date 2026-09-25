# ImpactFlow

**NGO Operations. Connected. Automated. Measurable.**

ImpactFlow is an NGO operations and ERP integration platform: a Laravel + Next.js system for managing programs, beneficiaries, staff/volunteers, expenses and approvals, integrated with Odoo ERP, with data quality management, reporting, and audit logging layered on top.

> **Status: Phase 1 of 12 (Foundation).** This README describes what is actually built today, and links to the plan for everything else. See [`docs/implementation-plan.md`](docs/implementation-plan.md) for the full phase-by-phase roadmap and current status table.

## Overview

Most NGOs run programs, finance, HR, and beneficiary data across a patchwork of spreadsheets and a general-purpose ERP that was never built for casework or program delivery. ImpactFlow digitizes the operational side (programs, beneficiaries, activities, approvals) and integrates it with Odoo for the parts Odoo already does well (contacts, HR, projects, accounting), instead of duplicating ERP functionality or bolting everything onto spreadsheets.

## Problem statement

- Program and beneficiary data lives in disconnected spreadsheets with no audit trail, validation, or duplicate detection.
- Expense and program approvals happen over email with no enforced workflow or budget checks.
- ERP systems (like Odoo) hold financial/HR truth but have no NGO-specific program or beneficiary model, and NGOs end up either avoiding the ERP or hand-copying data into it.
- Leadership has no single, trustworthy view of program performance, spend, or data quality.

## Solution

A purpose-built operations layer (Laravel API + Next.js frontend) that owns NGO-specific entities (programs, beneficiaries, activities, workflows) and syncs the subset of data Odoo is authoritative for (contacts, employees, projects, expenses) through a dedicated, mockable integration layer — see [`docs/odoo-integration.md`](docs/odoo-integration.md).

## Key features (by phase)

See [`docs/implementation-plan.md`](docs/implementation-plan.md) for the authoritative, up-to-date status table. As of Phase 1: authentication (Sanctum SPA/cookie-based), role-based access control data model (`spatie/laravel-permission`, six roles), a health-check endpoint, and the full Docker/CI scaffolding described below. Everything else — programs, beneficiaries, expenses, workflows, data quality, reporting, Odoo sync, notifications — is designed in `docs/` and built out in the phases that follow.

## Architecture

See [`docs/architecture.md`](docs/architecture.md) for the full system diagram, backend/frontend structure, authentication design, and Docker topology. In short:

- **Backend:** Laravel 12 (PHP 8.3), PostgreSQL, Redis, service-oriented (`Actions`/`Services`, not fat controllers).
- **Frontend:** Next.js (App Router, TypeScript), Tailwind, shadcn/ui, TanStack Query.
- **Single origin:** nginx routes `/` to Next.js and `/api`/`/sanctum` to Laravel, so Sanctum's cookie-based SPA auth works without CORS.
- **Odoo integration:** isolated behind `app/Services/Odoo/*`, with a mock mode so the project runs without a live Odoo server (Phase 7).

## Technology stack

Laravel 12 · PHP 8.3 · Laravel Sanctum · `spatie/laravel-permission` · Pest · PostgreSQL · Redis · Next.js (App Router) · TypeScript · Tailwind CSS · shadcn/ui · TanStack Query · React Hook Form · Zod · Docker Compose · GitHub Actions.

## Database architecture

See [`docs/database-design.md`](docs/database-design.md) for the full normalized schema across all phases (identity/RBAC, organization, programs, beneficiaries, staff/volunteers, finance, workflow engine, data quality, Odoo integration, reporting). Phase 1 implements the identity/RBAC tables only; everything else is documented as target schema and built out per the phase table.

## Odoo integration architecture

See [`docs/odoo-integration.md`](docs/odoo-integration.md) — service layer design, entity mappings, mock-mode strategy, and honest failure/retry handling. Not yet implemented (Phase 7); documented now so the rest of the system is built consistent with it.

## API documentation

See [`docs/api-design.md`](docs/api-design.md) for conventions (`{data, meta}` envelope, error shapes, pagination/filtering) and the full endpoint inventory, marked implemented vs. planned.

## Authentication & authorization

Laravel Sanctum SPA (cookie/session) authentication — see [`docs/architecture.md`](docs/architecture.md#3-authentication--session-architecture) for why (short version: Livewire admin screens share the same session guard, so there's one identity system, not two). Six roles are seeded via `spatie/laravel-permission` (Super Administrator, Program Manager, Finance Officer, Field Officer, HR/Admin Officer, Management); policy-level enforcement of what each role can do lands in Phase 2 — Phase 1 only implements login/logout/current-user.

## Workflow engine

Designed in [`docs/database-design.md`](docs/database-design.md#8-approval-workflow-engine--phase-4-) as a generic, reusable engine (`approval_workflows` / `workflow_steps` / `workflow_instances` / `workflow_actions`) rather than one hardcoded per entity. Implemented in Phase 4.

## Data quality system

Designed to detect missing required fields, duplicate records, invalid phone/date formats, and orphaned references, surfaced as a data quality score and an issue queue (`data_quality_issues`). Implemented in Phase 5.

## Local development

### Prerequisites

- PHP 8.3+ and Composer (for fast local `artisan`/test iteration)
- Node.js 22+
- Docker + Docker Compose

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Start Postgres + Redis (from the repo root, in another terminal):
#   cp .env.example .env   (repo root .env, for docker-compose variable substitution)
#   docker compose up -d postgres redis

php artisan migrate --seed
./vendor/bin/pest      # run tests
./vendor/bin/pint      # code style
php artisan serve      # http://127.0.0.1:8000 (API only — see note below)
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev            # http://localhost:3000
```

**Important:** the frontend's Sanctum cookie auth flow depends on the frontend and API being served from the *same origin* (see architecture doc). `npm run dev` alone (without nginx in front) is useful for UI/component iteration, but logging in against a real API from `localhost:3000` will not work — the browser and Laravel would be on different origins. To exercise the real login flow end-to-end, run the full Docker Compose stack below.

### Full stack (Docker Compose — the validated path)

```bash
cp .env.example .env                       # repo root: Postgres bootstrap creds, APP_PORT
cp backend/.env.example backend/.env       # set DB_PASSWORD, run `php artisan key:generate --show` for APP_KEY
cp frontend/.env.example frontend/.env

docker compose up -d --build
curl http://localhost/api/health
```

Then open `http://localhost` — the whole app (Next.js + Laravel API) is served through nginx on one origin.

## Environment variables

| File | Purpose |
|---|---|
| `.env.example` (repo root) | Variables `docker-compose.yml` itself uses (Postgres bootstrap credentials, `APP_PORT`) |
| `backend/.env.example` | Laravel app config (DB, Redis, Sanctum, session, mail; documents the not-yet-implemented `ODOO_*` variables for Phase 7) |
| `frontend/.env.example` | Next.js config (`NEXT_PUBLIC_API_URL`, `INTERNAL_API_URL`, `SESSION_COOKIE_NAME`) |

## Docker setup

`docker-compose.yml` defines the full stack from day one: `nginx` (reverse proxy), `laravel` (php-fpm), `nextjs`, `postgres`, `redis`, `worker` (`queue:work`), `scheduler` (`schedule:work`) — see [`docs/architecture.md`](docs/architecture.md#5-docker-topology). All services have health checks; no secrets are baked into the compose file or images.

## Database setup

```bash
docker compose up -d postgres redis
cd backend && php artisan migrate --seed
```

The seeder creates the six roles and one demo Super Administrator (see below). Nothing else is seeded yet — beneficiary/program/staff demo data ships with Phase 3.

## Demo credentials

Seeded by `database/seeders/DemoAdminSeeder.php` — for local/demo use only, never used in a real deployment:

| Email | Password | Role |
|---|---|---|
| `admin@impactflow.test` | `password` (or `DEMO_ADMIN_PASSWORD` env override) | Super Administrator |

## Mock Odoo mode

Not yet implemented (Phase 7). Once built, `ODOO_MODE=mock` will let the entire system run and be demoed without a live Odoo server — see [`docs/odoo-integration.md`](docs/odoo-integration.md#5-mock-mode) for the design. Every Odoo-related screen will show a visible **"Mock Odoo Environment"** badge whenever mock mode is active, so nothing is ever presented as a real integration when it isn't.

## Live Odoo configuration

Not yet implemented (Phase 7). Will be configured via `ODOO_MODE=live` plus `ODOO_BASE_URL` / `ODOO_DATABASE` / `ODOO_USERNAME` / `ODOO_PASSWORD` — see [`docs/odoo-integration.md`](docs/odoo-integration.md#9-environment-variables). Never hardcoded, never committed.

## Testing

```bash
cd backend && ./vendor/bin/pest
cd frontend && npm run lint && npx tsc --noEmit && npm run build
```

Current backend coverage: login (success/failure/inactive-account), logout, current-user endpoint, health check, and the role/demo-admin seeder. Full unit/feature/API/workflow/E2E coverage is built out per phase (Phase 10 is dedicated to closing any remaining gaps) — see [`docs/implementation-plan.md`](docs/implementation-plan.md).

## CI/CD

`.github/workflows/ci.yml` runs on every push/PR: backend lint (Pint) + tests (Pest), frontend lint + type-check + build, and a Docker Compose build/smoke test hitting `/api/health`. No paid services required.

## Deployment

Not yet formalized beyond the Docker Compose stack described above — a production deployment target (and any platform-specific config) will be documented here once Phase 11 (CI/CD) is built out.

## Screenshots

Added once the UI has enough real screens to be worth screenshotting (Phase 3+). The current Phase 1 UI is a login page and an empty dashboard shell — see `docs/demo/` (added in Phase 12) for the eventual walkthrough.

## Project structure

```text
impactflow/
├── backend/          # Laravel 12 API
├── frontend/         # Next.js app
├── docker/           # nginx config, PHP + Next.js Dockerfiles
├── docs/             # architecture, database, API, Odoo integration, SOPs, guides
├── .github/workflows/ # CI
└── docker-compose.yml
```

See [`docs/architecture.md`](docs/architecture.md) for the full backend/frontend internal structure.

## Future improvements

Everything tracked in [`docs/implementation-plan.md`](docs/implementation-plan.md) phases 2–12: RBAC policy enforcement, programs/beneficiaries/staff/volunteers/activities, expense & asset management with an approval workflow engine, CSV/Excel import with duplicate detection and a data quality engine, an executive dashboard with real KPIs and reports, Odoo integration (mock + live) with a sync dashboard, notifications, a full security hardening pass, full test coverage, and complete SOP/admin/developer documentation.
