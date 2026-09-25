import { apiFetch } from "@/lib/api/client";
import type { ManagedUser, PageMeta } from "@/types/rbac";

interface ListEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

interface Envelope<T> {
  data: T;
}

export interface ListUsersParams {
  page?: number;
  q?: string;
  role?: string;
  isActive?: boolean;
}

function buildQuery(params: ListUsersParams): string {
  const search = new URLSearchParams();
  if (params.page) search.set("page", String(params.page));
  if (params.q) search.set("q", params.q);
  if (params.role) search.set("filter[role]", params.role);
  if (params.isActive !== undefined) search.set("filter[is_active]", String(params.isActive));
  const qs = search.toString();
  return qs ? `?${qs}` : "";
}

export function listUsers(params: ListUsersParams = {}): Promise<ListEnvelope<ManagedUser>> {
  return apiFetch<ListEnvelope<ManagedUser>>(`/api/v1/users${buildQuery(params)}`);
}

export interface CreateUserPayload {
  name: string;
  email: string;
  phone?: string;
  password: string;
  roles: string[];
}

export function createUser(payload: CreateUserPayload): Promise<ManagedUser> {
  return apiFetch<Envelope<ManagedUser>>("/api/v1/users", { method: "POST", body: payload }).then(
    (res) => res.data,
  );
}

export interface UpdateUserPayload {
  name?: string;
  email?: string;
  phone?: string | null;
}

export function updateUser(id: number, payload: UpdateUserPayload): Promise<ManagedUser> {
  return apiFetch<Envelope<ManagedUser>>(`/api/v1/users/${id}`, {
    method: "PUT",
    body: payload,
  }).then((res) => res.data);
}

export function syncUserRoles(id: number, roles: string[]): Promise<ManagedUser> {
  return apiFetch<Envelope<ManagedUser>>(`/api/v1/users/${id}/roles`, {
    method: "PUT",
    body: { roles },
  }).then((res) => res.data);
}

export function toggleUserActive(id: number, isActive: boolean): Promise<ManagedUser> {
  return apiFetch<Envelope<ManagedUser>>(`/api/v1/users/${id}/active`, {
    method: "PATCH",
    body: { is_active: isActive },
  }).then((res) => res.data);
}
