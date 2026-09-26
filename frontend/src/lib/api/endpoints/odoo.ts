import { apiFetch } from "@/lib/api/client";
import type { OdooConfig, OdooStatus, OdooSyncLogEntry } from "@/types/odoo";
import type { PageMeta } from "@/types/rbac";

interface Envelope<T> {
  data: T;
}

interface ListEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

export function getOdooStatus(): Promise<OdooStatus> {
  return apiFetch<Envelope<OdooStatus>>("/api/v1/odoo/status").then((res) => res.data);
}

export function listOdooSyncLogs(page = 1, status?: string): Promise<ListEnvelope<OdooSyncLogEntry>> {
  const search = new URLSearchParams();
  if (page > 1) search.set("page", String(page));
  if (status) search.set("filter[status]", status);
  const qs = search.toString();
  return apiFetch<ListEnvelope<OdooSyncLogEntry>>(`/api/v1/odoo/sync-logs${qs ? `?${qs}` : ""}`);
}

/**
 * `entity` is the backend's short slug (program/beneficiary/employee/expense)
 * — lowercasing a sync log row's `entity_type` (e.g. "Program") happens to
 * match it exactly, since that's how config('odoo.mappings') names each slug.
 */
export function retryOdooSync(entity: string, id: string): Promise<void> {
  return apiFetch<{ message: string }>(`/api/v1/odoo/sync/${entity}/${id}/retry`, { method: "POST" }).then(
    () => undefined,
  );
}

export function getOdooConfig(): Promise<OdooConfig> {
  return apiFetch<Envelope<OdooConfig>>("/api/v1/odoo/config").then((res) => res.data);
}

export function updateOdooConfig(payload: { is_active: boolean }): Promise<OdooConfig> {
  return apiFetch<Envelope<OdooConfig>>("/api/v1/odoo/config", { method: "PUT", body: payload }).then(
    (res) => res.data,
  );
}
