import { apiFetch } from "@/lib/api/client";
import type { Activity, AttendanceRecord } from "@/types/activities";

interface Envelope<T> {
  data: T;
}

export function getActivity(id: string): Promise<Activity> {
  return apiFetch<Envelope<Activity>>(`/api/v1/activities/${id}`).then((res) => res.data);
}

export function updateActivity(id: string, payload: Partial<Pick<Activity, "title" | "description" | "scheduled_at" | "location" | "status">>): Promise<Activity> {
  return apiFetch<Envelope<Activity>>(`/api/v1/activities/${id}`, { method: "PUT", body: payload }).then(
    (res) => res.data,
  );
}

export function listAttendance(activityId: string): Promise<AttendanceRecord[]> {
  return apiFetch<Envelope<AttendanceRecord[]>>(`/api/v1/activities/${activityId}/attendance`).then(
    (res) => res.data,
  );
}

export function recordAttendance(
  activityId: string,
  attendance: Record<string, boolean>,
): Promise<AttendanceRecord[]> {
  return apiFetch<Envelope<AttendanceRecord[]>>(`/api/v1/activities/${activityId}/attendance`, {
    method: "POST",
    body: { attendance },
  }).then((res) => res.data);
}
