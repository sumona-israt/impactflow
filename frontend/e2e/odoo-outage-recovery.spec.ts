import { execFileSync } from "node:child_process";
import { expect, test } from "@playwright/test";
import { loginAs, TEST_USERS } from "./support/auth";

/**
 * Mirrors docs/implementation-plan.md §6 demo scenario 5: Admin opens the
 * Odoo Integration dashboard → reviews sync logs → simulates an Odoo outage
 * → retries → sync recovers.
 *
 * ODOO_MOCK_FAILURE_RATE is an env var read once per request — there's no
 * running-container way to flip it mid-suite without a restart, so the
 * "outage" is seeded directly as a failed odoo_sync_logs row via `artisan
 * tinker` inside the already-running `laravel` container (the same
 * mock-mode client that handles every other spec's real Odoo syncs then
 * genuinely recovers it on retry — this isn't a stubbed success).
 */
test.beforeAll(() => {
  const php = [
    "$program = \\App\\Models\\Program::create(['name' => 'E2E Odoo Outage " + Date.now() + "']);",
    "\\App\\Models\\OdooSyncLog::create([",
    "'entity_type' => \\App\\Models\\Program::class,",
    "'local_id' => $program->id,",
    "'operation' => 'sync',",
    "'status' => 'failed',",
    "'error_message' => 'Simulated outage for E2E.',",
    "'retry_count' => 5,",
    "'request_time' => now(),",
    "'response_time' => now(),",
    "]);",
    "echo 'seeded '.$program->id;",
  ].join(" ");

  execFileSync("docker", ["compose", "exec", "-T", "laravel", "php", "artisan", "tinker", "--execute", php], {
    cwd: "..",
    encoding: "utf-8",
  });
});

test("a failed Odoo sync can be retried and recovers", async ({ page }) => {
  await loginAs(page, TEST_USERS.management);
  await page.goto("/odoo");

  // base-ui's SelectValue displays the raw stored value (e.g. "all") rather
  // than the matching item's label until the popup has been opened once, so
  // this targets the trigger by role — there's exactly one combobox on this
  // page for the Management role — instead of its unreliable display text.
  const statusSelect = page.getByRole("combobox");
  await statusSelect.click();
  await page.getByRole("option", { name: "Failed", exact: true }).click();

  // The sync log table has no local-id column — "Program" + the "Failed"
  // filter is enough to isolate this row, since every other spec's Program
  // syncs go through a real approval flow and land as "Success".
  const failedRow = page.getByRole("row").filter({ hasText: "Program" }).first();
  await expect(failedRow).toBeVisible();
  await failedRow.getByRole("button", { name: "Retry" }).click();

  // The retry runs on the queue worker, asynchronously, and creates a new
  // sync log row rather than updating the failed one in place — the
  // dashboard doesn't poll, so reload until the fresh row has actually
  // landed instead of a fixed sleep.
  const recoveredRow = page.getByRole("row").filter({ hasText: "Program" }).filter({ hasText: "Success" }).first();
  await expect(async () => {
    await page.reload();
    await statusSelect.click();
    await page.getByRole("option", { name: "Success", exact: true }).click();
    await expect(recoveredRow).toBeVisible({ timeout: 2000 });
  }).toPass({ timeout: 20000, intervals: [2000] });
});
