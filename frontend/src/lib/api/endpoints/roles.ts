import { apiFetch } from "@/lib/api/client";
import type { Permission, Role } from "@/types/rbac";

interface Envelope<T> {
  data: T;
}

export function listRoles(): Promise<Role[]> {
  return apiFetch<Envelope<Role[]>>("/api/v1/roles").then((res) => res.data);
}

export function listPermissions(): Promise<Permission[]> {
  return apiFetch<Envelope<Permission[]>>("/api/v1/permissions").then((res) => res.data);
}

export function updateRolePermissions(roleId: number, permissions: string[]): Promise<Role> {
  return apiFetch<Envelope<Role>>(`/api/v1/roles/${roleId}/permissions`, {
    method: "PUT",
    body: { permissions },
  }).then((res) => res.data);
}
