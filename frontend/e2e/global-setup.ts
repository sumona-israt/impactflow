import { execFileSync } from "node:child_process";
import { request, type FullConfig } from "@playwright/test";
import { SUPER_ADMIN, TEST_USERS } from "./support/auth";

/**
 * Runs once before the whole suite: idempotently provisions one demo user
 * per role via the real API (authenticated as the seeded SuperAdmin), so
 * the E2E suite doesn't depend on a test-only seeder — see
 * docs/testing-strategy.md. Safe to re-run against the same database
 * (a 422 "email already taken" response means the user already exists).
 */
export default async function globalSetup(config: FullConfig) {
  const baseURL = config.projects[0]?.use?.baseURL ?? "http://localhost";
  const context = await request.newContext({ baseURL, extraHTTPHeaders: { Referer: `${baseURL}/` } });

  // The full suite logs each test user in 2-3 times, comfortably under the
  // login route's 5/min throttle (see AppServiceProvider::boot) in a single
  // clean run — but re-running the suite repeatedly in quick succession
  // (as happens while debugging) stacks attempts within the same rolling
  // window and starts 429ing real, correct logins. Clear it up front rather
  // than loosen a deliberate security control.
  execFileSync("docker", ["compose", "exec", "-T", "laravel", "php", "artisan", "cache:clear"], {
    cwd: "..",
    encoding: "utf-8",
  });

  // Re-read the XSRF-TOKEN cookie before every mutating request rather than
  // once up front — logging in regenerates the session, and the encrypted
  // cookie value that guards it changes too (Sanctum ties the CSRF token to
  // the session), so a stale header 419s.
  async function xsrfHeaders(): Promise<Record<string, string>> {
    await context.get("/sanctum/csrf-cookie");
    const token = (await context.storageState()).cookies.find((c) => c.name === "XSRF-TOKEN")?.value;
    return token ? { "X-XSRF-TOKEN": decodeURIComponent(token) } : {};
  }

  const login = await context.post("/api/v1/login", {
    headers: await xsrfHeaders(),
    data: { email: SUPER_ADMIN.email, password: SUPER_ADMIN.password },
  });

  if (!login.ok()) {
    throw new Error(`E2E global setup: could not log in as the seeded SuperAdmin (${login.status()}). Has the app been migrated + seeded?`);
  }

  for (const user of Object.values(TEST_USERS)) {
    const response = await context.post("/api/v1/users", {
      headers: await xsrfHeaders(),
      data: { name: user.name, email: user.email, password: user.password, roles: [user.role] },
    });

    if (!response.ok() && response.status() !== 422) {
      throw new Error(`E2E global setup: failed to provision ${user.email} (${response.status()}): ${await response.text()}`);
    }
  }

  await context.dispose();
}
