import { apiFetch } from "@/lib/api/client";
import type { DashboardKpis } from "@/types/dashboard";

interface Envelope<T> {
  data: T;
}

export function getDashboardKpis(): Promise<DashboardKpis> {
  return apiFetch<Envelope<DashboardKpis>>("/api/v1/dashboard/kpis").then((res) => res.data);
}
