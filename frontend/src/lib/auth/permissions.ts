import type { AuthUser } from "@/types/auth";

export function hasPermission(user: AuthUser | null, permission: string): boolean {
  return user?.permissions.includes(permission) ?? false;
}

export function hasAnyPermission(user: AuthUser | null, permissions: string[]): boolean {
  return permissions.some((permission) => hasPermission(user, permission));
}
