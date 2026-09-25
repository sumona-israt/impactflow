import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

/**
 * Next.js 16 renamed `middleware.ts` to `proxy.ts` (same runtime semantics).
 *
 * This is a cheap, non-authoritative gate: it only checks whether the
 * Laravel session cookie is present, to fast-redirect obvious guests before
 * any rendering happens. It never trusts the cookie's validity — the
 * authoritative check is `getServerAuth()` in the (dashboard) layout, which
 * actually calls Laravel (see docs/architecture.md §4).
 */
const SESSION_COOKIE_NAME = process.env.SESSION_COOKIE_NAME ?? "impactflow_session";

export function proxy(request: NextRequest) {
  const hasSessionCookie = request.cookies.has(SESSION_COOKIE_NAME);
  const { pathname } = request.nextUrl;

  if (pathname.startsWith("/login") && hasSessionCookie) {
    return NextResponse.redirect(new URL("/dashboard", request.url));
  }

  if (pathname.startsWith("/dashboard") && !hasSessionCookie) {
    return NextResponse.redirect(new URL("/login", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/login", "/dashboard/:path*"],
};
