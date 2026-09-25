import { apiFetch } from "@/lib/api/client";
import type { AuditLogEntry, PageMeta } from "@/types/rbac";

interface ListEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

export interface ListAuditLogsParams {
  page?: number;
  action?: string;
  entityType?: string;
  dateFrom?: string;
  dateTo?: string;
}

function buildQuery(params: ListAuditLogsParams): string {
  const search = new URLSearchParams();
  if (params.page) search.set("page", String(params.page));
  if (params.action) search.set("filter[action]", params.action);
  if (params.entityType) search.set("filter[entity_type]", params.entityType);
  if (params.dateFrom) search.set("filter[date_from]", params.dateFrom);
  if (params.dateTo) search.set("filter[date_to]", params.dateTo);
  const qs = search.toString();
  return qs ? `?${qs}` : "";
}

export function listAuditLogs(
  params: ListAuditLogsParams = {},
): Promise<ListEnvelope<AuditLogEntry>> {
  return apiFetch<ListEnvelope<AuditLogEntry>>(`/api/v1/audit-logs${buildQuery(params)}`);
}
