import { apiFetch } from "@/lib/api/client";
import type { AuthUser } from "@/types/auth";

interface Envelope<T> {
  data: T;
}

export function login(email: string, password: string): Promise<AuthUser> {
  return apiFetch<Envelope<AuthUser>>("/api/v1/login", {
    method: "POST",
    body: { email, password },
  }).then((res) => res.data);
}

export function logout(): Promise<void> {
  return apiFetch<void>("/api/v1/logout", { method: "POST" });
}

export function fetchCurrentUser(): Promise<AuthUser> {
  return apiFetch<Envelope<AuthUser>>("/api/v1/user").then((res) => res.data);
}
