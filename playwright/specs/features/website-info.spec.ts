/**
 * Settings · Website Info tab (#webinfo): read-only checks on the
 * Environment Variables table and the Access File Integrity (.htaccess) panel.
 *
 * Pure read; no state is mutated and nothing needs cleanup. The page is
 * order-independent, so this describe runs in the default parallel mode.
 */
import { test, expect } from "@playwright/test";

const WEBINFO_URL = "/wp-admin/admin.php?page=sucuriscan_settings#webinfo";

test.describe("Settings · Website Info", () => {
  test("loads the environment variables and access-file-integrity panels", async ({
    page,
  }) => {
    await page.goto(WEBINFO_URL);

    // Environment Variables: the ABSPATH row renders its label in the first
    // cell and the resolved constant in the last cell. In wp-env ABSPATH is
    // the default docroot, /var/www/html/.
    const abspathRow = page.getByTestId("ABSPATH");
    await expect(abspathRow.locator("td").first()).toContainText("ABSPATH");
    await expect(abspathRow.locator("td").last()).toContainText(
      "/var/www/html/",
    );

    // Access File Integrity: assert the "no .htaccess" message on the whole
    // panel. tests/e2e-prepare.sh touches an EMPTY /var/www/html/.htaccess, so
    // getHtaccessPath() (file_exists-based, src/base.lib.php:374) reports the
    // file as found and the plugin actually toggles the "found" branch visible.
    // The panel template (inc/tpl/settings-webinfo-htaccess.html.tpl) still
    // renders the not-found <p> in the DOM (merely display:none via
    // .sucuriscan-hidden), and toContainText reads textContent including hidden
    // descendants — matching the original Cypress .contains() behaviour. So the
    // assertion below holds regardless of which branch is visually shown.
    await expect(
      page.getByTestId("sucuriscan_access_file_integrity"),
    ).toContainText(
      "Your website has no .htaccess file or it was not found in the default location.",
    );
  });
});
