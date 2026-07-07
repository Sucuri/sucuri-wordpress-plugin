/**
 * Firewall (WAF) API-key lifecycle — GATED on a real Sucuri WAF API key.
 *
 * Every test in this file talks to the LIVE Sucuri WAF API (https://sucuri.net),
 * so the whole suite skips itself unless `WAF_API_KEY` is set (see support/env.ts).
 * When the key is present the tests run serially as one lifecycle:
 *   activate key -> load audit logs -> blocklist an IP -> clear cache (global +
 *   by path) -> delete key. The delete step is the natural teardown (leaves the
 *   key absent); `beforeAll`/`afterAll` additionally pin/restore options so the
 *   activation form is visible on a clean re-run.
 *
 * Deviations from the original Cypress source (cypress/integration/
 * sucuri-scanner-firewall.js), verified against the current templates:
 *  - Delete is driven through the Options dropdown
 *    (`#sucuriscan-waf-key-options option[value="delete"]`); the old
 *    `button[data-cy=sucuriscan-delete-wafkey]` selector no longer exists in any
 *    template (firewall-settings.html.tpl:81-85 + hidden input line 109).
 *  - "Clear cache by path" asserts on `#firewall-page-clear-cache-response`
 *    (firewall-clearcache.html.tpl:48 — where the per-path handler actually
 *    writes), not `#firewall-clear-cache-response` which the stale Cypress
 *    selector targeted.
 *  - All `cy.wait(ms)` hard sleeps are replaced with `page.waitForResponse` on
 *    the relevant admin-ajax `form_action` plus web-first text assertions.
 */
import { test, expect } from "@playwright/test";
import type { Page } from "@playwright/test";
import { WAF_API_KEY } from "../../support/env";
import { deleteOption, updateOption, wpEval } from "../../support/wp-cli";

// The whole suite requires a real, valid WAF key and exercises the live API.
test.skip(!WAF_API_KEY, "requires a real Sucuri WAF API key (set WAF_API_KEY)");

// Tests form a single ordered lifecycle (save -> use -> delete) against one
// shared WordPress instance and one live WAF account.
test.describe.configure({ mode: "serial" });

const SETTINGS_URL = "/wp-admin/admin.php?page=sucuriscan_firewall#settings";
const AUDITLOGS_URL = "/wp-admin/admin.php?page=sucuriscan_firewall#auditlogs";
const IPACCESS_URL = "/wp-admin/admin.php?page=sucuriscan_firewall#ipaccess";
const CLEARCACHE_URL =
  "/wp-admin/admin.php?page=sucuriscan_firewall#clearcache";

// Known attacker IP used by the original blocklist test.
const ATTACKER_IP = "82.165.185.18";

/** True when a WAF key currently lives in the encrypted DB option. */
function wafKeyPresent(): boolean {
  // getOption(':cloudproxy_apikey') returns the decrypted key or '' when absent.
  return (
    wpEval('echo SucuriScanOption::getOption(":cloudproxy_apikey");').trim()
      .length > 0
  );
}

/** Remove any stored WAF key + reset the paired proxy/header options to baseline. */
function removeWafKey(): void {
  if (wafKeyPresent()) {
    wpEval('SucuriScanOption::deleteOption(":cloudproxy_apikey");');
  }
  deleteOption("sucuriscan_secret_cloudproxy_apikey_enc");
  deleteOption("sucuriscan_secret_cloudproxy_apikey");
  updateOption("sucuriscan_revproxy", "disabled");
  updateOption("sucuriscan_addr_header", "REMOTE_ADDR");
}

test.describe("Firewall · WAF API key (live)", () => {
  test.beforeAll(() => {
    // Start with no key so the activation form (and its input) is visible:
    // a present key hides input[name=sucuriscan_cloudproxy_apikey].
    removeWafKey();
  });

  test.afterAll(() => {
    // Best-effort cleanup so re-runs start clean even if the delete test failed.
    removeWafKey();
  });

  test("can activate api key", async ({ page }) => {
    await page.goto(SETTINGS_URL);

    await expect(
      page.getByRole("heading", { name: "Firewall Settings" }),
    ).toBeVisible();

    await page
      .locator('input[name="sucuriscan_cloudproxy_apikey"]')
      .fill(WAF_API_KEY);
    await page.getByTestId("sucuriscan-save-wafkey").click();

    // Three independent success notices render as separate divs; assert each on
    // its own so Playwright auto-retries while the page settles.
    const notices = page.locator(".sucuriscan-alert-updated");
    await expect(notices).toContainText(
      "SUCURI: Firewall API key was successfully saved",
    );
    await expect(notices).toContainText(
      "SUCURI: Reverse proxy support was set to enabled",
    );
    await expect(notices).toContainText(
      "SUCURI: HTTP header was set to HTTP_X_SUCURI_CLIENTIP",
    );
  });

  test("can try to load audit logs", async ({ page }) => {
    await page.goto(AUDITLOGS_URL);

    // The audit-logs AJAX auto-fires on load (firewall-auditlogs.html.tpl:41),
    // showing "Loading..." first. We either see the empty-state message or at
    // least one entry exposing a Target field; toContainText auto-waits past the
    // interim Loading... state.
    await expect(
      page.locator("table.sucuriscan-firewall-auditlogs"),
    ).toContainText(/no data available\.|Target:/);
  });

  test("can try to add ip address to the blocklist", async ({ page }) => {
    await page.goto(IPACCESS_URL);

    await page.getByTestId("sucuriscan_ip_access_input").fill(ATTACKER_IP);

    // Replaces cy.wait(7000): wait for the live firewall_blocklist round-trip.
    await Promise.all([
      page.waitForResponse(
        (r) =>
          r.url().includes("admin-ajax.php") &&
          (r.request().postData() ?? "").includes("firewall_blocklist"),
      ),
      page.getByTestId("sucuriscan_ip_access_submit").click(),
    ]);

    // Response text comes from the live WAF API; we only require the IP to echo
    // back, which proves connectivity regardless of "added"/"already" wording.
    await expect(page.locator("#sucuriscan-ipaccess-response")).toContainText(
      `IP address ${ATTACKER_IP}`,
    );
  });

  test("can clear cache when post/page is updated", async ({ page }) => {
    await page.goto(CLEARCACHE_URL);

    // Enable auto-clear (fires the firewall_auto_clear_cache AJAX on change).
    await page.locator('input[name="sucuriscan_auto_clear_cache"]').check();

    // The GLOBAL clear-cache button lives in the top navbar (base.html.tpl:213)
    // and writes into #firewall-clear-cache-response (base.html.tpl:148).
    // Replaces cy.wait(2000): wait for the firewall_clear_cache round-trip.
    await Promise.all([
      page.waitForResponse(
        (r) =>
          r.url().includes("admin-ajax.php") &&
          (r.request().postData() ?? "").includes("firewall_clear_cache"),
      ),
      page.locator("#firewall-clear-cache-button").click(),
    ]);

    // base.html.tpl:23-26 wipes this response 5s later — assert immediately.
    await expect(page.locator("#firewall-clear-cache-response")).toContainText(
      /The cache for the domain ".*" is being cleared\. Note that it may take up to two minutes for it to be fully flushed\./,
    );
  });

  test("can clear cache by path", async ({ page }) => {
    await page.goto(CLEARCACHE_URL);

    await page.getByTestId("firewall-clear-cache-path-input").fill("blog");

    // The submit button is disabled until the input has length > 0
    // (firewall-clearcache.html.tpl:68-73).
    const submit = page.getByTestId("sucuriscan-clear-cache-path");
    await expect(submit).toBeEnabled();

    // Replaces cy.wait(2000): the per-path handler also POSTs firewall_clear_cache,
    // but with a `path` param — match on it to avoid racing the global button.
    await Promise.all([
      page.waitForResponse(
        (r) =>
          r.url().includes("admin-ajax.php") &&
          (r.request().postData() ?? "").includes("firewall_clear_cache") &&
          (r.request().postData() ?? "").includes("path"),
      ),
      submit.click(),
    ]);

    // DEVIATION: the per-path handler writes its result into
    // #firewall-page-clear-cache-response (firewall-clearcache.html.tpl:48), NOT
    // #firewall-clear-cache-response as the stale Cypress selector assumed.
    await expect(
      page.locator("#firewall-page-clear-cache-response"),
    ).toContainText(
      /The cache for ".*" is being cleared\. Note that it may take up to two minutes for it to be fully flushed\./,
    );
  });

  test("can delete api key", async ({ page }) => {
    await page.goto(SETTINGS_URL);

    // DEVIATION: there is no button[data-cy=sucuriscan-delete-wafkey]. Deletion
    // is driven through the Options dropdown <option value="delete"> which sets
    // sucuriscan_delete_wafkey=1 and submits the form
    // (firewall-settings.html.tpl:81-85). The <option> is bound via a jQuery
    // click handler inside a custom dropdown, so dispatch the click directly.
    await deleteWafKeyViaDropdown(page);

    const notices = page.locator(".sucuriscan-alert-updated");
    await expect(notices).toContainText(
      "SUCURI: Firewall API key was successfully removed",
    );
    await expect(notices).toContainText(
      "SUCURI: Reverse proxy support was set to disabled",
    );
    await expect(notices).toContainText(
      "SUCURI: HTTP header was set to REMOTE_ADDR",
    );
  });
});

/** Trigger the delete-key flow via the non-standard Options dropdown <option>. */
async function deleteWafKeyViaDropdown(page: Page): Promise<void> {
  const deleteOptionEl = page.locator(
    '#sucuriscan-waf-key-options option[value="delete"]',
  );
  await expect(deleteOptionEl).toBeAttached();
  await deleteOptionEl.dispatchEvent("click");
}
