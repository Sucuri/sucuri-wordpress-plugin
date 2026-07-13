/**
 * Settings · Website Info tab (#webinfo): read-only checks on the
 * Environment Variables table and the Access File Integrity (.htaccess) panel.
 *
 * Pure read; no state is mutated and nothing needs cleanup. The page is
 * order-independent, so this describe runs in the default parallel mode.
 */
import { test, expect } from "../../support/fixtures";
import {
  restoreWpFiles,
  snapshotWpFiles,
  wpEval,
  type FileSnapshot,
} from "../../support/wp-cli";

const WEBINFO_URL = "/wp-admin/admin.php?page=sucuriscan_settings#webinfo";

test.describe("Settings · Website Info", () => {
  let htaccess: FileSnapshot;

  test.beforeAll(() => {
    htaccess = snapshotWpFiles([".htaccess"]);
  });

  test.beforeEach(() => {
    wpEval('touch(ABSPATH.".htaccess");');
  });

  test.afterAll(() => {
    restoreWpFiles(htaccess);
  });

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

    const accessPanel = page.getByTestId("sucuriscan_access_file_integrity");
    await expect(
      accessPanel.locator(".sucuriscan-inline-alert-success"),
    ).toBeVisible();
    await expect(
      accessPanel.locator(".sucuriscan-inline-alert-success"),
    ).toContainText("Htaccess file found in /var/www/html/.htaccess");
    await expect(
      accessPanel.locator(".sucuriscan-inline-alert-error"),
    ).toBeHidden();
  });
});
