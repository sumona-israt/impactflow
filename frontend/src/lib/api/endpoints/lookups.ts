import { apiFetch } from "@/lib/api/client";
import type { Branch, Department, ProgramCategory } from "@/types/lookups";

interface Envelope<T> {
  data: T;
}

export function listDepartments(): Promise<Department[]> {
  return apiFetch<Envelope<Department[]>>("/api/v1/departments").then((res) => res.data);
}

export function createDepartment(name: string): Promise<Department> {
  return apiFetch<Envelope<Department>>("/api/v1/departments", { method: "POST", body: { name } }).then(
    (res) => res.data,
  );
}

export function listBranches(): Promise<Branch[]> {
  return apiFetch<Envelope<Branch[]>>("/api/v1/branches").then((res) => res.data);
}

export function createBranch(payload: { name: string; district?: string; upazila?: string }): Promise<Branch> {
  return apiFetch<Envelope<Branch>>("/api/v1/branches", { method: "POST", body: payload }).then((res) => res.data);
}

export function listProgramCategories(): Promise<ProgramCategory[]> {
  return apiFetch<Envelope<ProgramCategory[]>>("/api/v1/program-categories").then((res) => res.data);
}
