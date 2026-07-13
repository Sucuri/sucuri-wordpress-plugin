/**
 * Scanner area: scheduled tasks (cronjobs), the WordPress integrity diff
 * utility (false-positive ignore/unignore, per-page dropdown counts,
 * pagination), the integrity diff utility enable/disable toggle, and
 * ignoring files/folders during scans.
 *
 * Shared state: every integrity test reads the seeded file baseline and mutates
 * the integrity false-positive cache (sucuri-integrity.php data store). The
 * pagination test in particular marks 15 files fixed WITHOUT un-ignoring them,
 * so an afterEach wipes the integrity + ignore data stores for the next test/run.
 * The cron schedule and the diff-utility option are also restored.
 *
 * Every selected test creates the scanner fixtures and clears scanner state.
 * beforeEach pins :diff_utility = enabled so the dashboard renders the
 * clickable integrity rows and the toggle test starts from a known state.
 */
import { test, expect } from "../../support/fixtures";
import type { Page, Response } from "@playwright/test";
import {
  deleteOption,
  restoreCron,
  restoreWpFiles,
  snapshotCron,
  snapshotWpFiles,
  updateOption,
  wpEval,
  type CronSnapshot,
  type FileSnapshot,
} from "../../support/wp-cli";

const DASHBOARD_URL = "/wp-admin/admin.php?page=sucuriscan";
const SCANNER_URL = "/wp-admin/admin.php?page=sucuriscan_settings#scanner";

const SCANNER_FIXTURES = [
  "wp-config-test.php",
  ...Array.from({ length: 100 }, (_, index) => `wp-test-file-${index + 1}.php`),
];

/**
 * Match the integrity-list AJAX: a POST to admin-ajax.php?page=sucuriscan
 * whose body contains "check_wordpress_integrity". Replaces the Cypress
 * cy.intercept alias + cy.wait('@integrityCheck').
 */
function waitForIntegrityCheck(page: Page): Promise<Response> {
  return page.waitForResponse(
    (r) =>
      r.url().includes("admin-ajax.php") &&
      r.url().includes("page=sucuriscan") &&
      r.request().method() === "POST" &&
      (r.request().postData() ?? "").includes("check_wordpress_integrity"),
  );
}

/** Navigate to the dashboard and await the integrity-list AJAX it fires on load. */
async function gotoDashboardAndWaitIntegrity(page: Page): Promise<void> {
  const integrity = waitForIntegrityCheck(page);
  await page.goto(DASHBOARD_URL);
  await integrity;
}

/**
 * Change the integrity per-page dropdown and wait for the re-render AJAX, then
 * assert the visible file-row count. Replaces the racy .sucuriscan-is-loading
 * "Loading..." gate with a deterministic waitForResponse + toHaveCount.
 */
async function selectPerPageAndExpectCount(
  page: Page,
  value: string,
  count: number,
): Promise<void> {
  const integrity = waitForIntegrityCheck(page);
  await page
    .getByTestId("sucuriscan_integrity_files_per_page")
    .selectOption(value);
  await integrity;
  await expect(page.locator(".sucuriscan-integrity-filepath")).toHaveCount(
    count,
  );
}

/** Read the affected-file total reported by the current integrity response. */
async function integrityFileCount(page: Page): Promise<number> {
  const heading = await page
    .locator(".sucuriscan-integrity-table thead span")
    .first()
    .innerText();
  const match = heading.match(/WordPress Integrity \((\d+)\)/);

  if (!match) {
    throw new Error(`Could not read the integrity file count from: ${heading}`);
  }

  return Number(match[1]);
}

/**
 * Tick the confirm-irreversible checkbox, assert the submit button is enabled
 * (it is disabled until both the confirm box and >=1 file row are checked),
 * then submit the Mark-as-Fixed form.
 */
async function confirmAndSubmitIntegrity(page: Page): Promise<void> {
  await page.getByTestId("sucuriscan_integrity_incorrect_checkbox").check();
  const submit = page.getByTestId("sucuriscan_integrity_incorrect_submit");
  await expect(submit).toBeEnabled();
  await submit.click();
}

/**
 * Reset the shared scanner state so each test starts from the seeded baseline:
 * delete the integrity false-positive cache and the ignore-scanning data store
 * (the plugin recreates them empty on next access). Restores the 105-file
 * dashboard count and an empty ignore list.
 */
function clearScannerDataStores(): void {
  wpEval(
    '@unlink(SucuriScan::dataStorePath("sucuri-integrity.php"));' +
      '@unlink(SucuriScan::dataStorePath("sucuri-ignorescanning.php"));',
  );
}

test.describe("Scanner", () => {
  let originalFiles: FileSnapshot;
  let originalPluginCron: CronSnapshot[];

  test.beforeAll(() => {
    originalFiles = snapshotWpFiles(SCANNER_FIXTURES);
    originalPluginCron = snapshotCron("wp_update_plugins");
  });

  test.beforeEach(() => {
    clearScannerDataStores();
    deleteOption("sucuriscan_checksum_api");
    // The dashboard only renders the clickable integrity rows (and the toggle
    // test only starts "disabled") when this option is enabled at page load.
    updateOption("sucuriscan_diff_utility", "enabled");
    wpEval(
      'touch(ABSPATH."wp-config-test.php");' +
        'for($i=1;$i<=100;$i++){touch(ABSPATH."wp-test-file-".$i.".php");}' +
        'wp_clear_scheduled_hook("wp_update_plugins");' +
        'wp_schedule_event(time()+3600,"twicedaily","wp_update_plugins");',
    );
  });

  test.afterEach(() => {
    // Guarantee an empty ignore list for the next test, even if a test left
    // files marked-fixed (the pagination test does).
    clearScannerDataStores();
    restoreCron("wp_update_plugins", originalPluginCron);
  });

  test.afterAll(() => {
    restoreWpFiles(originalFiles);
  });

  test("can modify scheduled tasks", async ({ page }) => {
    await page.goto(SCANNER_URL);

    // The cron table body is populated async via the get_cronjobs AJAX; wait
    // for the wp_update_plugins row before interacting with its checkbox.
    const row = page.getByTestId("sucuriscan_row_wp_update_plugins");
    await expect(row).toBeVisible();

    await page.locator('input[value="wp_update_plugins"]').check();
    await page.getByTestId("sucuriscan_cronjobs_select").selectOption({
      label: "Quarterly (every 7776000 seconds)",
    });
    await page.getByTestId("sucuriscan_cronjobs_submit").click();

    await expect(page.locator(".sucuriscan-alert")).toContainText(
      "1 tasks has been re-scheduled to run quarterly.",
    );

    // The Schedule column (3rd td) now reads "quarterly"; assert on the whole
    // row since "quarterly" only appears in that cell.
    await expect(
      page.getByTestId("sucuriscan_row_wp_update_plugins"),
    ).toContainText("quarterly");
  });

  test("can ignore and unignore false positives (integrity diff utility)", async ({
    page,
  }) => {
    await gotoDashboardAndWaitIntegrity(page);

    // Show all files so the wp-config-test.php "added" row is on the page.
    const integrity = waitForIntegrityCheck(page);
    await page
      .getByTestId("sucuriscan_integrity_files_per_page")
      .selectOption("1000");
    await integrity;

    const fileCheckbox = page.locator(
      'input[value="added@wp-config-test.php"]',
    );
    await expect(fileCheckbox).toBeVisible();
    await fileCheckbox.check();
    await confirmAndSubmitIntegrity(page);

    await expect(page.locator(".sucuriscan-alert")).toContainText(
      "1 out of 1 files were successfully processed.",
    );

    await page.goto(SCANNER_URL);

    const falsePositiveTable = page.getByTestId(
      "sucuriscan_integrity_diff_false_positive_table",
    );
    await expect(falsePositiveTable).toContainText("wp-config-test.php");

    await page.locator('input[value="wp-config-test.php"]').check();
    await page
      .getByTestId("sucuriscan_integrity_diff_false_positive_submit")
      .click();

    await expect(page.locator(".sucuriscan-alert")).toContainText(
      "The selected files have been successfully processed.",
    );
    await expect(
      page.getByTestId("sucuriscan_integrity_diff_false_positive_table"),
    ).toContainText("no data available");

    await gotoDashboardAndWaitIntegrity(page);

    await expect(
      page.getByTestId("sucuriscan_integrity_list_table"),
    ).toContainText("wp-config-test.php");
  });

  test("can use new dropdown in integrity diff utility", async ({ page }) => {
    await gotoDashboardAndWaitIntegrity(page);
    const initialCount = await integrityFileCount(page);
    expect(initialCount).toBeGreaterThanOrEqual(101);

    const totalPages = Math.ceil(initialCount / 15);
    // Pagination renders at most 16 buttons around the active page.
    await expect(
      page.locator(
        ".sucuriscan-pagination-integrity .sucuriscan-pagination-link",
      ),
    ).toHaveCount(Math.min(totalPages, 16));

    await selectPerPageAndExpectCount(page, "200", initialCount);
    const fixturePaths = await page
      .locator(".sucuriscan-integrity-filepath")
      .evaluateAll((elements) =>
        elements
          .map((element) => element.getAttribute("data-filepath") ?? "")
          .filter((path) => /^wp-test-file-\d+\.php$/.test(path)),
      );
    expect(new Set(fixturePaths).size).toBe(100);
    for (let index = 1; index <= 100; index++) {
      expect(fixturePaths).toContain(`wp-test-file-${index}.php`);
    }
    await selectPerPageAndExpectCount(page, "15", 15);
    await selectPerPageAndExpectCount(page, "50", 50);

    // WordPress-version checksum differences can add modified core files that
    // cannot be marked fixed. Show all rows and select 50 deterministic files
    // created by this spec instead.
    await selectPerPageAndExpectCount(page, "200", initialCount);
    const seededFiles = page.locator(
      'input[name="sucuriscan_integrity[]"][value^="added@wp-test-file-"]',
    );
    await expect(seededFiles).toHaveCount(100);
    for (let index = 0; index < 50; index++) {
      await seededFiles.nth(index).check();
    }
    await confirmAndSubmitIntegrity(page);

    await expect(page.locator(".sucuriscan-alert")).toContainText(
      "50 out of 50 files were successfully processed.",
    );

    await page.goto(SCANNER_URL);

    // Each ignored file is a row with class sucuriscan-integrity-<UniqueId>.
    await expect(
      page
        .getByTestId("sucuriscan_integrity_diff_false_positive_table")
        .locator('tr[class^="sucuriscan-integrity-"]'),
    ).toHaveCount(50);

    await page
      .getByTestId("sucuriscan_integrity_diff_false_positive_table")
      .locator("#cb-select-all-1")
      .check();
    await page
      .getByTestId("sucuriscan_integrity_diff_false_positive_submit")
      .click();

    await expect(page.locator(".sucuriscan-alert")).toContainText(
      "The selected files have been successfully processed.",
    );
    await expect(
      page.getByTestId("sucuriscan_integrity_diff_false_positive_table"),
    ).toContainText("no data available");

    await gotoDashboardAndWaitIntegrity(page);

    await selectPerPageAndExpectCount(
      page,
      "200",
      initialCount,
    );
  });

  test("can use pagination in integrity diff utility", async ({ page }) => {
    await gotoDashboardAndWaitIntegrity(page);

    const pagination = page.locator(
      ".sucuriscan-pagination-integrity .sucuriscan-pagination-link",
    );
    const initialCount = await integrityFileCount(page);
    const totalPages = Math.ceil(initialCount / 15);
    await expect(pagination).toHaveCount(Math.min(totalPages, 16));
    await expect(
      page.locator('.sucuriscan-pagination-integrity [data-page="2"]'),
    ).toBeVisible();

    const pageOneFirstPath = await page
      .locator(".sucuriscan-integrity-filepath")
      .first()
      .getAttribute("data-filepath");

    const toPage2 = waitForIntegrityCheck(page);
    await page
      .locator('.sucuriscan-pagination-integrity [data-page="2"]')
      .click();
    await toPage2;
    await expect(
      page.locator(".sucuriscan-integrity-filepath").first(),
    ).not.toHaveAttribute("data-filepath", pageOneFirstPath ?? "");

    const visibleLastPage = Math.min(totalPages, 16);
    const toLastPage = waitForIntegrityCheck(page);
    await page
      .locator(
        `.sucuriscan-pagination-integrity [data-page="${visibleLastPage}"]`,
      )
      .click();
    await toLastPage;
    await expect(page.locator(".sucuriscan-integrity-filepath").first()).toBeVisible();
  });

  test("can activate and deactivate the WordPress integrity diff utility", async ({
    page,
  }) => {
    // beforeEach pins :diff_utility = enabled, so the toggle starts at "Disable"
    // (enabled state) -> first click disables it (label flips to "Enable") ...
    await page.goto(SCANNER_URL);

    const toggle = page.getByTestId(
      "sucuriscan_scanner_integrity_diff_utility_toggle",
    );
    await expect(toggle).toContainText("Disable");

    await toggle.click();
    await expect(page.locator(".sucuriscan-alert")).toContainText(
      "The status of the integrity diff utility has been changed",
    );
    await expect(
      page.getByTestId("sucuriscan_scanner_integrity_diff_utility_toggle"),
    ).toContainText("Enable");

    await page
      .getByTestId("sucuriscan_scanner_integrity_diff_utility_toggle")
      .click();
    await expect(page.locator(".sucuriscan-alert")).toContainText(
      "The status of the integrity diff utility has been changed",
    );
    await expect(
      page.getByTestId("sucuriscan_scanner_integrity_diff_utility_toggle"),
    ).toContainText("Disable");
  });

  test("can ignore files and folders during the scans", async ({ page }) => {
    await page.goto(SCANNER_URL);

    await page
      .getByTestId("sucuriscan_ignore_files_folders_input")
      .fill("sucuri-images");
    await page
      .getByTestId("sucuriscan_ignore_files_folders_ignore_submit")
      .click();

    // Note: no leading "The" here, unlike the false-positive un-ignore string.
    await expect(page.locator(".sucuriscan-alert")).toContainText(
      "Selected files have been successfully processed.",
    );
    await expect(
      page.getByTestId("sucuriscan_ignore_files_folders_table"),
    ).toContainText("sucuri-images");

    const unignoreCheckbox = page.locator('input[value="sucuri-images"]');
    await expect(unignoreCheckbox).toBeVisible();
    await unignoreCheckbox.check();
    await page
      .getByTestId("sucuriscan_ignore_files_folders_unignore_submit")
      .click();

    await expect(page.locator(".sucuriscan-alert")).toContainText(
      "Selected files have been successfully processed.",
    );
    await expect(
      page.getByTestId("sucuriscan_ignore_files_folders_table"),
    ).toContainText("no data available");
  });
});
