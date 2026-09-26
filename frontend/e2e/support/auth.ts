import { expect, type Page } from "@playwright/test";

export const SUPER_ADMIN = {
  name: "ImpactFlow Admin",
  email: "admin@impactflow.test",
  password: process.env.DEMO_ADMIN_PASSWORD ?? "password",
};

/**
 * One demo user per non-SuperAdmin role, provisioned idempotently via the
 * real API in e2e/global-setup.ts rather than a test-only seeder — see
 * docs/testing-strategy.md.
 */
export const TEST_USERS = {
  programManager: {
    name: "E2E Program Manager",
    email: "e2e.program-manager@impactflow.test",
    password: "Password123!",
    role: "program-manager",
  },
  management: {
    name: "E2E Management",
    email: "e2e.management@impactflow.test",
    password: "Password123!",
    role: "management",
  },
  fieldOfficer: {
    name: "E2E Field Officer",
    email: "e2e.field-officer@impactflow.test",
    password: "Password123!",
    role: "field-officer",
  },
  financeOfficer: {
    name: "E2E Finance Officer",
    email: "e2e.finance-officer@impactflow.test",
    password: "Password123!",
    role: "finance-officer",
  },
} as const;

/**
 * Logs in via the real login form — exercises the Sanctum SPA cookie dance
 * (CSRF cookie, session cookie, X-XSRF-TOKEN) rather than a shortcut, since
 * that's part of what E2E coverage is meant to verify.
 */
export async function loginAs(page: Page, user: { email: string; password: string }) {
  if (!page.url().endsWith("/login")) {
    await page.goto("/login");
  }
  try {
    await expect(page.getByLabel("Email")).toBeVisible({ timeout: 5000 });
  } catch {
    await page.reload();
    await expect(page.getByLabel("Email")).toBeVisible({ timeout: 10000 });
  }
  await page.getByLabel("Email").fill(user.email);
  await page.getByLabel("Password").fill(user.password);
  await page.getByRole("button", { name: "Sign in" }).click();
  await expect(page).toHaveURL(/\/dashboard/);
}

/**
 * Clears the browser's cookie jar directly rather than calling the logout
 * endpoint or driving the topbar menu — logout itself isn't one of the
 * docs/implementation-plan.md §6 demo scenarios, this is pure test plumbing
 * for switching roles mid-spec. A cleared session cookie is exactly as
 * "logged out" as a server-side logout as far as the next loginAs() call is
 * concerned; the real login form is what actually needs UI coverage.
 *
 * The immediate goto("/login") right after a full-page redirect (the
 * previous action's login/approval landed on a fresh page) occasionally
 * races with Next dev-mode's aborted RSC prefetch requests and leaves the
 * page stuck mid-navigation — reload once if the form didn't actually show
 * up rather than let the next loginAs() hang on a missing field.
 */
export async function logout(page: Page) {
  await page.context().clearCookies();
  await page.goto("/login");
  try {
    await expect(page.getByLabel("Email")).toBeVisible({ timeout: 5000 });
  } catch {
    await page.reload();
    await expect(page.getByLabel("Email")).toBeVisible({ timeout: 10000 });
  }
}
