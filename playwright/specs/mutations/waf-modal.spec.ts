/**
 * WAF activation modal: the "Activate Your Firewall API Key" prompt that the
 * dashboard renders ONLY in the freemium state (no WAF key) when the dismiss
 * cookie is not yet '1'.
 *
 * The whole gate is server-side (template.lib.php:128-148): the markup is
 * emitted only when the page is the dashboard AND no WAF key is stored AND
 * `sucuriscan_waf_dismissed` !== '1'. So:
 *   - freemium baseline is mandatory -> beforeEach deletes the key + its
 *     encrypted twins so isPremium()/getKey() is false;
 *   - the admin storageState carries sucuriscan_waf_dismissed=1 (the Cypress
 *     login seeded it), so we override the cookie to '0' on the context BEFORE
 *     the first navigation, otherwise the modal would never render;
 *   - once the CTA sets the cookie to '1', the markup is simply absent on the
 *     next server render — assert toHaveCount(0) after navigation (the node is
 *     removed from the DOM, not hidden), no reload needed.
 *
 * Lives in mutations/ because it depends on the global isPremium() state (the
 * shared sucuriscan_cloudproxy_apikey option) and must run freemium.
 */
import { test, expect } from "@playwright/test";
import { deleteOption } from "../../support/wp-cli";
import { BASE_URL } from "../../support/env";

const DASHBOARD_URL = "/wp-admin/admin.php?page=sucuriscan";

test.describe("WAF activation modal", () => {
  test.beforeEach(() => {
    // Force the freemium baseline: with any of these set, getKey() returns a
    // value, isPremium() is true, and the modal is never rendered.
    deleteOption("sucuriscan_cloudproxy_apikey");
    deleteOption("sucuriscan_secret_cloudproxy_apikey_enc");
    deleteOption("sucuriscan_secret_cloudproxy_apikey");
  });

  test("appears only on Dashboard, dismisses once, and CTA navigates to WAF", async ({
    page,
  }) => {
    const modal = page.getByTestId("sucuriscan-modal-container");

    // The admin storageState seeds sucuriscan_waf_dismissed=1; reset it to '0'
    // on the context so the very first server render shows the modal. This
    // replaces the Cypress double-setCookie ordering hack (cy source 110/113).
    await page.context().addCookies([
      {
        name: "sucuriscan_waf_dismissed",
        value: "0",
        // Provide `url` alone: Playwright derives the host + "/" path from it and
        // rejects a url+path pair ("Cookie should have either url or path").
        url: BASE_URL,
      },
    ]);

    await page.goto(DASHBOARD_URL);
    await expect(modal).toBeVisible();

    // CTA navigates to the Firewall page and (via base.html.tpl JS) writes the
    // dismiss cookie to '1'.
    await page.getByTestId("sucuriscan-waf-modal-main-action").click();
    await expect(page).toHaveURL(/page=sucuriscan_firewall/);

    // Modal is dashboard-only AND now dismissed: it must be absent everywhere.
    // Each goto is a fresh server render that re-evaluates the gate, so no
    // reload is needed; the markup is removed from the DOM, not hidden.
    await page.goto("/wp-admin/admin.php?page=sucuriscan_2fa");
    await expect(modal).toHaveCount(0);

    await page.goto("/wp-admin/admin.php?page=sucuriscan_firewall");
    await expect(modal).toHaveCount(0);

    await page.goto("/wp-admin/admin.php?page=sucuriscan_settings");
    await expect(modal).toHaveCount(0);

    // Back on the dashboard: dismissed cookie keeps it suppressed.
    await page.goto(DASHBOARD_URL);
    await expect(modal).toHaveCount(0);
  });
});
