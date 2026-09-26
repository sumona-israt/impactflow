<?php

namespace App\Enums;

/**
 * Permissions are code-defined (Laravel/spatie convention: roles are the
 * assignable unit admins manage, permissions are a fixed vocabulary
 * developers add as features land) — see docs/architecture.md. Phase 2 only
 * defines permissions for the entities that exist so far (users, roles,
 * audit logs); Phase 3+ adds their own as those models arrive.
 */
enum PermissionEnum: string
{
    case ViewAnyUsers = 'users.viewAny';
    case ViewUser = 'users.view';
    case CreateUser = 'users.create';
    case UpdateUser = 'users.update';
    case ManageUserRoles = 'users.manageRoles';

    case ViewAnyRoles = 'roles.viewAny';
    case ManageRolePermissions = 'roles.managePermissions';

    case ViewAnyAuditLogs = 'audit-logs.viewAny';

    // Phase 3: organization lookups
    case ViewAnyDepartments = 'departments.viewAny';
    case ManageDepartments = 'departments.manage';
    case ViewAnyBranches = 'branches.viewAny';
    case ManageBranches = 'branches.manage';
    case ViewAnyProgramCategories = 'program-categories.viewAny';
    case ManageProgramCategories = 'program-categories.manage';

    // Phase 3: programs
    case ViewAnyPrograms = 'programs.viewAny';
    case ViewProgram = 'programs.view';
    case CreateProgram = 'programs.create';
    case UpdateProgram = 'programs.update';
    case UpdateProgramStatus = 'programs.updateStatus';
    case EnrollBeneficiaryInProgram = 'programs.enrollBeneficiary';

    // Phase 3: beneficiaries
    case ViewAnyBeneficiaries = 'beneficiaries.viewAny';
    case ViewBeneficiary = 'beneficiaries.view';
    case CreateBeneficiary = 'beneficiaries.create';
    case UpdateBeneficiary = 'beneficiaries.update';

    // Phase 3: staff & volunteers
    case ViewAnyEmployees = 'employees.viewAny';
    case CreateEmployee = 'employees.create';
    case UpdateEmployee = 'employees.update';
    case ViewAnyVolunteers = 'volunteers.viewAny';
    case CreateVolunteer = 'volunteers.create';
    case UpdateVolunteer = 'volunteers.update';

    // Phase 3: activities
    case ViewAnyActivities = 'activities.viewAny';
    case CreateActivity = 'activities.create';
    case UpdateActivity = 'activities.update';
    case RecordActivityAttendance = 'activities.recordAttendance';

    // Phase 4: expense categories (lookup)
    case ViewAnyExpenseCategories = 'expense-categories.viewAny';
    case ManageExpenseCategories = 'expense-categories.manage';

    // Phase 4: expenses (who may act at each workflow step is governed by
    // the step's role_required, not a permission — see docs/database-design.md §8)
    case ViewAnyExpenses = 'expenses.viewAny';
    case ViewExpense = 'expenses.view';
    case CreateExpense = 'expenses.create';
    case UpdateExpense = 'expenses.update';

    // Phase 4: assets
    case ViewAnyAssets = 'assets.viewAny';
    case CreateAsset = 'assets.create';
    case UpdateAsset = 'assets.update';
    case AssignAsset = 'assets.assign';

    // Phase 4: workflows (read-only visibility — see docs/database-design.md §8)
    case ViewAnyWorkflows = 'workflows.viewAny';

    // Phase 5: data imports & quality (see docs/database-design.md §9)
    case ViewAnyDataImports = 'data-imports.viewAny';
    case CreateDataImport = 'data-imports.create';
    case ViewAnyDataQualityIssues = 'data-quality-issues.viewAny';
    case ManageDataQualityIssues = 'data-quality-issues.manage';

    // Phase 7: Odoo integration (see docs/database-design.md §10)
    case ViewOdooStatus = 'odoo.viewAny';
    case RetryOdooSync = 'odoo.retry';
    case ManageOdooConfig = 'odoo.manageConfig';
}
