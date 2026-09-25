# ImpactFlow — Architecture

## 1. System overview

```mermaid
flowchart TB
    U[Users - browser] --> N[nginx - reverse proxy, single origin]
    N -->|"/"| FE[Next.js frontend - App Router]
    N -->|"/api, /sanctum"| BE[Laravel API - Sanctum SPA auth]
    BE --> PG[(PostgreSQL)]
    BE --> R[(Redis - sessions, cache, queues)]
    BE --> Q[Queue workers - Laravel Horizon/queue:work]
    BE --> S[Scheduler - schedule:work]
    Q --> ODOO[Odoo ERP - live or mock]
    BE -.->|reporting queries| PG
    FE -->|SSR fetch, cookie forwarded| BE
```

`nginx` is the single entry point in both local Docker and any future deployment: `/` routes to Next.js, `/api` and `/sanctum` route to Laravel. This keeps the frontend and API on one registrable origin, which is what makes Sanctum's cookie-based SPA auth work without CORS/SameSite workarounds (see §3).

## 2. Backend structure

Laravel 12, PHP 8.3, service-oriented — business logic lives in `Actions`/`Services`, not controllers.

```text
backend/
├── app/
│   ├── Actions/          # single-purpose use-case classes (LoginAction, ApproveExpenseAction, ...)
│   ├── Console/          # scheduled commands
│   ├── DTOs/             # typed data transfer objects for cross-layer boundaries
│   ├── Enums/            # RoleEnum, ProgramStatus, ExpenseStatus, ... (PHP 8.3 backed enums)
│   ├── Events/           # domain events (ExpenseApproved, BeneficiaryRegistered, ...)
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   ├── Requests/     # FormRequest validation
│   │   └── Resources/    # API Resources -> {data, meta} envelope
│   ├── Jobs/             # queued work (Odoo sync, imports, report generation, emails)
│   ├── Listeners/
│   ├── Livewire/         # selected internal/admin workflows only (e.g. Odoo config screen)
│   ├── Mail/
│   ├── Models/
│   ├── Notifications/
│   ├── Policies/         # authorization, one per model, enforced via Gate/Policy
│   ├── Services/
│   │   ├── Odoo/         # OdooClient, OdooAuthService, OdooSyncService, OdooMappingService, OdooHealthService
│   │   ├── Workflow/      # generic approval-workflow engine
│   │   ├── Reporting/
│   │   ├── DataQuality/
│   │   └── Import/
│   └── Support/          # ApiResponse and other cross-cutting helpers
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── routes/
│   ├── api.php           # /api/v1/*
│   └── web.php           # Sanctum CSRF cookie route, Livewire admin routes
├── resources/views/      # Livewire/Blade views for admin-only screens
└── tests/
    ├── Feature/
    └── Unit/
```

**Scaffolding policy:** a directory above is only populated with real code when a phase genuinely needs it. `Repositories/` is intentionally absent — generic Eloquent CRUD repositories are an anti-pattern here; the one real gateway abstraction the project needs is `Services/Odoo/OdooClient`, which already has a home. `Policies/`, `Livewire/`, `Events/Listeners` gain their first files in Phase 2 (RBAC) and Phase 4 (workflow engine) respectively — not stubbed empty in Phase 1.

## 3. Authentication & session architecture

- **Mechanism:** Laravel Sanctum SPA (stateful, cookie-based) authentication — the same session guard used by Livewire admin screens. One identity system serves both the Next.js SPA and Livewire, so RBAC state never drifts between two auth mechanisms.
- **Flow:** Next.js calls `GET /sanctum/csrf-cookie` once, then `POST /api/v1/login` with credentials; Laravel sets an encrypted, HttpOnly session cookie (`SESSION_DRIVER=redis`). Subsequent requests carry the cookie automatically (same origin via nginx) and an `X-XSRF-TOKEN` header read from the non-HttpOnly `XSRF-TOKEN` cookie for CSRF protection.
- **Server-side rendering gotcha:** Next.js Server Components/Route Handlers do not automatically forward the browser's cookies when calling Laravel. `frontend/src/lib/auth/session.ts` exports `getServerAuth()`, the single seam every protected server component uses to read the incoming request's `cookie` header and forward it to `GET /api/v1/user`.
- **Future mobile path:** Sanctum's token abilities remain available (not wired into the primary web flow) as a documented option if a mobile client is ever added — no code exists for this in Phase 1, it's a reserved architectural option only.

## 4. Frontend structure

Next.js (App Router), TypeScript, Tailwind, shadcn/ui, TanStack Query.

```text
frontend/src/
├── app/
│   ├── (auth)/login/page.tsx
│   ├── (dashboard)/layout.tsx       # server-side auth gate + shell (sidebar/topbar)
│   └── (dashboard)/dashboard/page.tsx
├── components/
│   ├── ui/            # shadcn-generated primitives
│   └── layout/         # sidebar, topbar, nav
├── lib/
│   ├── api/
│   │   ├── client.ts   # fetch wrapper: credentials 'include', CSRF bootstrap, X-XSRF-TOKEN header
│   │   ├── errors.ts   # ApiError{status, message, fieldErrors} normalized from Laravel's 422 shape
│   │   └── endpoints/   # auth.ts now; programs.ts, beneficiaries.ts, ... added per later phase
│   ├── auth/session.ts # getServerAuth()
│   └── query-client.ts  # QueryClient + RSC hydration boundary setup
├── middleware.ts        # cheap cookie-presence redirect for protected route matcher
└── types/
```

Two-layer route protection: `middleware.ts` does a cheap cookie-presence check (fast, no network call); the `(dashboard)/layout.tsx` server component does the authoritative check via `getServerAuth()` and will carry role/permission data for permission-driven nav once Phase 2 lands. This seam is built once, in Phase 1, so every later module inherits it.

## 5. Docker topology

`docker-compose.yml` defines the full stack from day 1 (not deferred to phase-end), so infra drift is caught early:

| Service | Role |
|---|---|
| `nginx` | single entry point, path-based reverse proxy |
| `laravel` | php-fpm running the Laravel app |
| `nextjs` | Next.js app (standalone output) |
| `postgres` | primary datastore |
| `redis` | sessions, cache, queue driver |
| `worker` | `php artisan queue:work` — Odoo sync, imports, report generation, emails (populated as those jobs are built) |
| `scheduler` | `php artisan schedule:work` — nightly data-quality scan, Odoo sync, reminders (populated in later phases) |

For local development, PHP/Composer are also installed on the host (via Homebrew) purely for fast `artisan`/test iteration; the host CLI points at the same containerized Postgres/Redis (ports exposed to `127.0.0.1`) rather than running a second, drifting `artisan serve` HTTP server. Every change is still validated against the full `docker compose up` stack before a phase is considered done.

## 6. Cross-cutting conventions

- **API responses:** every endpoint returns `{"data": ..., "meta": {...}}` on success (see `docs/api-design.md`) via `App\Support\ApiResponse` and Laravel API Resources; validation failures return Laravel's standard `{"message": ..., "errors": {...}}` 422 shape. The frontend's `lib/api/errors.ts` normalizes both into one `ApiError` type — built once, reused by every module.
- **Authorization:** enforced server-side via Policies/Gates (Phase 2 onward) — never trust frontend route hiding alone.
- **Audit logging:** a single `AuditLog` model + observer/listener pattern, wired in Phase 2, used by every sensitive mutation from then on.
- **Odoo integration:** isolated entirely behind `Services/Odoo/*`; no other part of the app talks to Odoo's API directly. See `docs/odoo-integration.md`.
