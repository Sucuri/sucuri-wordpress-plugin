/**
 * Post-hack actions · Update WordPress secret keys + Automatic Secret Keys
 * Updater schedule. Page: /wp-admin/admin.php?page=sucuriscan_post_hack_actions.
 *
 * AUTH-DESTRUCTIVE: SucuriScanEvent::setNewConfigKeys() rewrites the
 * AUTH_KEY/SALT constants in wp-config.php, invalidating EVERY active session —
 * including the shared admin storageState this project inherits. So the test:
 *   1. rotates the keys (still authenticated at this point),
 *   2. re-authenticates via the UI in the same context (login() does a fresh
 *      wp-login.php round-trip, replacing the now-stale cookies),
 *   3. exercises the auto-updater (Disabled -> Quarterly -> Disabled).
 *
 * afterAll regenerates the global admin storageState because the rotation left
 * the saved artifact stale for every later spec/run, and re-pins the
 * auto-updater cron to Disabled so re-runs start from a known state.
 */
import { test, expect } from "@playwright/test";
import { login, saveAdminStorageState } from "../../support/auth";
import { adminUser } from "../../support/env";
import { wpEval } from "../../support/wp-cli";

const POST_HACK_URL = "/wp-admin/admin.php?page=sucuriscan_post_hack_actions";

// The single test mutates auth + cron in a strict sequence; keep it serial.
test.describe.configure({ mode: "serial" });

test.afterAll(async ({ browser }) => {
  // Defensively clear the auto-updater cron so the auto-updater starts Disabled
  // on the next run regardless of where the test left off.
  wpEval("wp_clear_scheduled_hook('sucuriscan_autoseckeyupdater');");
  // The key rotation invalidated the shared admin storageState; rebuild it so
  // every later spec (and the next run) re-reads valid admin cookies.
  await saveAdminStorageState(browser);
});

test("can update the secret keys", async ({ page }) => {
  await page.goto(POST_HACK_URL);

  // Confirm-risk checkbox + generate. This rewrites wp-config.php and
  // invalidates the current session.
  await page.getByTestId("sucuriscan_security_keys_checkbox").check();
  await page.getByTestId("sucuriscan_security_keys_submit").click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Secret keys updated successfully (summary of the operation bellow).",
  );

  // Re-authenticate: the rotation invalidated our cookies, so reloading the
  // post-hack page would bounce to wp-login. login() navigates to wp-login.php
  // and submits fresh credentials, replacing the stale session in this context.
  await login(page, adminUser);
  await page.goto(POST_HACK_URL);

  // Auto-updater defaults to Disabled. The badge text uses a literal em-dash
  // (U+2014, from &mdash; in security-keys.html.tpl:47).
  const autoupdater = page.getByTestId("sucuriscan_security_keys_autoupdater");
  await expect(autoupdater).toContainText(
    "Automatic Secret Keys Updater — Disabled",
  );

  // Enable on a Quarterly schedule.
  await page
    .getByTestId("sucuriscan_security_keys_autoupdater_select")
    .selectOption({ label: "Quarterly" });
  await page.getByTestId("sucuriscan_security_keys_autoupdater_submit").click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Automatic Secret Keys Updater enabled.",
  );
  await expect(autoupdater).toContainText(
    "Automatic Secret Keys Updater — Enabled",
  );

  // Disable again — leaves the cron in its original (cleared) state.
  await page
    .getByTestId("sucuriscan_security_keys_autoupdater_select")
    .selectOption({ label: "Disabled" });
  await page.getByTestId("sucuriscan_security_keys_autoupdater_submit").click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Automatic Secret Keys Updater disabled.",
  );
  await expect(autoupdater).toContainText(
    "Automatic Secret Keys Updater — Disabled",
  );
});

// Ported from cypress it.skip "can reset installed plugins" (sucuri-scanner.cy.js
// 738-751). Kept skipped: it re-downloads and reinstalls akismet from
// api.wordpress.org (live-network dependent, slow) and mutates the plugins
// filesystem (global-destructive), so it is unsafe/non-deterministic in CI.
test.skip("can reset installed plugins", async ({ page }) => {
  await page.goto(
    "/wp-admin/admin.php?page=sucuriscan_settings&sucuriscan_lastlogin=1#posthack",
  );

  await page.locator('input[value="akismet/akismet.php"]').check();
  await page.getByTestId("sucuriscan_reset_plugins_submit").click();

  const response = page.getByTestId("sucuriscan_reset_plugin_response");
  await expect(response).toContainText("Loading");
  // The original waited 2s for the reinstall; a real port would waitForResponse
  // on the reset_plugin AJAX with a long timeout.
  await expect(response).toContainText("Installed");
});
