/**
 * Hardening · Allow Blocked PHP Files (allowlist add / remove).
 *
 * Exercises the prevention page's allowlist form: adding a blocked PHP file
 * (flips Apache 403 -> 200), rejecting duplicates, removing legacy
 * wildcard (".*") rules, the full 403<->200 add/remove round-trip, and multi-file
 * add + Select-All removal.
 *
 * SHARED STATE / IDEMPOTENCY: every test mutates wp-content/.htaccess or
 * wp-includes/.htaccess (and Select-All clears all allowlisted folders), so the
 * whole file runs serially. The legacy-removal test PERMANENTLY drops the
 * `<Files archive-legacy.php>` grant, flipping that file to 403 forever; a
 * beforeAll re-seed (tests/e2e-seed-hardening.sh) restores the seeded baseline
 * so the 200 precondition holds on every run, and an afterAll re-seed wipes the
 * allowlist rules left behind by the add tests.
 */
import { test, expect } from "../../support/fixtures";
import type { APIRequestContext, Page } from "@playwright/test";
import { expectForbidden, expectHelloWorld } from "../../support/http";
import { runPluginScript } from "../../support/wp-cli";

const HARDENING_URL =
  "/wp-admin/admin.php?page=sucuriscan_hardening_prevention";

// Absolute container paths rendered from WP_CONTENT_DIR / ABSPATH+WPINC; the
// <select> option values are these exact strings (settings-hardening.php:785-789).
const WP_CONTENT = "/var/www/html/wp-content";
const WP_INCLUDES = "/var/www/html/wp-includes";

// The add/remove tests share wp-content/.htaccess and the allowlist table; the
// multi-file Select-All clears every folder, so order matters — run serially.
test.describe.configure({ mode: "serial" });

/**
 * Restore the seeded hardening fixtures: recreates archive.php (403),
 * archive-legacy.php + its legacy <Files> grant (200), wp-includes/test-1/*.php,
 * and the deny-all .htaccess baselines (which also clears any leftover allowlist
 * rules). Idempotent and the key to re-runnability after the legacy-removal test.
 */
function reseedHardening(): void {
  runPluginScript("tests/e2e-seed-hardening.sh");
}

/** Add one relative PHP file under `folder` to the allowlist and assert success. */
async function addAllowlistFile(
  page: Page,
  file: string,
  folder: string,
): Promise<void> {
  await page.getByTestId("sucuriscan_hardening_allowlist_input").fill(file);
  await page
    .getByTestId("sucuriscan_hardening_allowlist_select")
    .selectOption(folder);
  await page.getByTestId("sucuriscan_hardening_allowlist_submit").click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "The file has been allowed",
  );
}

/**
 * Poll the public URL until Apache reflects the just-written .htaccess rule, then
 * run the body assertion. Absorbs the brief lag between the form POST and Apache
 * serving the new rule (replaces any implicit Cypress retry / cy.wait).
 */
async function expectPublicStatus(
  request: APIRequestContext,
  path: string,
  status: number,
  assertBody: (request: APIRequestContext, path: string) => Promise<void>,
): Promise<void> {
  await expect
    .poll(async () =>
      (await request.get(path, { failOnStatusCode: false })).status(),
    )
    .toBe(status);
  await assertBody(request, path);
}

test.beforeAll(() => {
  // Start every run from the seeded baseline so the legacy <Files> grant exists
  // (archive-legacy.php = 200) and archive.php is blocked (403).
  reseedHardening();
});

test.afterAll(() => {
  // Wipe the allowlist rules left in wp-content/.htaccess and wp-includes/.htaccess
  // (testing.php, test-1/test-*.php) by restoring the deny-all baselines.
  reseedHardening();
});

// SKIPPED in the Cypress source (it.skip). Toggles every hardening option on the
// prevention page (firewall premium notice; wpuploads/wpcontent/wpincludes
// apply+revert; fileeditor apply+revert which mutates wp-config.php
// DISALLOW_FILE_EDIT; autoSecretKeyUpdater enable/disable which mutates WP cron).
// Kept skipped: it depends on a premium/firewall state plus filesystem hardening
// (.htaccess + wp-config.php + cron) that is environment-fragile — a crash
// between an apply and its revert leaves persistent state that breaks re-runs.
test.skip("can toggle hardening options", async ({ page }) => {
  await page.goto(HARDENING_URL);

  await page.locator('input[name="sucuriscan_hardening_firewall"]').click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "The firewall is a premium service that you need purchase at - Sucuri Firewall",
  );

  await page.locator('input[name="sucuriscan_hardening_wpuploads"]').click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Hardening applied to the uploads directory",
  );
  await page
    .locator('input[name="sucuriscan_hardening_wpuploads_revert"]')
    .click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Hardening reverted in the uploads directory",
  );

  await page.locator('input[name="sucuriscan_hardening_wpcontent"]').click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Hardening applied to the content directory",
  );
  await page
    .locator('input[name="sucuriscan_hardening_wpcontent_revert"]')
    .click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Hardening reverted in the content directory",
  );

  await page.locator('input[name="sucuriscan_hardening_wpincludes"]').click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Hardening applied to the library directory",
  );
  await page
    .locator('input[name="sucuriscan_hardening_wpincludes_revert"]')
    .click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Hardening reverted in the library directory",
  );

  await page.locator('input[name="sucuriscan_hardening_fileeditor"]').click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Hardening applied to the plugin and theme editor",
  );
  await page
    .locator('input[name="sucuriscan_hardening_fileeditor_revert"]')
    .click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Hardening reverted in the plugin and theme editor",
  );

  await page
    .locator('input[name="sucuriscan_hardening_autoSecretKeyUpdater"]')
    .click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    'Automatic Secret Keys Updater enabled. The default frequency is "Weekly"',
  );
  await page
    .locator('input[name="sucuriscan_hardening_autoSecretKeyUpdater_revert"]')
    .click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Automatic Secret Keys Updater disabled.",
  );
});

test("cannot add the same file twice to the allowlist", async ({ page }) => {
  await page.goto(HARDENING_URL);

  // First add succeeds.
  await addAllowlistFile(page, "test-1/testing.php", WP_INCLUDES);

  // Re-adding the identical file+folder is rejected as a duplicate.
  await page
    .getByTestId("sucuriscan_hardening_allowlist_input")
    .fill("test-1/testing.php");
  await page
    .getByTestId("sucuriscan_hardening_allowlist_select")
    .selectOption(WP_INCLUDES);
  await page.getByTestId("sucuriscan_hardening_allowlist_submit").click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "File is already in the allowlist",
  );
});

test("can remove legacy rules from allow blocked PHP files", async ({
  page,
  loggedOutRequest,
}) => {
  // Seeded legacy <Files archive-legacy.php> grant makes the file reachable (200).
  await expectHelloWorld(loggedOutRequest, "/wp-content/archive-legacy.php");

  await page.goto(HARDENING_URL);

  const table = page.locator("table.sucuriscan-hardening-allowlist-table");

  // The /*/ wildcard in the pattern confirms this is the legacy rule.
  await expect(table).toContainText(
    "/var/www/html/wp-content/.*/archive-legacy.php",
  );

  // Select the archive-legacy.php row and remove it.
  await page
    .getByRole("row")
    .filter({ hasText: "archive-legacy.php" })
    .getByRole("checkbox")
    .check();
  await page
    .getByTestId("sucuriscan_hardening_remove_allowlist_submit")
    .click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Selected files have been removed",
  );

  // The legacy grant is gone -> the file is now blocked (403 Forbidden).
  await expectPublicStatus(
    loggedOutRequest,
    "/wp-content/archive-legacy.php",
    403,
    expectForbidden,
  );
});

test("can add and remove from allowlist of blocked PHP files", async ({
  page,
  loggedOutRequest,
}) => {
  // Blocked by default.
  await expectForbidden(loggedOutRequest, "/wp-content/archive.php");

  await page.goto(HARDENING_URL);
  await addAllowlistFile(page, "archive.php", WP_CONTENT);

  // Now reachable (200 Hello, world!).
  await expectPublicStatus(
    loggedOutRequest,
    "/wp-content/archive.php",
    200,
    expectHelloWorld,
  );

  // Remove it again via the per-row checkbox.
  await page
    .getByRole("row")
    .filter({ hasText: "archive.php" })
    .getByRole("checkbox")
    .check();
  await page
    .getByTestId("sucuriscan_hardening_remove_allowlist_submit")
    .click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Selected files have been removed",
  );

  // Back to blocked (403 Forbidden).
  await expectPublicStatus(
    loggedOutRequest,
    "/wp-content/archive.php",
    403,
    expectForbidden,
  );
});

test("Can add and remove multiple files from the allowlist of blocked PHP files", async ({
  page,
}) => {
  await page.goto(HARDENING_URL);

  // Preserve the exact source strings (note the leading slash on the first one;
  // allowlistRule ltrims it, so all three are accepted).
  await addAllowlistFile(page, "/test-1/test-1.php", WP_INCLUDES);
  await addAllowlistFile(page, "test-1/test-2.php", WP_INCLUDES);
  await addAllowlistFile(page, "test-1/test-3.php", WP_INCLUDES);

  // Select-All checks every row checkbox across all folders, then remove.
  await page.getByTestId("sucuriscan_hardening_select_all").check();
  await page
    .getByTestId("sucuriscan_hardening_remove_allowlist_submit")
    .click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Selected files have been removed",
  );
});
