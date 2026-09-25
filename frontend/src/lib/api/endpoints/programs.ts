import { apiFetch } from "@/lib/api/client";
import type { Enrollment, Program, ProgramStatus } from "@/types/programs";
import type { Activity } from "@/types/activities";
import type { PageMeta } from "@/types/rbac";

interface ListEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

interface Envelope<T> {
  data: T;
}

export interface ListProgramsParams {
  page?: number;
  q?: string;
  status?: ProgramStatus;
  categoryId?: number;
}

function buildQuery(params: ListProgramsParams): string {
  const search = new URLSearchParams();
  if (params.page) search.set("page", String(params.page));
  if (params.q) search.set("q", params.q);
  if (params.status) search.set("filter[status]", params.status);
  if (params.categoryId) search.set("filter[category_id]", String(params.categoryId));
  const qs = search.toString();
  return qs ? `?${qs}` : "";
}

export function listPrograms(params: ListProgramsParams = {}): Promise<ListEnvelope<Program>> {
  return apiFetch<ListEnvelope<Program>>(`/api/v1/programs${buildQuery(params)}`);
}

export function getProgram(id: string): Promise<Program> {
  return apiFetch<Envelope<Program>>(`/api/v1/programs/${id}`).then((res) => res.data);
}

export interface CreateProgramPayload {
  name: string;
  description?: string;
  category_id?: number;
  manager_id?: number;
  branch_id?: number;
  district?: string;
  upazila?: string;
  start_date?: string;
  end_date?: string;
  budget?: number;
  target_beneficiaries?: number;
}

export function createProgram(payload: CreateProgramPayload): Promise<Program> {
  return apiFetch<Envelope<Program>>("/api/v1/programs", { method: "POST", body: payload }).then((res) => res.data);
}

export function updateProgram(id: string, payload: Partial<CreateProgramPayload>): Promise<Program> {
  return apiFetch<Envelope<Program>>(`/api/v1/programs/${id}`, { method: "PUT", body: payload }).then(
    (res) => res.data,
  );
}

export function updateProgramStatus(id: string, status: ProgramStatus): Promise<Program> {
  return apiFetch<Envelope<Program>>(`/api/v1/programs/${id}/status`, {
    method: "PATCH",
    body: { status },
  }).then((res) => res.data);
}

export function listProgramBeneficiaries(id: string): Promise<Enrollment[]> {
  return apiFetch<Envelope<Enrollment[]>>(`/api/v1/programs/${id}/beneficiaries`).then((res) => res.data);
}

export function enrollBeneficiary(programId: string, beneficiaryId: string): Promise<Enrollment> {
  return apiFetch<Envelope<Enrollment>>(`/api/v1/programs/${programId}/beneficiaries`, {
    method: "POST",
    body: { beneficiary_id: beneficiaryId },
  }).then((res) => res.data);
}

export function unenrollBeneficiary(programId: string, enrollmentId: number): Promise<Enrollment> {
  return apiFetch<Envelope<Enrollment>>(`/api/v1/programs/${programId}/beneficiaries/${enrollmentId}`, {
    method: "DELETE",
  }).then((res) => res.data);
}

export function listProgramActivities(id: string): Promise<Activity[]> {
  return apiFetch<Envelope<Activity[]>>(`/api/v1/programs/${id}/activities`).then((res) => res.data);
}

export function createProgramActivity(
  programId: string,
  payload: { title: string; description?: string; scheduled_at: string; location?: string },
): Promise<Activity> {
  return apiFetch<Envelope<Activity>>(`/api/v1/programs/${programId}/activities`, {
    method: "POST",
    body: payload,
  }).then((res) => res.data);
}
