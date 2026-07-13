/**
 * Headers Management · Content-Security-Policy (CSP).
 *
 * Covers the interactive enforce-checkbox enable/disable behaviour, saving an
 * enforced default-src 'none' as Report-Only (verified on the live front-end
 * header), the sandbox multi_checkbox directive, and the conditional
 * upgrade-insecure-requests directive.
 *
 * Every test resets the CSP option object and seeds the directives it needs,
 * so each scenario can run independently. The token order in the emitted header is fixed
 * by the PHP option-array declaration order (allow-forms < allow-orientation-lock
 * < allow-popups), not by checkbox click order, so the exact strings are asserted.
 *
 * Front-end header reads use the unauthenticated `loggedOutRequest` fixture so
 * they mirror Cypress' anonymous `cy.request('/')` (csp.lib.php emits the header
 * on normal front-end requests).
 *
 * afterAll sets the CSP mode option back to `disabled` so the live site stops
 * emitting Content-Security-Policy-Report-Only — keeping re-runs and the CORS
 * spec clean.
 */
import { test, expect } from "../../support/fixtures";
import type { Page } from "@playwright/test";
import { deleteOption, updateOption } from "../../support/wp-cli";
import {
  expectHeaderEquals,
  expectHeaderContains,
  expectHeaderAbsent,
} from "../../support/http";

const HEADERS_URL = "/wp-admin/admin.php?page=sucuriscan_headers_management";
const CSP_HEADER = "content-security-policy-report-only";

/** Submit the CSP form and wait for the new page to fully render. */
async function submitCspForm(page: Page): Promise<void> {
  // Click the submit button, then auto-wait for the confirmation alert that
  // WordPress renders after a full-page POST round-trip.  Using the web-first
  // toContainText assertion (rather than waitForResponse) guarantees the new
  // DOM is stable and JavaScript has run before we interact with any inputs.
  await page.getByTestId("sucuriscan_headers_csp_control_submit_btn").click();
  await expect(page.locator(".sucuriscan-alert")).toContainText(
    "Content Security Policy settings were updated.",
  );
}

test.describe("Headers · Content-Security-Policy", () => {
  test.beforeEach(() => {
    // Reset to clean state before the suite so a previous interrupted run that
    // left any directive enforced doesn't break the first assertion.
    // Disabling the mode stops header emission; deleting the options object
    // causes the plugin to regenerate defaults (all enforced=false) on the next
    // page load — which is the clean baseline the first test requires.
    updateOption("sucuriscan_headers_csp", "disabled");
    deleteOption("sucuriscan_headers_csp_options");
  });

  test.afterAll(async ({ loggedOutRequest }) => {
    // Disable CSP so the live front-end stops emitting the Report-Only header,
    // keeping re-runs and the CORS spec clean. Deleting the options object
    // resets all enforced flags to their plugin defaults (all false), ensuring
    // a subsequent run always starts from a known-clean state.
    updateOption("sucuriscan_headers_csp", "disabled");
    deleteOption("sucuriscan_headers_csp_options");
    await expectHeaderAbsent(loggedOutRequest, "/", CSP_HEADER);
  });

  test("Toggling enforce checkbox enables/disables inputs interactively", async ({
    page,
  }) => {
    await page.goto(HEADERS_URL);

    const enforce = page.locator(
      "input[name='sucuriscan_enforced_default_src']",
    );
    const value = page.locator("input[name='sucuriscan_csp_default_src']");

    await expect(enforce).not.toBeChecked();
    await expect(value).toBeDisabled();

    // The inline jQuery click handler flips the input's disabled prop; web-first
    // assertions auto-retry until the handler has run.
    await enforce.check({ force: true });
    await expect(value).toBeEnabled();

    await enforce.uncheck({ force: true });
    await expect(value).toBeDisabled();
  });

  test("Saves enforced state and value changes and persists after reload", async ({
    page,
    loggedOutRequest,
  }) => {
    await page.goto(HEADERS_URL);

    await page
      .locator("input[name='sucuriscan_enforced_default_src']")
      .check({ force: true });
    const value = page.locator("input[name='sucuriscan_csp_default_src']");
    await value.fill("'none'");

    // Option label 'Report Only' maps to the underlying value 'report-only'.
    await page
      .getByTestId("sucuriscan_csp_options_mode_button")
      .selectOption("report-only");
    await submitCspForm(page);

    await expect(value).toHaveValue("'none'");
    await expect(value).toBeEnabled();

    await expectHeaderEquals(
      loggedOutRequest,
      "/",
      CSP_HEADER,
      "default-src 'none'",
    );

    // Switch to Disabled and confirm the header disappears.
    await page
      .getByTestId("sucuriscan_csp_options_mode_button")
      .selectOption("disabled");
    await submitCspForm(page);

    await expectHeaderAbsent(loggedOutRequest, "/", CSP_HEADER);
  });

  test("Test multi_checkbox directive (sandbox)", async ({
    page,
    loggedOutRequest,
  }) => {
    await page.goto(HEADERS_URL);

    await page
      .locator("input[name='sucuriscan_enforced_default_src']")
      .check({ force: true });
    await page
      .locator("input[name='sucuriscan_csp_default_src']")
      .fill("'none'");
    await page
      .locator("input[name='sucuriscan_enforced_sandbox']")
      .check({ force: true });
    await page
      .locator("input[name='sucuriscan_csp_sandbox_allow-forms']")
      .check({ force: true });
    await page
      .locator("input[name='sucuriscan_csp_sandbox_allow-popups']")
      .check({ force: true });
    await page
      .locator("input[name='sucuriscan_csp_sandbox_allow-orientation-lock']")
      .check({ force: true });

    await page
      .getByTestId("sucuriscan_csp_options_mode_button")
      .selectOption("report-only");
    await submitCspForm(page);

    // Token order is fixed by PHP option-array declaration order, not click order.
    await expectHeaderEquals(
      loggedOutRequest,
      "/",
      CSP_HEADER,
      "default-src 'none'; sandbox allow-forms allow-orientation-lock allow-popups",
    );

    // submitCspForm above waits for the full-page reload confirmation, so the
    // DOM is stable and JavaScript has enabled the sub-inputs (enforce_sandbox
    // is checked after the save).  No { force: true } needed here.
    await page
      .locator("input[name='sucuriscan_csp_sandbox_allow-forms']")
      .uncheck();
    await page
      .locator("input[name='sucuriscan_csp_sandbox_allow-popups']")
      .uncheck();
    await page
      .locator("input[name='sucuriscan_csp_sandbox_allow-orientation-lock']")
      .uncheck();
    await page
      .locator("input[name='sucuriscan_csp_sandbox_allow-same-origin']")
      .check();

    await submitCspForm(page);

    await expectHeaderEquals(
      loggedOutRequest,
      "/",
      CSP_HEADER,
      "default-src 'none'; sandbox allow-same-origin",
    );
  });

  test("Upgrade Insecure Requests directive should not appear unless enforced", async ({
    page,
    loggedOutRequest,
  }) => {
    await page.goto(HEADERS_URL);

    await page
      .locator("input[name='sucuriscan_enforced_default_src']")
      .check({ force: true });
    await page
      .locator("input[name='sucuriscan_csp_default_src']")
      .fill("'none'");
    await page
      .getByTestId("sucuriscan_csp_options_mode_button")
      .selectOption("report-only");
    await submitCspForm(page);

    const before = await loggedOutRequest.get("/", { failOnStatusCode: false });
    expect(before.headers()[CSP_HEADER] ?? "").not.toContain(
      "upgrade-insecure-requests",
    );

    await page.goto(HEADERS_URL);

    const enforce = page.locator(
      "input[name='sucuriscan_enforced_upgrade_insecure_requests']",
    );
    const token = page.locator(
      "input[name='sucuriscan_csp_upgrade_insecure_requests_upgrade-insecure-requests']",
    );

    await expect(enforce).not.toBeChecked();
    await expect(token).toBeDisabled();

    await enforce.check({ force: true });
    await expect(token).toBeEnabled();
    await token.check({ force: true });

    await submitCspForm(page);

    await expectHeaderContains(
      loggedOutRequest,
      "/",
      CSP_HEADER,
      "upgrade-insecure-requests",
    );
  });
});
