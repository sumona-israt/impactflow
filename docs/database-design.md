# ImpactFlow — Database Design

PostgreSQL, normalized relational schema. UUID primary keys for entities referenced across modules/APIs (programs, beneficiaries, employees, expenses, etc.); bigint identity keys are acceptable for pure lookup/pivot tables. Soft deletes on entities where recovery matters (programs, beneficiaries, employees, assets); hard deletes on logs/pivots. All tables carry `created_at`/`updated_at`; mutable entities also carry `created_by`/`updated_by`.

**Status legend:** ✅ implemented (Phase 1) · ⏳ designed, implemented in the noted phase.

## 1. Identity & access — Phase 1 ✅ / Phase 2 ✅

| Table | Status | Notes |
|---|---|---|
| `users` | ✅ | Laravel default + `phone`, `is_active`, `last_login_at` |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | ✅ | Provided by `spatie/laravel-permission`; seeded with the six roles from `App\Enums\RoleEnum` (SuperAdmin, ProgramManager, FinanceOfficer, FieldOfficer, HrAdminOfficer, Management). Permissions defined in `App\Enums\PermissionEnum` — Phase 2 only defines `users.*`/`roles.*`/`audit-logs.viewAny` (the entities that exist so far); Phase 3+ modules add their own as they land |
| `audit_logs` | ✅ | `id, user_id (nullOnDelete), action, entity_type, entity_id, old_values (json, sensitive fields redacted), new_values (json, redacted), ip_address, user_agent, created_at` — immutable, written only via `App\Services\Audit\AuditLogger` |

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

## 10. Odoo integration — Phase 7 ⏳

| Table | Key columns |
|---|---|
| `odoo_connections` | `id, name, base_url, database, mode (mock/live), is_active` (credentials referenced via env, never stored in plaintext in this table) |
| `odoo_mappings` | `id, entity_type, local_id, odoo_model, odoo_id` |
| `odoo_sync_logs` | `id, entity, local_id, odoo_id nullable, operation, status, request_payload (jsonb), response_payload (jsonb), error_message, retry_count, request_time, response_time` |

## 11. Reporting & notifications — Phase 6 / 8 ⏳

| Table | Key columns |
|---|---|
| `reports` | `id, type, parameters (jsonb), generated_by, generated_at` |
| `report_exports` | `id, report_id, format (csv/xlsx/pdf), file_path` |
| `notifications` | Laravel's default notifications table (polymorphic) |

## 12. Indexing & integrity conventions

- Foreign keys on every relationship, `on delete restrict` for financial/audit-relevant links, `on delete cascade` only for true child records (e.g. `beneficiary_programs`, `activity_attendance`, `expense_attachments`).
- Unique constraints: `users.email`, `beneficiaries` duplicate-detection composite index on `(full_name, phone)` (used by the data quality engine, not a hard unique constraint — duplicates are flagged, not blocked outright, since legitimate near-duplicates exist).
- Indexes on every foreign key and on columns used for filtering (`status`, `district`, `program_id`, `created_at`).
- No unnecessary tables — every table above maps to a concrete feature in the product brief.
