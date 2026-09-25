import "server-only";

import { cookies, headers } from "next/headers";
import type { AuthUser } from "@/types/auth";

/**
 * Server Components/Route Handlers don't forward the browser's cookies
 * automatically when calling Laravel (see docs/architecture.md §3). This is
 * the one seam every protected server component uses to read the incoming
 * request's cookies and forward them to Laravel's /api/v1/user endpoint.
 *
 * INTERNAL_API_URL points at nginx's address on the Docker network (the
 * Next.js server container cannot resolve "the browser's origin" the way a
 * client-side fetch can); NEXT_PUBLIC_API_URL is used for client-side calls
 * instead (see lib/api/client.ts).
 */
const INTERNAL_API_URL = process.env.INTERNAL_API_URL ?? "http://localhost";

export async function getServerAuth(): Promise<AuthUser | null> {
  const cookieStore = await cookies();
  const cookieHeader = cookieStore
    .getAll()
    .map((cookie) => `${cookie.name}=${cookie.value}`)
    .join("; ");

  if (!cookieHeader) {
    return null;
  }

  // Sanctum's EnsureFrontendRequestsAreStateful only authenticates a request
  // via the session cookie when its Referer/Origin matches a configured
  // stateful domain (see docs/architecture.md §3) — without this, every
  // server-to-server call here would look unauthenticated even with a valid
  // session cookie attached. Forwarding the browser's own incoming Host is
  // exactly the same origin Sanctum expects, since nginx is the single entry
  // point for both the frontend and the API.
  const incomingHost = (await headers()).get("host");

  const response = await fetch(`${INTERNAL_API_URL}/api/v1/user`, {
    headers: {
      Accept: "application/json",
      Cookie: cookieHeader,
      ...(incomingHost ? { Referer: `http://${incomingHost}/` } : {}),
    },
    cache: "no-store",
  });

  if (!response.ok) {
    return null;
  }

  const body = (await response.json()) as { data: AuthUser };

  return body.data;
}
