import { expect, test } from "@playwright/test";
import { loginAs, TEST_USERS } from "./support/auth";

/**
 * Mirrors docs/implementation-plan.md §6 demo scenario 4: Admin uploads an
 * Excel/CSV beneficiary list → column mapping → validation/duplicate report
 * → commit → data quality dashboard updates.
 *
 * The CSV is generated in-memory (not a static checked-in fixture) with a
 * per-run unique name+phone: the E2E database persists across runs (unlike
 * Pest's RefreshDatabase), so a fixed fixture would accumulate cross-run
 * duplicates and make the "exactly one duplicate" assertion unreliable.
 */
test("importing a CSV with a duplicate row surfaces it in preview and commits the rest", async ({ page }) => {
  const uniqueId = Date.now();
  const duplicateName = `E2E Import Duplicate ${uniqueId}`;
  const duplicatePhone = "01733334444";
  const newName = `E2E Import New ${uniqueId}`;

  await loginAs(page, TEST_USERS.fieldOfficer);

  // Register the "existing" beneficiary the CSV's first row will duplicate.
  await page.goto("/beneficiaries");
  await page.getByRole("button", { name: "Register beneficiary" }).click();
  await page.getByLabel("Full name").fill(duplicateName);
  await page.getByLabel("Phone", { exact: true }).fill(duplicatePhone);
  await page.getByLabel("Registration date").fill(new Date().toISOString().slice(0, 10));
  await page.getByRole("button", { name: "Register beneficiary" }).click();
  await expect(page).toHaveURL(/\/beneficiaries\/[^/]+$/);

  const today = new Date().toISOString().slice(0, 10);
  const csv = [
    "Name,Phone,RegDate",
    `${duplicateName},${duplicatePhone},${today}`,
    `${newName},01799998888,${today}`,
  ].join("\n");

  await page.goto("/data-quality");
  await page.getByRole("button", { name: "New import" }).click();
  await page.locator("#import-file").setInputFiles({
    name: "beneficiaries.csv",
    mimeType: "text/csv",
    buffer: Buffer.from(csv),
  });
  await page.getByRole("button", { name: "Upload" }).click();

  await expect(page).toHaveURL(/\/data-quality\/imports\/[^/]+$/);

  async function mapColumn(fieldLabel: string, header: string) {
    // Anchored regex, not a substring match: "Phone" must not also match
    // "Emergency contact phone"'s label.
    const label = page.locator("label").filter({ hasText: new RegExp(`^${fieldLabel}\\s*\\*?$`) });
    const row = label.locator("xpath=..");
    await row.getByRole("combobox").click();
    await page.getByRole("option", { name: header, exact: true }).click();
  }

  await mapColumn("Full name", "Name");
  await mapColumn("Registration date", "RegDate");
  await mapColumn("Phone", "Phone");

  await page.getByRole("button", { name: "Save mapping" }).click();
  await page.getByRole("button", { name: "Run preview" }).click();

  // "Duplicate" also labels a summary card above the table, mounted at the
  // same time — scope to the table's row badge to avoid a strict-mode
  // ambiguity between the two.
  await expect(page.getByRole("table").getByText("Duplicate", { exact: true })).toBeVisible();
  // 1 clean row + 1 duplicate row = commit button should offer 2.
  await expect(page.getByRole("button", { name: /^Commit \(2 beneficiaries\)$/ })).toBeVisible();

  await page.getByRole("button", { name: /^Commit/ }).click();
  await expect(page.getByText(/Import committed — 2 beneficiaries created\./)).toBeVisible({ timeout: 20000 });
});
