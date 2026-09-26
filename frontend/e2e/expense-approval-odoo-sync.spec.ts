import { expect, test } from "@playwright/test";
import { loginAs, logout, TEST_USERS } from "./support/auth";

/**
 * Mirrors docs/implementation-plan.md §6 demo scenario 3: Field Officer
 * submits an expense → Program Manager approves → Finance approves → Odoo
 * sync fires → visible on the Odoo Integration dashboard. Runs against
 * ODOO_MODE=mock (the default), so this exercises the real
 * ExpenseApproved -> OdooSyncJob -> FakeOdooClient path, not a stub.
 */
test("an approved expense produces a real Odoo sync log entry", async ({ page }) => {
  const programName = `E2E Expense Program ${Date.now()}`;

  await loginAs(page, TEST_USERS.programManager);
  await page.goto("/programs");
  await page.getByRole("button", { name: "Create program" }).click();
  await page.getByLabel("Name").fill(programName);
  await page.getByLabel("Start date").fill("2026-01-01");
  await page.getByLabel("Budget (BDT)").fill("100000");
  await page.getByRole("button", { name: "Create program" }).click();
  await expect(page).toHaveURL(/\/programs\/[^/]+$/);

  await logout(page);
  await loginAs(page, TEST_USERS.fieldOfficer);

  await page.goto("/finance");
  await page.getByRole("button", { name: "New expense" }).click();
  await page.getByText("Select program", { exact: true }).click();
  await page.getByRole("option", { name: programName }).click();
  await page.getByLabel("Amount").fill("1500");
  await page.getByLabel("Expense date").fill(new Date().toISOString().slice(0, 10));
  await page.getByRole("button", { name: "Create draft" }).click();

  await expect(page).toHaveURL(/\/finance\/expenses\/[^/]+$/);
  const expenseUrl = page.url();

  await page.getByRole("button", { name: "Submit for review" }).click();
  await expect(page.getByText("Program Review", { exact: true })).toBeVisible();

  await logout(page);
  await loginAs(page, TEST_USERS.programManager);
  await page.goto(expenseUrl);
  await page.getByRole("button", { name: "Approve" }).click();
  await expect(page.getByText("Finance Review", { exact: true })).toBeVisible();

  await logout(page);
  await loginAs(page, TEST_USERS.financeOfficer);
  await page.goto(expenseUrl);
  await page.getByRole("button", { name: "Approve" }).click();
  await expect(page.getByText("Approved", { exact: true })).toBeVisible();

  await logout(page);
  await loginAs(page, TEST_USERS.management);
  await page.goto("/odoo");

  // The sync fires via a queued job — the dashboard doesn't poll, so reload
  // until the async OdooSyncJob has actually landed the row.
  const expenseRow = page.getByRole("row").filter({ hasText: "Expense" }).first();
  await expect
    .poll(
      async () => {
        if (await expenseRow.isVisible()) return true;
        await page.reload();
        return expenseRow.isVisible();
      },
      { timeout: 20000, intervals: [1000] },
    )
    .toBe(true);
  await expect(expenseRow.getByText("Success")).toBeVisible({ timeout: 20000 });
});
