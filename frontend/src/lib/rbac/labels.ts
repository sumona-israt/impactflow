/** Slugs match backend App\Enums\RoleEnum values. */
export const ROLE_OPTIONS = [
  "super-admin",
  "program-manager",
  "finance-officer",
  "field-officer",
  "hr-admin-officer",
  "management",
] as const;

export function roleLabel(role: string): string {
  return role
    .split("-")
    .map((word) => word[0].toUpperCase() + word.slice(1))
    .join(" ");
}

/**
 * Known audit actions so far (see App\Services\Audit\AuditLogger call
 * sites). Phase 3+ modules append their own here as they add auditing.
 */
export const AUDIT_ACTIONS = [
  "user.created",
  "user.updated",
  "user.activated",
  "user.deactivated",
  "user.roles_changed",
  "role.permissions_changed",
] as const;

export function auditActionLabel(action: string): string {
  return action.replace(/[._]/g, " ");
}

export function permissionLabel(permission: string): string {
  const [resource, action] = permission.split(".");
  const readableAction = action.replace(/([a-z])([A-Z])/g, "$1 $2").toLowerCase();
  return `${readableAction} ${resource.replace("-", " ")}`;
}
