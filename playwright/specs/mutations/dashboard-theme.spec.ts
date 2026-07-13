/**
 * Dashboard Light/Dark theme gating (freemium vs premium UI).
 *
 * The dashboard renders a different "theme" depending on whether a valid WAF
 * key is stored (isPremium()): freemium shows the Unlock-Premium link + upgrade
 * banner and hides the vulnerability panels and plugin/theme lists; premium
 * hides the freemium markers and reveals the vulnerability panels + the two
 * plugin/theme list-body wrappers.
 *
 * isPremium() is GLOBAL (single shared `sucuriscan_cloudproxy_apikey` option),
 * so the two tests are mutually interfering and run serial: Light (freemium)
 * first against a clean baseline, Dark (premium) second after saving a key.
 * The Dark test MUST restore the freemium baseline afterward (delete the key +
 * reset revproxy/addr-header side-effects) so the WAF-modal / Light-Theme tests
 * and any other isPremium-sensitive spec keep passing.
 */
import { test, expect } from "@playwright/test";
import { deleteOption, updateOption, wp } from "../../support/wp-cli";
import { adminUser } from "../../support/env";

const DASHBOARD_URL = "/wp-admin/admin.php?page=sucuriscan";
const FIREWALL_URL = "/wp-admin/admin.php?page=sucuriscan_firewall";

// Valid-FORMAT only (passes isValidKey ^([a-z0-9]{32})/([a-z0-9]{32})$); it never
// reaches the live Sucuri API, so it flips isPremium() without a real account.
const FAKE_API_KEY =
  "abcdefghiabcegasabcdefghiabcegas/abcdefghiabcegasabcdefghiabcegas";

/** Delete the stored WAF key (all storage variants) and reverse its proxy side-effects. */
function restoreFreemiumBaseline(): void {
  deleteOption("sucuriscan_cloudproxy_apikey");
  deleteOption("sucuriscan_secret_cloudproxy_apikey_enc");
  deleteOption("sucuriscan_secret_cloudproxy_apikey");
  // Saving a key calls setRevProxy('enable') + setAddrHeader('HTTP_X_SUCURI_CLIENTIP');
  // reverse both so the freemium baseline (used by waf-modal and others) is clean.
  updateOption("sucuriscan_revproxy", "disabled");
  updateOption("sucuriscan_addr_header", "REMOTE_ADDR");
  try {
    wp(
      "user",
      "meta",
      "delete",
      adminUser.login,
      "sucuriscan_preferred_theme",
    );
  } catch {
    // Absence is the desired baseline.
  }
}

async function stubExternalAjax(page: import("@playwright/test").Page): Promise<void> {
  await page.route("**/admin-ajax.php**", async (route) => {
    const body = route.request().postData() ?? "";
    if (body.includes("firewall_settings")) {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ ok: true, settings: {} }),
      });
    }
    if (body.includes("vulnerabilities_scan_core_php")) {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ success: false, data: "Could not fetch data" }),
      });
    }
    return route.fallback();
  });
}

test.describe.configure({ mode: "serial" });

test.describe("Dashboard theme gating", () => {
  test.afterEach(() => {
    // Always return to freemium regardless of which test ran/failed.
    restoreFreemiumBaseline();
  });

  test("Test Light Theme", async ({ page }) => {
    // Freemium baseline: no valid WAF key stored.
    restoreFreemiumBaseline();

    await page.goto(DASHBOARD_URL);

    await expect(page.locator(".unlock-premium")).toBeVisible();
    await expect(page.locator(".sucuriscan-upgrade-banner")).toBeVisible();

    await expect(page.locator("#core-vulnerability-results")).not.toBeVisible();
    await expect(page.locator("#php-vulnerability-results")).not.toBeVisible();
    // Both list bodies (Plugins + Themes) sit inside the PremiumVisibility wrapper,
    // which is display:none in freemium — present in the DOM but hidden. Mirror the
    // Cypress `should('not.be.visible')` over the matched set: strict mode forbids
    // not.toBeVisible() on a multi-element locator, so assert the count and check each.
    const themeBodies = page.locator(".sucuriscan-themes-list-body");
    await expect(themeBodies).toHaveCount(2);
    await expect(themeBodies.first()).not.toBeVisible();
    await expect(themeBodies.last()).not.toBeVisible();
  });

  test("Test Dark Theme", async ({ page }) => {
    await stubExternalAjax(page);
    await page.goto(FIREWALL_URL);

    // If a key is already stored the entry form is hidden behind an "Update"
    // click; reveal it before typing so the test is safe on a dirty environment.
    const form = page.locator("#sucuriscan-waf-key-form");
    if (
      ((await form.getAttribute("class")) ?? "").includes("sucuriscan-hidden")
    ) {
      // The "Update" entry is an <option> inside a hover-revealed custom dropdown
      // (firewall-settings.html.tpl:114-118) with a jQuery click handler bound to
      // it. Playwright can't .click() a hidden <option>; dispatchEvent fires the
      // handler directly — the faithful equivalent of Cypress's synthetic click.
      await page
        .locator('#sucuriscan-waf-key-options option[value="update"]')
        .dispatchEvent("click");
    }

    await page
      .locator('input[name="sucuriscan_cloudproxy_apikey"]')
      .fill(FAKE_API_KEY);
    await page.getByTestId("sucuriscan-save-wafkey").click();
    // Confirm the key was actually saved before navigating away. Saving a key also
    // flips the reverse-proxy + addr-header settings, so THREE success alerts render;
    // filter to the key one (Cypress's .contains() picked the matching node, Playwright
    // strict mode forbids toContainText over a multi-element locator).
    await expect(
      page
        .locator(".sucuriscan-alert-updated")
        .filter({ hasText: "Firewall API key was successfully saved" }),
    ).toBeVisible();

    // Stub the vulnerability-scan AJAX to fail immediately, avoiding a ~60s wait
    // for two sequential 30s external-API timeouts. Register BEFORE visiting the
    // dashboard so the background scan request is intercepted on first render.
    await page.goto(DASHBOARD_URL);

    await expect(page.locator(".unlock-premium")).not.toBeVisible();
    await expect(page.locator(".sucuriscan-upgrade-banner")).not.toBeVisible();

    // The API returns no vulnerability info because the (fake) key is invalid.
    await expect(page.locator("#core-vulnerability-results")).toContainText(
      "Error: Could not fetch WordPress Core vulnerabilities.",
    );
    await expect(page.locator("#php-vulnerability-results")).toContainText(
      "Error: Could not fetch PHP vulnerabilities.",
    );
    // Structural: the two wrapper bodies (Plugins + Themes), only present in premium.
    await expect(page.locator(".sucuriscan-themes-list-body")).toHaveCount(2);
  });

  test("toggles and persists the premium dashboard theme", async ({ page }) => {
    restoreFreemiumBaseline();
    await stubExternalAjax(page);
    await page.goto(FIREWALL_URL);
    await page
      .locator('input[name="sucuriscan_cloudproxy_apikey"]')
      .fill(FAKE_API_KEY);
    await page.getByTestId("sucuriscan-save-wafkey").click();
    await expect(
      page
        .locator(".sucuriscan-alert-updated")
        .filter({ hasText: "Firewall API key was successfully saved" }),
    ).toBeVisible();

    await page.goto(DASHBOARD_URL);
    const toggle = page.locator("#sucuriscan-toggle-theme");
    await expect(toggle).toHaveAttribute("data-theme", "light");
    await Promise.all([
      page.waitForResponse(
        (response) =>
          response.url().includes("admin-ajax.php") &&
          (response.request().postData() ?? "").includes("toggle_theme"),
      ),
      toggle.click(),
    ]);
    await expect(toggle).toHaveAttribute("data-theme", "dark");

    await page.reload();
    await expect(page.locator("#sucuriscan-toggle-theme")).toHaveAttribute(
      "data-theme",
      "dark",
    );
    await expect(
      page.locator('link[href*="/inc/css/dark.css"]'),
    ).toBeAttached();
  });
});
