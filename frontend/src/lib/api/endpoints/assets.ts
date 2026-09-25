import { apiFetch } from "@/lib/api/client";
import type { Asset, AssetStatus } from "@/types/finance";
import type { PageMeta } from "@/types/rbac";

interface ListEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

interface Envelope<T> {
  data: T;
}

export function listAssets(params: { page?: number; q?: string; status?: AssetStatus } = {}): Promise<ListEnvelope<Asset>> {
  const search = new URLSearchParams();
  if (params.page) search.set("page", String(params.page));
  if (params.q) search.set("q", params.q);
  if (params.status) search.set("filter[status]", params.status);
  const qs = search.toString();

  return apiFetch<ListEnvelope<Asset>>(`/api/v1/assets${qs ? `?${qs}` : ""}`);
}

export interface CreateAssetPayload {
  name: string;
  category?: string;
  serial_number?: string;
  purchase_date?: string;
  purchase_value?: number;
  location?: string;
  condition?: string;
}

export function createAsset(payload: CreateAssetPayload): Promise<Asset> {
  return apiFetch<Envelope<Asset>>("/api/v1/assets", { method: "POST", body: payload }).then((res) => res.data);
}

export function updateAsset(id: string, payload: Partial<CreateAssetPayload>): Promise<Asset> {
  return apiFetch<Envelope<Asset>>(`/api/v1/assets/${id}`, { method: "PUT", body: payload }).then((res) => res.data);
}

export function assignAsset(id: string, userId: number): Promise<Asset> {
  return apiFetch<Envelope<Asset>>(`/api/v1/assets/${id}/assign`, {
    method: "POST",
    body: { user_id: userId },
  }).then((res) => res.data);
}

export function returnAsset(id: string): Promise<Asset> {
  return apiFetch<Envelope<Asset>>(`/api/v1/assets/${id}/return`, { method: "POST" }).then((res) => res.data);
}
