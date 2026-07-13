/**
 * Plugin lifecycle: deactivate then re-activate sucuri-scanner from the WP core
 * plugins list (wp-admin/plugins.php).
 *
 * GLOBAL-DESTRUCTIVE: deactivating the plugin removes every sucuriscan admin
 * page, so leaving it off would break the rest of the suite. The two steps are
 * therefore serial and strictly ordered (deactivate -> activate): the `.activate`
 * link only exists while inactive and `.deactivate` only while active, so order
 * matters. beforeAll pins the plugin ACTIVE (so the deactivate link is present)
 * and afterAll force-reactivates it via wp-cli even if a test failed.
 */
import { test, expect } from "@playwright/test";
import { wp } from "../../support/wp-cli";
import { PLUGIN_SLUG } from "../../support/env";

const PLUGINS_URL = "/wp-admin/plugins.php";
const ROW = `[data-slug="${PLUGIN_SLUG}"]`;

test.describe.configure({ mode: "serial" });

test.describe("Plugin lifecycle", () => {
  test.beforeAll(() => {
    // The deactivate link only renders while the plugin is active.
    wp("plugin", "activate", PLUGIN_SLUG);
  });

  test.afterAll(() => {
    // Always leave the plugin enabled — a deactivated plugin removes all sucuri
    // admin pages and breaks every other spec, so re-activate even on failure.
    wp("plugin", "activate", PLUGIN_SLUG);
  });

  test("can deactivate sucuri-scanner", async ({ page }) => {
    await page.goto(PLUGINS_URL);
    await page.locator(`${ROW} .deactivate`).click();
    await expect(page.getByText("Plugin deactivated.")).toBeVisible();
  });

  test("can activate sucuri-scanner", async ({ page }) => {
    await page.goto(PLUGINS_URL);
    await page.locator(`${ROW} .activate`).click();
    await expect(page.getByText("Plugin activated.")).toBeVisible();
  });
});
