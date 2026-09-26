import { apiFetch } from "@/lib/api/client";
import type { NotificationEntry } from "@/types/notifications";
import type { PageMeta } from "@/types/rbac";

interface Envelope<T> {
  data: T;
}

interface ListEnvelope<T> {
  data: T[];
  meta: PageMeta;
}

export function listNotifications(page = 1): Promise<ListEnvelope<NotificationEntry>> {
  const search = new URLSearchParams();
  if (page > 1) search.set("page", String(page));
  const qs = search.toString();
  return apiFetch<ListEnvelope<NotificationEntry>>(`/api/v1/notifications${qs ? `?${qs}` : ""}`);
}

export function getUnreadNotificationCount(): Promise<number> {
  return apiFetch<Envelope<{ count: number }>>("/api/v1/notifications/unread-count").then(
    (res) => res.data.count,
  );
}

export function markNotificationRead(id: string): Promise<NotificationEntry> {
  return apiFetch<Envelope<NotificationEntry>>(`/api/v1/notifications/${id}/read`, { method: "POST" }).then(
    (res) => res.data,
  );
}

export function markAllNotificationsRead(): Promise<void> {
  return apiFetch<{ message: string }>("/api/v1/notifications/read-all", { method: "POST" }).then(() => undefined);
}
