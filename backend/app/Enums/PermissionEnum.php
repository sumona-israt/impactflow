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
}
