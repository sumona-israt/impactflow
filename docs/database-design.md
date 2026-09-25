# ImpactFlow — Database Design

PostgreSQL, normalized relational schema. UUID primary keys for entities referenced across modules/APIs (programs, beneficiaries, employees, expenses, etc.); bigint identity keys are acceptable for pure lookup/pivot tables. Soft deletes on entities where recovery matters (programs, beneficiaries, employees, assets); hard deletes on logs/pivots. All tables carry `created_at`/`updated_at`; mutable entities also carry `created_by`/`updated_by`.

**Status legend:** ✅ implemented (Phase 1) · ⏳ designed, implemented in the noted phase.

## 1. Identity & access — Phase 1 ✅ / Phase 2 ✅

| Table | Status | Notes |
|---|---|---|
| `users` | ✅ | Laravel default + `phone`, `is_active`, `last_login_at` |
| `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` | ✅ | Provided by `spatie/laravel-permission`; seeded with the six roles from `App\Enums\RoleEnum` (SuperAdmin, ProgramManager, FinanceOfficer, FieldOfficer, HrAdminOfficer, Management). Permissions defined in `App\Enums\PermissionEnum` — Phase 2 only defines `users.*`/`roles.*`/`audit-logs.viewAny` (the entities that exist so far); Phase 3+ modules add their own as they land |
| `audit_logs` | ✅ | `id, user_id (nullOnDelete), action, entity_type, entity_id, old_values (json, sensitive fields redacted), new_values (json, redacted), ip_address, user_agent, created_at` — immutable, written only via `App\Services\Audit\AuditLogger` |

## 2. Organization — Phase 3 ⏳

| Table | Key columns |
|---|---|
| `organizations` | `id, name, registration_number, address, ...` (single-row in practice; future multi-tenant hook) |
| `departments` | `id, organization_id, name, parent_department_id` |
| `branches` | `id, organization_id, name, district, upazila, address` |

## 3. Programs — Phase 3 ⏳

| Table | Key columns |
|---|---|
| `program_categories` | `id, name, description` |
| `programs` | `id (uuid), name, description, category_id, manager_id -> users, branch_id, start_date, end_date, status (enum), target_beneficiaries, actual_beneficiaries, odoo_project_id, created_by` |
| `program_locations` | `id, program_id, district, upazila` |
| `program_budgets` | `id, program_id, fiscal_year, allocated_amount, currency` |

`programs.status`: `draft, pending_approval, approved, active, paused, completed, archived`.

## 4. Beneficiaries — Phase 3 ⏳

| Table | Key columns |
|---|---|
| `beneficiaries` | `id (uuid), full_name, date_of_birth, gender, phone, email, address, district, upazila, status, registration_date, notes, data_quality_score` |
| `beneficiary_contacts` | `id, beneficiary_id, relation, name, phone` (emergency contacts) |
| `beneficiary_programs` | `id, beneficiary_id, program_id, enrolled_at, status` (pivot with metadata) |
| `beneficiary_documents` | `id, beneficiary_id, document_type, file_path, uploaded_by` |

Only demo-appropriate fields are collected; no fields beyond what's listed in the product brief. Sensitive fields (phone, DOB, address) are never returned by list endpoints — only on the authorized detail view.

## 5. Staff & volunteers — Phase 3 ⏳

| Table | Key columns |
|---|---|
| `employees` | `id (uuid), user_id nullable, department_id, branch_id, position, joining_date, status, odoo_employee_id` |
| `volunteers` | `id (uuid), full_name, phone, email, skills (jsonb), availability, status` |

## 6. Activities — Phase 3 ⏳

| Table | Key columns |
|---|---|
| `activities` | `id (uuid), program_id, title, description, scheduled_at, location, status` |
| `activity_attendance` | `id, activity_id, beneficiary_id nullable, employee_id nullable, volunteer_id nullable, attended (bool)` |

## 7. Finance & assets — Phase 4 ⏳

| Table | Key columns |
|---|---|
| `expense_categories` | `id, name` |
| `expenses` | `id (uuid), program_id, category_id, submitted_by, amount, currency, expense_date, description, status, approver_id, odoo_reference` |
| `expense_attachments` | `id, expense_id, file_path, uploaded_by` |
| `assets` | `id (uuid), name, category, serial_number, purchase_date, purchase_value, location, condition, status` |
| `asset_assignments` | `id, asset_id, assigned_to -> users, assigned_at, returned_at` |

`expenses.status`: `draft, submitted, program_review, finance_review, approved, rejected, odoo_synced`.

## 8. Approval workflow engine — Phase 4 ⏳

Generic, reusable — not hardcoded per entity.

| Table | Key columns |
|---|---|
| `approval_workflows` | `id, name, entity_type` (e.g. `Expense`, `Program`) |
| `workflow_steps` | `id, workflow_id, sequence, role_required, name` |
| `workflow_instances` | `id, workflow_id, entity_type, entity_id, current_step_id, status` |
| `workflow_actions` | `id, workflow_instance_id, step_id, actor_id, action (approve/reject/return/request_changes/escalate), comment, created_at` |

## 9. Data management — Phase 5 ⏳

| Table | Key columns |
|---|---|
| `data_imports` | `id, entity_type, file_path, uploaded_by, status, total_rows, valid_rows, duplicate_rows, invalid_rows` |
| `data_import_rows` | `id, data_import_id, row_number, raw_data (jsonb), status, errors (jsonb)` |
| `data_quality_issues` | `id, entity_type, entity_id, issue_type, severity, description, status (open/ignored/resolved), detected_at, resolved_at, resolved_by` |

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

- Foreign keys on every relationship, `on delete restrict` for financial/audit-relevant links, `on delete cascade` only for true child records (e.g. `beneficiary_documents`).
- Unique constraints: `users.email`, `beneficiaries` duplicate-detection composite index on `(full_name, phone)` (used by the data quality engine, not a hard unique constraint — duplicates are flagged, not blocked outright, since legitimate near-duplicates exist).
- Indexes on every foreign key and on columns used for filtering (`status`, `district`, `program_id`, `created_at`).
- No unnecessary tables — every table above maps to a concrete feature in the product brief.
