import { defineConfig, devices } from "@playwright/test";

/**
 * Runs against an already-running `docker compose` stack (the app needs
 * Postgres/Redis/the queue worker, which Playwright's own webServer
 * bootstrapping isn't set up for) — see docs/testing-strategy.md.
 *
 * fullyParallel/workers are deliberately serial: specs share one Postgres
 * database with no per-test transaction isolation (unlike Pest's
 * RefreshDatabase), so concurrent specs could observe each other's data
 * (e.g. dashboard KPI counts).
 */
export default defineConfig({
  testDir: "./e2e",
  globalSetup: "./e2e/global-setup.ts",
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: "list",
  use: {
    baseURL: process.env.E2E_BASE_URL ?? "http://localhost",
    trace: "retain-on-failure",
    screenshot: "only-on-failure",
  },
  projects: [{ name: "chromium", use: { ...devices["Desktop Chrome"] } }],
});
