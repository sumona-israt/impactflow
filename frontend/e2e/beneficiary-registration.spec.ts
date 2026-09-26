import { expect, test } from "@playwright/test";
import { loginAs, TEST_USERS } from "./support/auth";

/**
 * Mirrors docs/implementation-plan.md §6 demo scenario 2: Field Officer
 * registers a beneficiary → duplicate detection runs → data quality
 * validation → visible on the data quality dashboard.
 */
test("registering a duplicate beneficiary surfaces a data quality issue", async ({ page }) => {
  const fullName = `E2E Duplicate Test ${Date.now()}`;
  const phone = "01711112222";

  await loginAs(page, TEST_USERS.fieldOfficer);

  // First registration — the "original".
  await page.goto("/beneficiaries");
  await page.getByRole("button", { name: "Register beneficiary" }).click();
  await page.getByLabel("Full name").fill(fullName);
  await page.getByLabel("Phone", { exact: true }).fill(phone);
  await page.getByLabel("Registration date").fill(new Date().toISOString().slice(0, 10));
  await page.getByRole("button", { name: "Register beneficiary" }).click();
  await expect(page).toHaveURL(/\/beneficiaries\/[^/]+$/);

  // Second registration — same name + phone, triggers duplicate detection.
  await page.goto("/beneficiaries");
  await page.getByRole("button", { name: "Register beneficiary" }).click();
  await page.getByLabel("Full name").fill(fullName);
  await page.getByLabel("Phone", { exact: true }).fill(phone);
  await page.getByLabel("Registration date").fill(new Date().toISOString().slice(0, 10));
  await page.getByRole("button", { name: "Register beneficiary" }).click();
  await expect(page).toHaveURL(/\/beneficiaries\/[^/]+$/);

  await page.goto("/data-quality");
  await page.getByRole("tab", { name: "Quality Issues" }).click();

  await expect(page.getByText("Data quality score")).toBeVisible();
  await expect(page.getByText(fullName).first()).toBeVisible();
});
