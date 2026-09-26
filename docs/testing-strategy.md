# Testing Strategy

## 1. The pyramid

| Layer | Tool | What it covers | Count |
|---|---|---|---|
| Backend Unit | Pest (`tests/Unit`) | Pure logic with no DB: Enums (`ProgramStatus::allowedNextStatuses()`, `ReportType::permission()`, `ReportFormat::mimeType()`, `RoleEnum::label()`) and the two Odoo services with no Eloquent dependency (`FakeOdooClient`, `OdooMappingService`) | 37 |
| Backend Feature | Pest (`tests/Feature`) | Every API endpoint, through real HTTP requests + `RefreshDatabase` — auth, authorization, validation, workflow transitions, Odoo sync dispatch, notifications, reports, security | 124 |
| Frontend Unit/Component | Vitest + React Testing Library | Shared logic every page depends on (`lib/format.ts`, `lib/auth/permissions.ts`, `lib/api/errors.ts`) and the two genuinely reusable, framework-light components (`PaginationControls`, `Forbidden`) | 22 |
| E2E | Playwright (Chromium) | All 5 demo scenarios in this doc's own §6, driven through the real login form against a live `docker compose` stack — not mocked | 5 specs |

Backend total: **161 tests, 509 assertions** (up from 118 at the end of Phase 9 — this phase added the Unit suite from scratch plus two Feature gap-fills: `GET /api/v1/workflows` and `program-categories` store/update).

## 2. Tool choices

- **Pest**, not raw PHPUnit — already the project's convention since Phase 1; functional style keeps Feature tests readable as request/assert pairs.
- **Vitest**, not Jest — this is a Next 16 / React 19 / Turbopack project; Jest's transform pipeline lags a Next version this new, while Vitest's Vite-based pipeline handles ESM/JSX natively. No `@vitejs/plugin-react`: it pulls in `@babel/core@8`, conflicting with shadcn's `@babel/core@^7` — Vite's native oxc/esbuild JSX transform covers this project's needs without it.
- **Playwright, Chromium only** — this is a demo-scale portfolio project, not a cross-browser compatibility matrix. Firefox/WebKit can be added later if that becomes a real requirement.
- **PCOV, not Xdebug**, for coverage — this image only ever needs coverage *measurement*, never step-debugging, and PCOV is dramatically faster for that one job.

## 3. Running each layer locally

```bash
# Backend Unit + Feature (host PHP, sqlite in-memory — see §5 on why not Docker)
cd backend && ./vendor/bin/pest

# Backend coverage (needs PCOV — see §5)
cd backend && composer test:coverage

# Backend style
cd backend && vendor/bin/pint --test

# Frontend unit/component
cd frontend && npm run test

# Frontend type-check + lint
cd frontend && npx tsc --noEmit && npm run lint

# E2E — needs a live stack first
docker compose up -d
cd frontend && npx playwright test
```

E2E provisions its own demo users via `e2e/global-setup.ts` (real API calls, idempotent) and runs serially (`workers: 1`) — all 5 specs share one Postgres database with no per-test transaction isolation, unlike Pest's `RefreshDatabase`, so concurrent specs could otherwise observe each other's data (e.g. dashboard KPI counts).

## 4. Measured coverage baseline

**84.5% line coverage** (backend `app/`, via `composer test:coverage` inside the `laravel` container). Notable gaps, left as-is rather than padded with low-value tests:
- `Services/Odoo/OdooJsonRpcClient` (0%) — the live-mode Odoo client; every test runs in mock mode (`FakeOdooClient`) by design (see `docs/odoo-integration.md`), so this only executes against a real Odoo instance.
- A handful of `UpdateXRequest`/`Resource` classes (0–20%) — thin validation/serialization classes only exercised by update-flow tests that happen not to hit every branch; not worth bespoke tests for the coverage number alone.
- `Policies/UserPolicy`, `RolePolicy` (20–50%) — covered for the roles exercised by existing Feature tests; not every permission combination is enumerated.

No coverage gate is enforced yet (`--min=0`) — Phase 11 (CI/CD) is the natural place to turn this into a real gate, once there's a considered floor rather than a guessed one.

## 5. Known infrastructure gotchas (found and fixed this phase)

These aren't app bugs — they're specific to running the test suite inside this project's Docker setup, and are recorded here so they aren't rediscovered from scratch:

- **`docker-compose.yml`'s `env_file: ./backend/.env` injects real config as OS env vars inside the container** (`APP_ENV=local`, `DB_CONNECTION=pgsql`, `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`). PHPUnit's `<env>` overrides in `phpunit.xml` only apply to a variable that's *absent* from the environment unless `force="true"` is set — so without it, tests silently ran against the live Postgres/Redis stack instead of the intended isolated sqlite/array/sync config, causing CSRF, session, and async-queue-timing failures that don't reproduce on a host run. Fixed by adding `force="true"` to every `<env>` entry, plus a matching `<server>` block (PHPUnit's env force doesn't touch `$_SERVER`, which PHP's CLI SAPI already populated from the same OS env at process start).
- **`.dockerignore` excludes `tests/` and `.env`** from the image by design (a lean, secret-free build). This means `php artisan test` only works inside a *running* container if you `docker cp` the tests directory and a `.env` file in first — there's no bind mount for backend source. The straightforward path for routine test runs is the host (`cd backend && ./vendor/bin/pest`, against sqlite); the container route is only needed for measuring PCOV coverage, since the host's PHP install has no coverage driver.
- **Spatie's permission registrar caches roles/permissions in a process-wide singleton** that `RefreshDatabase`'s per-test transaction rollback never resets. A test that mutates roles/permissions can leak stale cached state into a later test in the same run, depending on file discovery order — invisible on a lucky ordering, real once file order changes (e.g. via `docker cp`). Fixed with a `beforeEach` in `tests/Pest.php` that calls `PermissionRegistrar::forgetCachedPermissions()` before every Feature test.
- **The login route's 5/min rate limit (Phase 9)** can trip during rapid, repeated local `npx playwright test` runs — each run's own logins are comfortably under the limit, but back-to-back manual re-runs while debugging stack within the same rolling window. `e2e/global-setup.ts` clears the cache at the start of every run so iterative local debugging doesn't self-inflict 429s; a single clean run was never at risk.
- **base-ui's `Select` `SelectValue` displays the raw stored value** (e.g. `"all"`), not the matching item's label, until its popup has been opened at least once — `getByText("All statuses")` doesn't resolve on first render. E2E specs target the trigger by `getByRole("combobox")` instead.
- **A Next.js dev-mode navigation race**: an immediate `goto("/login")` right after a full-page redirect occasionally collides with aborted RSC prefetch requests and leaves the page stuck mid-navigation. `e2e/support/auth.ts`'s `logout()`/`loginAs()` verify the form actually rendered and reload once if not, rather than assuming a single `goto` is reliable.
- **Never run backend Pest tests inside the `laravel` container without the `phpunit.xml` `force="true"` fix above landed first.** Before it did, `RefreshDatabase` silently ran against the real `pgsql` connection, and its first-use `migrate:fresh` wiped the live dev database (this happened once, while writing this fix — recovered with `php artisan migrate:fresh --seed`). The host run (`./vendor/bin/pest`, real sqlite in-memory) was never at risk; it's specifically the container route that's dangerous without the env-forcing fix in place.

## 6. Demo scenarios covered by E2E

1. `program-approval.spec.ts` — Program Manager creates and submits a program; Management approves it.
2. `beneficiary-registration.spec.ts` — a duplicate beneficiary registration surfaces a data quality issue.
3. `expense-approval-odoo-sync.spec.ts` — an expense clears both approval steps and produces a real Odoo sync log entry (mock mode).
4. `data-import.spec.ts` — a CSV import with a duplicate row previews correctly and commits the rest.
5. `odoo-outage-recovery.spec.ts` — a failed Odoo sync is retried and recovers.

## 7. Explicitly deferred

- **Full frontend component coverage** — `NotificationBell`, `DashboardView`, and the larger page-level components need a `QueryClientProvider` + mocked `next/navigation`/fetch scaffolding that's a real piece of infrastructure in its own right. The 5 components tested this phase (`format`, `permissions`, `errors`, `PaginationControls`, `Forbidden`) are the reusable, low-ceremony ones worth testing first; broader component coverage is the natural next increment.
- **Cross-browser E2E** (Firefox/WebKit) — not a real requirement for a demo-scale portfolio project; Chromium only for now.
- **CI-enforced coverage gate** — `composer test:coverage` is informational (`--min=0`) until Phase 11 picks a considered floor.
- **Mutation testing** — `pestphp/pest-plugin-mutate` is present as a transitive dependency but not wired into any script; out of scope for this phase.
