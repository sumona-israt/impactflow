import { expect, test } from "@playwright/test";
import { loginAs, logout, TEST_USERS } from "./support/auth";

/**
 * Mirrors docs/implementation-plan.md §6 demo scenario 1: Program Manager
 * creates a program → submits for approval → Management approves →
 * dashboard updates.
 */
test("Program Manager creates a program, submits it, and Management approves it", async ({ page }) => {
  const programName = `E2E Rural Education ${Date.now()}`;

  await loginAs(page, TEST_USERS.programManager);

  await page.goto("/programs");
  await page.getByRole("button", { name: "Create program" }).click();
  await page.getByLabel("Name").fill(programName);
  await page.getByLabel("Start date").fill("2026-01-01");
  await page.getByRole("button", { name: "Create program" }).click();

  await expect(page).toHaveURL(/\/programs\/[^/]+$/);
  await expect(page.getByRole("heading", { name: programName })).toBeVisible();

  // Draft -> Pending Approval. Not getByRole("combobox", {name}): base-ui's
  // Select doesn't wire its placeholder text into the trigger's accessible
  // name, only into visible text content.
  await page.getByText("Change status", { exact: true }).click();
  await page.getByRole("option", { name: "Pending Approval" }).click();
  await expect(page.getByText("Pending Approval", { exact: true })).toBeVisible();

  const programUrl = page.url();

  await logout(page);
  await loginAs(page, TEST_USERS.management);

  await page.goto(programUrl);
  await expect(page.getByRole("heading", { name: programName })).toBeVisible();

  // Pending Approval -> Approved
  await page.getByText("Change status", { exact: true }).click();
  await page.getByRole("option", { name: "Approved" }).click();
  await expect(page.getByText("Approved", { exact: true })).toBeVisible();

  await page.goto("/dashboard");
  await expect(page.getByText("Active Programs")).toBeVisible();
});
