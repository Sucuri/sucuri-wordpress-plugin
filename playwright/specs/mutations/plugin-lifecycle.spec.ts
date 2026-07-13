/**
 * Plugin lifecycle: deactivate then re-activate sucuri-scanner from the WP core
 * plugins list (wp-admin/plugins.php).
 *
 * GLOBAL-DESTRUCTIVE: deactivating the plugin removes every sucuriscan admin
 * page, so leaving it off would break the rest of the suite. The two steps are
 * therefore one atomic test. Cleanup force-reactivates the plugin and repairs
 * its scheduled scan even if the browser flow fails halfway through.
 */
import { test, expect } from "../../support/fixtures";
import {
  restoreCron,
  snapshotCron,
  wp,
  type CronSnapshot,
} from "../../support/wp-cli";
import { PLUGIN_SLUG } from "../../support/env";

const PLUGINS_URL = "/wp-admin/plugins.php";
const ROW = `[data-slug="${PLUGIN_SLUG}"]`;

test.describe("Plugin lifecycle", () => {
  let scheduledScan: CronSnapshot[];

  test.beforeEach(() => {
    scheduledScan = snapshotCron("sucuriscan_scheduled_scan");
    // The deactivate link only renders while the plugin is active.
    wp("plugin", "activate", PLUGIN_SLUG);
  });

  test.afterEach(() => {
    // Always leave the plugin enabled — a deactivated plugin removes all sucuri
    // admin pages and breaks every other spec, so re-activate even on failure.
    wp("plugin", "activate", PLUGIN_SLUG);
    restoreCron("sucuriscan_scheduled_scan", scheduledScan);
  });

  test("can deactivate and activate sucuri-scanner", async ({ page }) => {
    await page.goto(PLUGINS_URL);
    await page.locator(`${ROW} .deactivate`).click();
    await expect(page.getByText("Plugin deactivated.")).toBeVisible();
    await page.locator(`${ROW} .activate`).click();
    await expect(page.getByText("Plugin activated.")).toBeVisible();
  });
});
