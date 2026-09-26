import { apiFetch } from "@/lib/api/client";
import type {
  DataImport,
  DataImportRow,
  DataQualityIssue,
  DataQualityIssueStatus,
  DataQualityScore,
  ImportEntityType,
} from "@/types/data-quality";
import type { PageMeta } from "@/types/rbac";

interface ListEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

interface Envelope<T> {
  data: T;
}

export function uploadImport(file: File, entityType: ImportEntityType): Promise<DataImport> {
  const formData = new FormData();
  formData.append("file", file);
  formData.append("entity_type", entityType);

  return apiFetch<Envelope<DataImport>>("/api/v1/imports", { method: "POST", body: formData }).then(
    (res) => res.data,
  );
}

export function listImports(page = 1): Promise<ListEnvelope<DataImport>> {
  return apiFetch<ListEnvelope<DataImport>>(`/api/v1/imports?page=${page}`);
}

export function getImport(id: string): Promise<DataImport> {
  return apiFetch<Envelope<DataImport>>(`/api/v1/imports/${id}`).then((res) => res.data);
}

export function updateImportMapping(id: string, mapping: Record<string, string>): Promise<DataImport> {
  return apiFetch<Envelope<DataImport>>(`/api/v1/imports/${id}/mapping`, {
    method: "PUT",
    body: { mapping },
  }).then((res) => res.data);
}

export function runImportPreview(id: string): Promise<DataImport> {
  return apiFetch<Envelope<DataImport>>(`/api/v1/imports/${id}/preview`, { method: "POST" }).then(
    (res) => res.data,
  );
}

export function getImportPreviewRows(id: string, page = 1): Promise<ListEnvelope<DataImportRow>> {
  return apiFetch<ListEnvelope<DataImportRow>>(`/api/v1/imports/${id}/preview?page=${page}`);
}

export function commitImport(id: string): Promise<DataImport> {
  return apiFetch<Envelope<DataImport>>(`/api/v1/imports/${id}/commit`, { method: "POST" }).then(
    (res) => res.data,
  );
}

export function listDataQualityIssues(
  status?: DataQualityIssueStatus,
  page = 1,
): Promise<ListEnvelope<DataQualityIssue>> {
  const search = new URLSearchParams({ page: String(page) });
  if (status) search.set("filter[status]", status);

  return apiFetch<ListEnvelope<DataQualityIssue>>(`/api/v1/data-quality/issues?${search.toString()}`);
}

export function resolveDataQualityIssue(id: string): Promise<DataQualityIssue> {
  return apiFetch<Envelope<DataQualityIssue>>(`/api/v1/data-quality/issues/${id}/resolve`, {
    method: "POST",
  }).then((res) => res.data);
}

export function ignoreDataQualityIssue(id: string): Promise<DataQualityIssue> {
  return apiFetch<Envelope<DataQualityIssue>>(`/api/v1/data-quality/issues/${id}/ignore`, {
    method: "POST",
  }).then((res) => res.data);
}

export function getDataQualityScore(): Promise<DataQualityScore> {
  return apiFetch<Envelope<DataQualityScore>>("/api/v1/data-quality/score").then((res) => res.data);
}
