/**
 * Settings · API Service tab (#apiservice): the API service communication
 * enable/disable toggle and the WordPress Checksums API URL.
 *
 * State pinning: the toggle test asserts the Enable/Disable button label and a
 * paired change notice that depend on the option value at page load. Cypress
 * relied on an earlier in-file "import JSON settings" test leaving
 * sucuriscan_api_service='enabled'; that hidden ordering dependency is replaced
 * here by an explicit beforeEach seed, so the test is order-independent and
 * re-runnable. The toggle is enable-able only because SUCURISCAN_API_URL is
 * defined in .wp-env.json — otherwise the button is forced to "Enable".
 *
 * The checksum test stores a valid GitHub repository and verifies the resolved
 * API URL. afterAll restores the checksum and service options.
 */
import { test, expect } from "@playwright/test";
import { expectNotice } from "../../support/notices";
import {
  updateOption,
  deleteOption,
  readSettingsFileJson,
} from "../../support/wp-cli";

const APISERVICE_URL =
  "/wp-admin/admin.php?page=sucuriscan_settings#apiservice";

// The toggle test performs a paired enable/disable sequence whose label/notice
// assertions depend on the starting state; keep these tests ordered.
test.describe.configure({ mode: "serial" });

test.describe("Settings · API Service", () => {
  test.beforeEach(() => {
    // Deterministic starting point: the toggle test asserts the button reads
    // "Enable" after the first click, which only holds if it started enabled.
    updateOption("sucuriscan_api_service", "enabled");
  });

  test.afterAll(() => {
    updateOption("sucuriscan_api_service", "enabled");
    // The checksum test's api.wordpress.org URL fails the github regex and
    // deletes this option; clear it so a later integrity/checksum test starts
    // from the unset default.
    deleteOption("sucuriscan_checksum_api");
    // The malware-scan-target test sets this; reset to the unset default.
    deleteOption("sucuriscan_sitecheck_target");
  });

  test("can toggle api service communication", async ({ page }) => {
    await page.goto(APISERVICE_URL);

    const toggle = page.getByTestId("sucuriscan_api_status_toggle");

    // Starts enabled -> first click disables it; the button now offers "Enable".
    await toggle.click();
    await expectNotice(page, "The status of the API service has been changed");
    await expect(toggle).toContainText("Enable");

    // Second click re-enables it; net effect returns to enabled.
    await toggle.click();
    await expectNotice(page, "The status of the API service has been changed");
    await expect(toggle).toContainText("Disable");
  });

  test("can update the WordPress checksum repository", async ({ page }) => {
    await page.goto(APISERVICE_URL);

    await page
      .getByTestId("sucuriscan_wordpress_checksum_api_input")
      .fill("https://github.com/WordPress/WordPress");
    await page.getByTestId("sucuriscan_wordpress_checksum_api_submit").click();

    // The .updated notice and .sucuriscan-alert co-exist on the same notice div;
    // expectNotice targets .sucuriscan-alert, matching the same element.
    await expectNotice(
      page,
      "The URL to retrieve the WordPress checksums has been changed",
    );
    expect(readSettingsFileJson().sucuriscan_checksum_api).toBe(
      "WordPress/WordPress",
    );
    await expect(
      page.getByRole("link", {
        name: "https://api.github.com/repos/WordPress/WordPress/git/trees/master?recursive=1",
      }),
    ).toBeVisible();
  });

  test("can change malware scan target", async ({ page }) => {
    const testDomain = "sucuri.net";

    await page.goto(APISERVICE_URL);

    await page
      .getByTestId("sucuriscan_sitecheck_target_input")
      .fill(testDomain);
    await page.getByTestId("sucuriscan_sitecheck_target_submit").click();

    await page.reload();
    await expect(page.getByTestId("sucuriscan_sitecheck_target")).toContainText(
      testDomain,
    );
  });
});
