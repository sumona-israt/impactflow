import { apiFetch } from "@/lib/api/client";
import type { PageMeta } from "@/types/rbac";
import type { ReportFormat, ReportHistoryEntry, ReportType } from "@/types/reports";

interface ListEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

export function listReports(page = 1): Promise<ListEnvelope<ReportHistoryEntry>> {
  const search = new URLSearchParams();
  if (page > 1) search.set("page", String(page));
  const qs = search.toString();
  return apiFetch<ListEnvelope<ReportHistoryEntry>>(`/api/v1/reports${qs ? `?${qs}` : ""}`);
}

export interface ReportFilters {
  program_id?: string;
  status?: string;
  category_id?: string;
  district?: string;
  date_from?: string;
  date_to?: string;
}

/**
 * A plain URL, not an apiFetch call — used as an <a href> target so the
 * browser's own session cookie authenticates the download directly (apiFetch
 * only supports JSON responses). See attachmentDownloadUrl in expenses.ts.
 */
export function reportDownloadUrl(type: ReportType, format: ReportFormat, filters: ReportFilters = {}): string {
  const base = process.env.NEXT_PUBLIC_API_URL ?? "";
  const search = new URLSearchParams({ format });
  for (const [key, value] of Object.entries(filters)) {
    if (value) search.set(key, value);
  }
  return `${base}/api/v1/reports/${type}?${search.toString()}`;
}

export function reportRedownloadUrl(reportId: string): string {
  const base = process.env.NEXT_PUBLIC_API_URL ?? "";
  return `${base}/api/v1/reports/${reportId}/download`;
}
