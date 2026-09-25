import { apiFetch } from "@/lib/api/client";
import type { Employee, EmployeeStatus, Volunteer, VolunteerStatus } from "@/types/staff";
import type { PageMeta } from "@/types/rbac";

interface ListEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

interface Envelope<T> {
  data: T;
}

function buildQuery(params: { page?: number; q?: string; status?: string }): string {
  const search = new URLSearchParams();
  if (params.page) search.set("page", String(params.page));
  if (params.q) search.set("q", params.q);
  if (params.status) search.set("filter[status]", params.status);
  const qs = search.toString();
  return qs ? `?${qs}` : "";
}

export function listEmployees(
  params: { page?: number; q?: string; status?: EmployeeStatus } = {},
): Promise<ListEnvelope<Employee>> {
  return apiFetch<ListEnvelope<Employee>>(`/api/v1/employees${buildQuery(params)}`);
}

export interface CreateEmployeePayload {
  name: string;
  department_id?: number;
  branch_id?: number;
  position?: string;
  joining_date?: string;
}

export function createEmployee(payload: CreateEmployeePayload): Promise<Employee> {
  return apiFetch<Envelope<Employee>>("/api/v1/employees", { method: "POST", body: payload }).then(
    (res) => res.data,
  );
}

export function updateEmployee(id: string, payload: Partial<CreateEmployeePayload>): Promise<Employee> {
  return apiFetch<Envelope<Employee>>(`/api/v1/employees/${id}`, { method: "PUT", body: payload }).then(
    (res) => res.data,
  );
}

export function listVolunteers(
  params: { page?: number; q?: string; status?: VolunteerStatus } = {},
): Promise<ListEnvelope<Volunteer>> {
  return apiFetch<ListEnvelope<Volunteer>>(`/api/v1/volunteers${buildQuery(params)}`);
}

export interface CreateVolunteerPayload {
  full_name: string;
  phone?: string;
  email?: string;
  skills?: string[];
  availability?: string;
}

export function createVolunteer(payload: CreateVolunteerPayload): Promise<Volunteer> {
  return apiFetch<Envelope<Volunteer>>("/api/v1/volunteers", { method: "POST", body: payload }).then(
    (res) => res.data,
  );
}

export function updateVolunteer(id: string, payload: Partial<CreateVolunteerPayload>): Promise<Volunteer> {
  return apiFetch<Envelope<Volunteer>>(`/api/v1/volunteers/${id}`, { method: "PUT", body: payload }).then(
    (res) => res.data,
  );
}
