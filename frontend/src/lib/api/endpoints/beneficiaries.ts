import { apiFetch } from "@/lib/api/client";
import type { Beneficiary, BeneficiaryListItem, BeneficiaryStatus } from "@/types/beneficiaries";
import type { Enrollment } from "@/types/programs";
import type { PageMeta } from "@/types/rbac";

interface ListEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

interface Envelope<T> {
  data: T;
}

export interface ListBeneficiariesParams {
  page?: number;
  q?: string;
  status?: BeneficiaryStatus;
  district?: string;
}

function buildQuery(params: ListBeneficiariesParams): string {
  const search = new URLSearchParams();
  if (params.page) search.set("page", String(params.page));
  if (params.q) search.set("q", params.q);
  if (params.status) search.set("filter[status]", params.status);
  if (params.district) search.set("filter[district]", params.district);
  const qs = search.toString();
  return qs ? `?${qs}` : "";
}

export function listBeneficiaries(
  params: ListBeneficiariesParams = {},
): Promise<ListEnvelope<BeneficiaryListItem>> {
  return apiFetch<ListEnvelope<BeneficiaryListItem>>(`/api/v1/beneficiaries${buildQuery(params)}`);
}

export function getBeneficiary(id: string): Promise<Beneficiary> {
  return apiFetch<Envelope<Beneficiary>>(`/api/v1/beneficiaries/${id}`).then((res) => res.data);
}

export interface CreateBeneficiaryPayload {
  full_name: string;
  registration_date: string;
  date_of_birth?: string;
  gender?: string;
  phone?: string;
  email?: string;
  address?: string;
  district?: string;
  upazila?: string;
  emergency_contact_name?: string;
  emergency_contact_phone?: string;
  notes?: string;
}

export function createBeneficiary(payload: CreateBeneficiaryPayload): Promise<Beneficiary> {
  return apiFetch<Envelope<Beneficiary>>("/api/v1/beneficiaries", { method: "POST", body: payload }).then(
    (res) => res.data,
  );
}

export function updateBeneficiary(
  id: string,
  payload: Partial<CreateBeneficiaryPayload>,
): Promise<Beneficiary> {
  return apiFetch<Envelope<Beneficiary>>(`/api/v1/beneficiaries/${id}`, { method: "PUT", body: payload }).then(
    (res) => res.data,
  );
}

export function listBeneficiaryEnrollments(id: string): Promise<Enrollment[]> {
  return apiFetch<Envelope<Enrollment[]>>(`/api/v1/beneficiaries/${id}/enrollments`).then((res) => res.data);
}
