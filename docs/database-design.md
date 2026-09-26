# ImpactFlow — Database Design

PostgreSQL, normalized relational schema. UUID primary keys for entities referenced across modules/APIs (programs, beneficiaries, employees, expenses, etc.); bigint identity keys are acceptable for pure lookup/pivot tables. Soft deletes on entities where recovery matters (programs, beneficiaries, employees, assets); hard deletes on logs/pivots. All tables carry `created_at`/`updated_at`; mutable entities also carry `created_by`/`updated_by`.

**Status legend:** ✅ implemented (Phase 1) · ⏳ designed, implemented in the noted phase.

## 1. Identity & access — Phase 1 ✅ / Phase 2 ✅

| Table | Status | Notes |
|---|---|---|
| `users` | ✅ | Laravel default + `phone`, `is_active`, `last_login_at` |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | ✅ | Provided by `spatie/laravel-permission`; seeded with the six roles from `App\Enums\RoleEnum` (SuperAdmin, ProgramManager, FinanceOfficer, FieldOfficer, HrAdminOfficer, Management). Permissions defined in `App\Enums\PermissionEnum` — Phase 2 only defines `users.*`/`roles.*`/`audit-logs.viewAny` (the entities that exist so far); Phase 3+ modules add their own as they land |
| `audit_logs` | ✅ | `id, user_id (nullOnDelete), action, entity_type, entity_id, old_values (json, sensitive fields redacted), new_values (json, redacted), ip_address, user_agent, created_at` — immutable, written only via `App\Services\Audit\AuditLogger`. Redaction is a flat, global field-name deny-list (`AuditLogger::REDACTED_FIELDS`) — `password`/`remember_token` since Phase 2, plus `phone`/`date_of_birth`/`address`/`emergency_contact_name`/`emergency_contact_phone` since Phase 9, once an audit found the list hadn't kept pace with the PII fields this doc already claimed were redacted (see `docs/implementation-plan.md` Phase 9). Global by field name, not per-model config, so it also redacts `users.phone` consistently |

## 2. Organization — Phase 3 ✅

| Table | Key columns |
|---|---|
| `organizations` | `id, name, registration_number, address` — single seeded row (`OrganizationSeeder`); no CRUD UI, since nothing in the product brief needs more than one and a UI for editing a single fixed row isn't a real feature. Kept as a real table (not a config value) so `departments`/`branches` have a proper FK now rather than an awkward migration if that ever changes |
| `departments` | `id, organization_id, name, parent_department_id` |
| `branches` | `id, organization_id, name, district, upazila, address` |

Both are simple lookup tables: broadly readable (needed to populate select inputs on Programs/Employees forms), managed (create/update) by HR/Admin Officer or Super Administrator only.

## 3. Programs — Phase 3 ✅

| Table | Key columns |
|---|---|
| `program_categories` | `id, name, description` — lookup table, seeded with real categories (Education, Health, Livelihoods, WASH, Protection, Emergency Response) |
| `programs` | `id (uuid), name, description, category_id, manager_id -> users, branch_id, district, upazila, start_date, end_date, status (enum), budget, target_beneficiaries, progress, created_by` |

`programs.status`: `draft, pending_approval, approved, active, paused, completed, archived`. Phase 3 implements these as direct, permission-gated status-change actions (`programs.updateStatus`) with audit logging — not the generic workflow engine, which is Phase 4 scope and a better fit for Expense's genuinely multi-actor review chain. Program's status list is a simple linear state, so it doesn't need workflow steps/instances machinery; nothing stops Phase 4 from attaching the generic engine to Program later if a real multi-approver requirement appears.

**Simplified vs. the original design (documented here rather than silently dropped):**
- `program_locations` (a program spanning several districts) and `program_budgets` (per-fiscal-year budget rows) are dropped in favor of flat `district`/`upazila`/`budget` columns on `programs` — the product brief's own Program field list (§9) is flat, not normalized this way, and nothing in Phases 1-6 reads a multi-row budget breakdown. `program_budgets` may return in Phase 4 if budget-vs-actual reporting genuinely needs fiscal-year granularity.
- `actual_beneficiaries` is dropped as a stored column — it's a live count of `beneficiary_programs` rows, computed on read, so it can't drift from reality.
- `odoo_project_id` is deferred to Phase 7 (added via its own migration when the Odoo sync code that populates it actually exists) rather than sitting unused for four phases.

## 4. Beneficiaries — Phase 3 ✅

| Table | Key columns |
|---|---|
| `beneficiaries` | `id (uuid), full_name, date_of_birth, gender, phone, email, address, district, upazila, status, registration_date, emergency_contact_name, emergency_contact_phone, notes` |
| `beneficiary_programs` | `id, beneficiary_id, program_id, enrolled_at, status` (pivot with metadata) |

Only demo-appropriate fields are collected; no fields beyond what's listed in the product brief. Sensitive fields (phone, DOB, address) are never returned by list endpoints — only on the authorized detail view.

**Simplified vs. the original design:**
- `beneficiary_contacts` (a one-to-many emergency-contacts table) is dropped in favor of a flat `emergency_contact_name`/`emergency_contact_phone` pair — the product brief's field list (§10) asks for a single "Emergency Contact" field, not several.
- `beneficiary_documents`, `data_quality_score`, and duplicate detection were deferred to Phase 5 (Data Management). Duplicate detection and a data quality score now exist (§9) — `data_quality_score` itself stayed deferred in favor of computing the score on read (`GET /api/v1/data-quality/score`) rather than a stored, denormalized column nothing else reads. `beneficiary_documents` stays deferred past Phase 5 too — it wants a generic document/attachment system shared with expense receipts (§7) and import source files (§9), and no phase has built that generic layer yet; each phase so far has stored files with its own narrow, purpose-specific model instead.

## 5. Staff & volunteers — Phase 3 ✅

| Table | Key columns |
|---|---|
| `employees` | `id (uuid), user_id nullable, name, department_id, branch_id, position, joining_date, status` — `name` is stored directly (not derived from `users`) since `user_id` is nullable: not every employee has a login account |
| `volunteers` | `id (uuid), full_name, phone, email, skills (jsonb), availability, status` |

`odoo_employee_id` is deferred to Phase 7 for the same reason as `programs.odoo_project_id` above.

## 6. Activities — Phase 3 ✅

| Table | Key columns |
|---|---|
| `activities` | `id (uuid), program_id, title, description, scheduled_at, location, status` |
| `activity_attendance` | `id, activity_id, beneficiary_id, attended (bool)` |

**Simplified vs. the original design:** `activity_attendance` only tracks beneficiaries in Phase 3, not the originally-designed nullable triple-FK (beneficiary/employee/volunteer) — every demo scenario and dashboard chart in the product brief (§8, §58) talks about beneficiary activity participation specifically; staff/volunteer attendance isn't asked for anywhere. The schema can grow the other two nullable FKs later without breaking this table if that changes.

## 7. Finance & assets — Phase 4 ✅

| Table | Key columns |
|---|---|
| `expense_categories` | `id, name` — lookup table, seeded with real categories |
| `expenses` | `id (uuid), program_id, category_id, submitted_by, amount, currency, expense_date, description, status, created_by` |
| `expense_attachments` | `id, expense_id, file_path, original_name, mime_type, size, uploaded_by` — stored on a private disk, served only through an authenticated download route (see §12 and the Odoo-integration-style "no predictable public URLs" rule in the product brief §38) |
| `assets` | `id (uuid), name, category, serial_number, purchase_date, purchase_value, location, condition, status` |
| `asset_assignments` | `id, asset_id, assigned_to -> users, assigned_at, returned_at` |

`expenses.status`: `draft, program_review, finance_review, approved, rejected`. `odoo_synced` is added in Phase 7 once the sync that would actually set it exists — an unused status value today would be dead code.

**Simplified vs. the original design:**
- `submitted` is dropped as a distinct resting status — submitting an expense starts the approval workflow immediately, landing it in `program_review` in the same request, so there's no observable moment where "submitted" would differ from "program_review".
- `approver_id` (a single FK) is dropped — a two-step workflow has two approvers, and `workflow_actions` (see §8) already records who acted at each step with a timestamp and comment. A second, single-value column would just duplicate (and could drift from) that history.
- `program_budgets` (per-fiscal-year rows) stays deferred, not built in Phase 4 either: the budget-vs-actual check this phase implements (see §8) only needs `programs.budget` as a single ceiling, which already exists.

## 8. Approval workflow engine — Phase 4 ✅

Generic, reusable — not hardcoded per entity. Expense is the first (and, in Phase 4, only) consumer; Program's own status changes (Phase 3) deliberately stay a simple direct transition rather than being retrofitted onto this engine — see §3's reasoning. Nothing stops a later phase from moving Program onto it if a real multi-approver requirement appears there.

| Table | Key columns |
|---|---|
| `approval_workflows` | `id, name, entity_type` (e.g. `App\Models\Expense`) |
| `workflow_steps` | `id, workflow_id, sequence, role_required, name` — `name` doubles as the entity status the workflow reports while parked on that step (e.g. step named `program_review` ⇒ `expenses.status = 'program_review'` while an instance sits there) |
| `workflow_instances` | `id, workflow_id, entity_type, entity_id, current_step_id (null once terminal), status (in_progress/approved/rejected/returned)` |
| `workflow_actions` | `id, workflow_instance_id, step_id, actor_id, action, comment, created_at` |

**Decisions worth calling out:**
- Definitions (`approval_workflows`/`workflow_steps`) are seeded, not admin-editable in Phase 4 — a workflow *designer* UI is a real feature but a separate one from the engine actually processing approvals correctly, and nothing in the product brief's demo scenarios exercises editing a workflow's steps. `GET /api/v1/workflows` exists for visibility; there's no create/update endpoint yet.
- Action vocabulary is `approve | reject | return` — `request_changes` is functionally identical to `return` (send it back to the submitter with a comment) so it isn't a separate code path, and `escalate` (reassign to a different approver) is left out: it's a real feature but not one any current scenario needs, and building it well requires a "who do you escalate to" model this phase has no other use for.
- `return` always sends the instance back to the submitter (`workflow_instances.status = 'returned'`, entity status back to `draft`) rather than to a specific prior step. Multi-hop step navigation adds real complexity; "fix it and resubmit" (which starts a fresh instance) is both simpler and closer to how this works in practice.
- Budget-vs-actual enforcement (product brief §14: "prevent approval when appropriate budget rules are violated") is checked once, at the *final* approval step (Finance), against `programs.budget` minus already-approved expenses for that program — Finance is the budget gatekeeper per the product brief's role descriptions, so earlier steps don't duplicate the check.

## 9. Data management — Phase 5 ✅

| Table | Key columns |
|---|---|
| `data_imports` | `id, entity_type, file_path, original_name, uploaded_by, status, detected_headers (jsonb), column_mapping (jsonb), total_rows, valid_rows, duplicate_rows, invalid_rows, error_message` |
| `data_import_rows` | `id, data_import_id, row_number, raw_data (jsonb), status, errors (jsonb), beneficiary_id` |
| `data_quality_issues` | `id, entity_type, entity_id, issue_type, severity, description, status (open/ignored/resolved), detected_at, resolved_at, resolved_by` |

**Decisions worth calling out:**
- Scoped to `beneficiaries` only — the only entity with the `(full_name, phone)` duplicate index (§12) and the only one in the demo script (scenarios 2 & 4). `data_imports.entity_type` is a small enum (`ImportEntityType`) that picks an import *pipeline*, not a polymorphic morph target (a single import creates many records) — unlike `data_quality_issues.entity_type`/`entity_id`, which really is a morph pair, using the same FQCN convention as `workflow_instances` (§8).
- Duplicate heuristic is intentionally simple: exact match after normalization (`trim`+`lowercase` on name, digits-only on phone). Candidates are matched case-insensitively on name in SQL, then filtered by normalized phone in PHP — this keeps the query portable across Postgres and SQLite without needing DB-specific phone-stripping functions. True fuzzy/similarity matching is out of scope.
- Duplicates are flagged, not blocked, on both paths: ordinary beneficiary create/update (`BeneficiaryDuplicateDetector`, called from `CreateBeneficiaryAction`/`UpdateBeneficiaryAction`) and bulk-import commit (which reuses `CreateBeneficiaryAction` per row, so the detector and audit logging live in exactly one place for both).
- `issue_type`/`severity` are plain strings, not enums — there's a single detector (`duplicate_beneficiary` / `warning`) this phase, so an enum with one case would just be a placeholder. Room to grow once more quality checks exist.
- The import pipeline is staged: upload → map columns → preview (validates + detects duplicates into `data_import_rows`, replacing any prior staged rows on re-run) → commit. Invalid rows are never committed; fixing them means re-uploading, not editing a row in place (undemoed, avoided half-wiring).
- Commit runs as a queued job (`ProcessDataImportCommit`) on the `worker` container already provisioned in `docker-compose.yml` — matching `docs/architecture.md`'s anticipated `Jobs/Import`. It authenticates as the uploader (`Auth::setUser`) purely for audit/`created_by` attribution, since the job runs outside any HTTP request.
- Data quality score: `round(100 * (1 - open_issues / max(total_beneficiaries, 1)), 1)`, clamped to `[0, 100]` — simple and deterministic rather than a weighted/severity-based formula, since there's only one issue type today.

## 10. Odoo integration — Phase 7 ✅

| Table | Key columns |
|---|---|
| `odoo_connections` | `id, name, is_active` — single seeded row |
| `odoo_mappings` | `id, entity_type, local_id, odoo_model, odoo_id`, unique on `(entity_type, local_id, odoo_model)` |
| `odoo_sync_logs` | `id, entity_type, local_id, odoo_id nullable, operation, status, request_payload (json), response_payload (json), error_message, retry_count, request_time, response_time`, unique on `(entity_type, local_id, operation)` |
| `programs.odoo_project_id`, `employees.odoo_employee_id` | nullable, set once a sync succeeds |
| `expenses.odoo_synced` | boolean, set once a sync succeeds |

Also see `App\Enums\PermissionEnum`'s Phase 7 cases (`odoo.viewAny`, `odoo.retry`, `odoo.manageConfig`) and `App\Events\{ProgramApproved,ExpenseApproved,BeneficiaryRegistered,EmployeeCreated}` — the first domain events in this codebase, each with one `App\Listeners\Dispatch*ToOdoo` listener that queues `App\Jobs\OdooSyncJob`.

**Decisions worth calling out:**
- `odoo_connections` deliberately does **not** store `base_url`/`database`/`mode`, unlike the original sketch — those stay env/config-only (`config/odoo.php`), so there's exactly one place they can drift from the real credentials, never two. The table's only real job is the one thing that can't live in a `.env` file and still be operable from the UI: a SuperAdmin-mutable `is_active` pause/resume toggle, used by the "simulate an outage" demo scenario and by `OdooSyncService`, which skips (not fails) a sync while paused.
- `odoo_sync_logs` upserts one row per `(entity_type, local_id, operation)` rather than inserting a new row per attempt — `retry_count` increments in place across `OdooSyncJob`'s retries, matching what the column name already implies. A row's final state is one of `pending` (mid-attempt), `success`, `failed` (this attempt failed; `OdooSyncJob::failed()` sets this same row once retries are exhausted), or `skipped` (the connection was paused).
- Beneficiaries get no denormalized `odoo_partner_id` column, unlike Program/Employee — their sync state lives only in `odoo_mappings`. Program and Employee detail pages want a quick "synced to Odoo" fact without a join; nothing in the product brief needs that for Beneficiaries specifically, so the more consistent (single-source) design was kept there.
- Transport is JSON-RPC 2 (`App\Services\Odoo\OdooJsonRpcClient`, via Laravel's `Http` facade) rather than XML-RPC — `ext-xmlrpc` isn't installed in this image and can no longer be installed from PHP core source past PHP 8, and `docs/odoo-integration.md` §2 itself allows either transport.
- `ProgramApproved` fires on `ProgramStatus::Approved`, not the later `Active` transition — it's the literal, distinct "approved for execution" moment in `ProgramStatus::allowedNextStatuses()`, and the moment a project should exist in Odoo regardless of when field activity actually starts.
- The Odoo → ImpactFlow direction (`odoo:poll`, scheduled every 5 minutes) probes connectivity and logs how many mapped records changed upstream since the last poll; it does not write those changes back into ImpactFlow. Reverse field-mapping and conflict resolution is a materially separate feature (whose data wins, partial-field merges) — detecting and reporting drift is the complete, honest slice this phase implements, not a partially-built reconciliation engine.
- The optional custom Odoo module (`docs/odoo-integration.md` §8) was not attempted — it was explicitly scoped as a stretch item, not core Phase 7 work.

## 11. Reporting & notifications — Phase 6 ✅ / Phase 8 ✅

| Table | Key columns |
|---|---|
| `reports` | `id (uuid), type, format, parameters (jsonb), file_path, generated_by, generated_at` |
| `notifications` | Laravel's default notifications table (polymorphic): `id (uuid), type, notifiable_type, notifiable_id, data (json), read_at, timestamps` |

**Decisions worth calling out:**
- No separate `report_exports` table. The original sketch above had one `reports` row potentially fanning out to multiple format exports; in the shipped UX (`?format=csv|xlsx|pdf` chosen up front, one file produced and downloaded per request), a `reports` row and its file are created atomically in the same request — there's never a moment where one row has two different-format children. `format`/`file_path` are folded directly onto `reports`, matching how `expense_attachments` folds `file_path` onto its own row rather than a generic "exports" abstraction.
- No `status` column, no queue. Report generation is one bounded SQL query (a few hundred rows at most, per the product's demo scale) plus one in-memory file write — the same shape as `DataQualityIssueController::score()` (Phase 5), which is synchronous. Every `reports` row that exists succeeded; a failed generation raises mid-request and never persists a row. Queuing (as `data_imports` commits are, via `ProcessDataImportCommit`) would add a job class and a polling UX purely to track a "pending" state that would never realistically last long enough to matter at this scale.
- Data layer: one `ReportBuilder` per `App\Enums\ReportType` (`App\Services\Reports\Builders\*`) turns filtered Eloquent queries into a shared `ReportDataset` DTO (ordered columns + pre-formatted string rows), consumed by two writers — `SpreadsheetReportWriter` (PhpSpreadsheet's Writer side, the counterpart to the Phase 5 reader-only `DataImportSpreadsheetReader`) for csv/xlsx, and `PdfReportWriter` (raw `dompdf/dompdf` — no Laravel wrapper package is installed — rendering one generic Blade view) for pdf. Builders pre-format every value to a display string so the writers stay dumb formatters with no per-report-type branching.
- Authorization reuses existing entity permissions rather than inventing report-specific ones: `App\Enums\ReportType::permission()` maps each of the 4 types to the permission that already governs viewing that entity (`programs.viewAny`, `beneficiaries.view`, `expenses.viewAny`, `data-quality-issues.viewAny`). Report history listing and re-download re-check the same per-type Gate rather than a blanket `reports.viewAny`.
- The executive dashboard (`GET /api/v1/dashboard/kpis`) needs no dedicated permission either — each of its 7 sections (programs, beneficiaries, staffing, activities, finance, data quality, workflows) is included in the response only if the caller holds that section's existing entity permission, omitted entirely (never a fabricated zero) otherwise. This mirrors `frontend/src/components/layout/nav-items.ts`'s `requiresAnyPermission` composition, applied server-side. One default-permission-set update rode along with this: `Management`'s seeded defaults (`PermissionSeeder`) gained `employees.viewAny`/`volunteers.viewAny`/`activities.viewAny` — it's the role meant to consume the dashboard, and previously couldn't see 3 of its sections out of the box.
- Beneficiary enrollment-by-month and any other date-bucketed series are grouped in PHP (`Carbon::format('Y-m')`), not SQL date functions — the same portability rule already established for the duplicate-detection query in §9, since tests run on SQLite and production runs on Postgres.
- **Phase 8 notifications:** `App\Contracts\Workflowable` gained one method, `workflowOwner(): ?User` ("whose action this workflow is about" — `Expense::workflowOwner()` returns its `submitter`), so the generic notification listener can reach a terminal decision's recipient without knowing about Expense specifically. Two new events — `WorkflowInstanceStarted`/`WorkflowInstanceActed` — are dispatched from `App\Services\Workflow\WorkflowService` itself, not from an Action layer: unlike `App\Events\ExpenseApproved` (Phase 7), which needs an `instanceof Expense` check and therefore lives in `RecordWorkflowActionAction` alongside that check, these two need no entity-specific branching at all, so they belong in the actual generic engine. `ExpenseApproved`'s existing dispatch site is untouched. Mail delivery uses Laravel's `log` driver (`MAIL_MAILER=log`, already the default) — an honest, zero-infrastructure "mock mode" for email, the same spirit as Odoo's mock mode, that needed no new code to exist.

## 12. Indexing & integrity conventions

- Foreign keys on every relationship, `on delete restrict` for financial/audit-relevant links, `on delete cascade` only for true child records (e.g. `beneficiary_programs`, `activity_attendance`, `expense_attachments`).
- Unique constraints: `users.email`, `beneficiaries` duplicate-detection composite index on `(full_name, phone)` (used by the data quality engine, not a hard unique constraint — duplicates are flagged, not blocked outright, since legitimate near-duplicates exist).
- Indexes on every foreign key and on columns used for filtering (`status`, `district`, `program_id`, `created_at`).
- No unnecessary tables — every table above maps to a concrete feature in the product brief.
