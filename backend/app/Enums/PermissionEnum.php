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
}
