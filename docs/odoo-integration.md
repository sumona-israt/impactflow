# ImpactFlow — Odoo Integration Design

Implemented in Phase 7. This document exists now so the rest of the system (routes, service boundaries, mock-mode expectations) is built consistently with it from the start.

## 1. Why this is isolated

No part of ImpactFlow outside `app/Services/Odoo/*` is allowed to know Odoo's XML-RPC/JSON-RPC wire format. Everything else — controllers, jobs, other services — talks to a small set of PHP interfaces. This means:
- Odoo's API can be swapped, versioned, or mocked without touching business logic.
- A live Odoo outage degrades to "sync pending," never a broken request/response cycle for the user.

## 2. Service layer

| Class | Responsibility |
|---|---|
| `OdooClient` | Low-level RPC transport (XML-RPC or JSON-RPC 2 depending on configured Odoo version). No business meaning — just `execute_kw(model, method, args)`. |
| `OdooAuthService` | Authenticates against `ODOO_DATABASE`/`ODOO_USERNAME`/`ODOO_PASSWORD`, caches the session/uid in Redis with TTL. |
| `OdooMappingService` | Translates between ImpactFlow records and Odoo model fields; reads/writes `odoo_mappings`. |
| `OdooSyncService` | Orchestrates a sync operation for one entity: build payload via `OdooMappingService`, call `OdooClient`, write an `odoo_sync_logs` row, update `odoo_mappings`. |
| `OdooHealthService` | Backs `/api/v1/odoo/status` — connectivity check, last successful sync, counts by entity. |

All are bound behind a single `OdooServiceInterface`-style contract per entity so `ODOO_MODE=mock` can swap in an in-memory/fake implementation with the exact same signatures (see §5).

## 3. Entities integrated

| ImpactFlow entity | Odoo model (typical) | Direction | Notes |
|---|---|---|---|
| Beneficiaries / general contacts | `res.partner` | ImpactFlow → Odoo | Beneficiaries and external contacts sync as contacts. |
| Employees | `hr.employee` | ImpactFlow → Odoo | Requires the HR module enabled in the target Odoo instance. |
| Programs | `project.project` | ImpactFlow → Odoo | A program maps to an Odoo project; `programs.odoo_project_id` stores the link. |
| Expenses | Odoo's expense/accounting model (`hr.expense` or the accounting move model, depending on which financial module the configured instance has installed) | ImpactFlow → Odoo | **Version/module-dependent** — see §4. |

We do not claim two-way real-time sync unless it is actually implemented; see §6 for the honest sync strategy.

## 4. Version-awareness

Odoo's available modules (and therefore models/fields) differ across Community vs. Enterprise and across versions. `OdooMappingService` is configuration-driven (a mapping definition per entity, not hardcoded field names), so:
- If the configured Odoo instance lacks the expense/accounting module, expense sync is disabled and the Odoo dashboard states this plainly rather than silently failing or faking success.
- Field mappings are declared in config (`config/odoo.php`), not scattered through code, so adapting to a different Odoo version is a config change, not a rewrite.

## 5. Mock mode

`ODOO_MODE=mock` (default for local/demo use, since a live Odoo server is not assumed):
- `OdooAuthService` simulates authentication (no network call).
- A `FakeOdooClient` returns deterministic, realistic-looking IDs/records instead of calling a real server.
- Sync operations still write real `odoo_sync_logs` rows and update real `odoo_mappings` — the *data flow and UI* are fully real; only the remote endpoint is simulated.
- Configurable simulated failures (e.g. `ODOO_MOCK_FAILURE_RATE`) let the demo show retry/recovery behavior honestly, without needing to physically take a server down.
- Every screen that surfaces Odoo data displays a **"Mock Odoo Environment"** badge when `ODOO_MODE=mock`, so nothing is presented as a real integration when it isn't.

`ODOO_MODE=live` uses `ODOO_BASE_URL`/`ODOO_DATABASE`/`ODOO_USERNAND`/`ODOO_PASSWORD` from the environment (never hardcoded, never committed) against a real instance.

## 6. Sync strategy: honest scope

Two-way real-time webhooks require a custom module installed on the Odoo side listening for changes and calling back into ImpactFlow (see §8) — this is not available out of the box in most Odoo deployments. The actual strategy:

- **ImpactFlow → Odoo:** queued jobs (`OdooSyncJob`), triggered on the relevant domain event (e.g. `ExpenseApproved`, `ProgramApproved`). This is real push-on-event sync, not polling.
- **Odoo → ImpactFlow:** scheduled polling (Laravel Scheduler, e.g. every N minutes) reading recently-updated Odoo records, OR, if the optional custom module (§8) is installed, a webhook callback. Whichever is actually wired up is what the Odoo dashboard and this document describe — the polling fallback is documented as the default, not as a limitation to hide.

## 7. Failure handling

```text
Domain event fires → OdooSyncJob queued
        ↓
Odoo unavailable / error response
        ↓
Job fails → Laravel queue retry with exponential backoff (configurable max attempts)
        ↓
Attempts exhausted → job lands in failed_jobs, odoo_sync_logs row marked "failed" with error_message
        ↓
Admin sees it on the Odoo Integration dashboard → manual "Retry" action re-dispatches the job
```

The rest of the application never blocks on Odoo: a beneficiary registration, expense approval, or program creation always succeeds locally first; Odoo sync is an asynchronous side effect with its own visible status, never a precondition for the local action to succeed.

## 8. Optional custom Odoo module — `impactflow_connector`

If time allows (tracked as a stretch item in Phase 7, not a Phase 1–12 hard requirement): a small custom Odoo module adding an `x_impactflow_ref` field and sync-status metadata to `res.partner`/`hr.employee`/`project.project`, plus a webhook endpoint that calls back into ImpactFlow on record changes. Kept intentionally small — it exists to demonstrate Odoo customization capability, not to recreate ImpactFlow's own logic inside Odoo.

## 9. Environment variables

```env
ODOO_MODE=mock            # mock | live
ODOO_BASE_URL=
ODOO_DATABASE=
ODOO_USERNAME=
ODOO_PASSWORD=
ODOO_MOCK_FAILURE_RATE=0  # 0-1, mock mode only, for demoing retry behavior
```

Documented in `backend/.env.example`; never committed with real values.
