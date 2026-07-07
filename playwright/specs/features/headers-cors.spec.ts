/**
 * Headers Management · Cross-Origin Resource Sharing (CORS).
 *
 * Drives the CORS panel at ?page=sucuriscan_headers_management: the inline
 * jQuery enable/disable toggle, saving the Access-Control-Allow-Origin value,
 * the Access-Control-Allow-Methods multi-checkbox logic, Allow-Credentials
 * set/unset, and disabling the whole CORS mode. Front-end headers are read as
 * an anonymous visitor via the `loggedOutRequest` fixture (mirrors cy.request('/')).
 *
 * State / ordering: several tests do not select a mode and rely on CORS already
 * being `enabled` by an earlier test, and the credentials test must run before
 * the final "disable" test. They run SERIAL in declaration order. The afterAll
 * selects CORS mode = disabled and submits so re-runs start clean (and the live
 * site stops emitting CORS headers into later spec files). The CORS form POST
 * reloads the page, so each submit awaits the navigation before any header read.
 */
import { test, expect } from "../../support/fixtures";
import type { Page } from "@playwright/test";
import { updateOption } from "../../support/wp-cli";
import {
  expectHeaderEquals,
  expectHeaderContains,
  expectHeaderAbsent,
} from "../../support/http";

const HEADERS_URL = "/wp-admin/admin.php?page=sucuriscan_headers_management";

const enforceOrigin = (page: Page) =>
  page.locator("input[name='sucuriscan_enforced_Access-Control-Allow-Origin']");
const originValue = (page: Page) =>
  page.locator("input[name='sucuriscan_cors_Access-Control-Allow-Origin']");
const enforceMethods = (page: Page) =>
  page.locator(
    "input[name='sucuriscan_enforced_Access-Control-Allow-Methods']",
  );
const methodGet = (page: Page) =>
  page.locator(
    "input[name='sucuriscan_cors_Access-Control-Allow-Methods_GET']",
  );
const methodPost = (page: Page) =>
  page.locator(
    "input[name='sucuriscan_cors_Access-Control-Allow-Methods_POST']",
  );
const methodOptions = (page: Page) =>
  page.locator(
    "input[name='sucuriscan_cors_Access-Control-Allow-Methods_OPTIONS']",
  );
const enforceCredentials = (page: Page) =>
  page.locator(
    "input[name='sucuriscan_enforced_Access-Control-Allow-Credentials']",
  );
const credentialsToken = (page: Page) =>
  page.locator(
    "input[name='sucuriscan_cors_Access-Control-Allow-Credentials_Access-Control-Allow-Credentials']",
  );
const modeSelect = (page: Page) =>
  page.getByTestId("sucuriscan_cors_options_mode_button");

/**
 * Click the CORS submit and wait for the form POST round-trip to complete, so
 * the option is persisted server-side before any front-end header is read.
 * (waitForLoadState('load') would resolve immediately since the page is already
 * loaded — we must wait for the POST navigation response instead.)
 */
async function submitCors(page: Page): Promise<void> {
  await Promise.all([
    page.waitForResponse(
      (r) =>
        r.request().method() === "POST" &&
        r.url().includes("page=sucuriscan_headers_management"),
    ),
    page.getByTestId("sucuriscan_headers_cors_control_submit_btn").click(),
  ]);
}

test.describe.configure({ mode: "serial" });

test.describe("Headers · CORS", () => {
  test.afterAll(() => {
    // Disable CORS mode so re-runs start clean and the live site stops emitting
    // CORS headers into subsequent spec files. Done via wp-cli (not the UI) so it
    // is auth-independent and cannot silently no-op. Mode 'disabled' => the plugin
    // short-circuits before sending any Access-Control-* header (cors.lib.php:42).
    updateOption("sucuriscan_headers_cors", "disabled");
  });

  test("toggling enforce checkbox enables/disables the Allow-Origin input", async ({
    page,
  }) => {
    await page.goto(HEADERS_URL);

    await enforceOrigin(page).uncheck({ force: true });
    await expect(enforceOrigin(page)).not.toBeChecked();
    await expect(originValue(page)).toBeDisabled();

    await enforceOrigin(page).check({ force: true });
    await expect(enforceOrigin(page)).toBeChecked();
    await expect(originValue(page)).toBeEnabled();
  });

  test("saves Allow-Origin value example.com, persists after reload and emits the header", async ({
    page,
    loggedOutRequest,
  }) => {
    await page.goto(HEADERS_URL);

    await enforceOrigin(page).check({ force: true });
    await originValue(page).fill("example.com");

    await modeSelect(page).selectOption("enabled");
    await submitCors(page);

    await page.reload();
    await expect(originValue(page)).toHaveValue("example.com");
    await expect(originValue(page)).toBeEnabled();

    await expectHeaderEquals(
      loggedOutRequest,
      "/",
      "access-control-allow-origin",
      "example.com",
    );
  });

  test("Allow-Methods multi-checkbox (GET/POST/OPTIONS) works correctly", async ({
    page,
    loggedOutRequest,
  }) => {
    await page.goto(HEADERS_URL);

    await enforceMethods(page).check({ force: true });

    await methodGet(page).check({ force: true });
    await methodOptions(page).check({ force: true });

    await modeSelect(page).selectOption("enabled");
    await submitCors(page);

    // First pass: header exists and includes GET + OPTIONS but not PUT.
    await expectHeaderContains(
      loggedOutRequest,
      "/",
      "access-control-allow-methods",
      "GET",
    );
    await expectHeaderContains(
      loggedOutRequest,
      "/",
      "access-control-allow-methods",
      "OPTIONS",
    );
    const firstPass = await loggedOutRequest.get("/", {
      failOnStatusCode: false,
    });
    expect(firstPass.headers()["access-control-allow-methods"]).not.toContain(
      "PUT",
    );

    await page.goto(HEADERS_URL);
    await methodPost(page).check({ force: true });
    await methodOptions(page).uncheck({ force: true });
    await submitCors(page);

    // Second pass: includes GET + POST, but not PUT and not OPTIONS.
    const secondPass = await loggedOutRequest.get("/", {
      failOnStatusCode: false,
    });
    const allowMethods =
      secondPass.headers()["access-control-allow-methods"] ?? "";
    expect(allowMethods).toContain("GET");
    expect(allowMethods).toContain("POST");
    expect(allowMethods).not.toContain("PUT");
    expect(allowMethods).not.toContain("OPTIONS");
  });

  test("allows setting and unsetting Allow-Credentials", async ({
    page,
    loggedOutRequest,
  }) => {
    await page.goto(HEADERS_URL);

    // Unset credentials and submit -> header absent (CORS still enabled by prior tests).
    await enforceCredentials(page).uncheck({ force: true });
    await submitCors(page);

    await expectHeaderAbsent(
      loggedOutRequest,
      "/",
      "access-control-allow-credentials",
    );

    // Enforce credentials + check the single sub-token -> header equals literal 'true'.
    await page.goto(HEADERS_URL);
    await enforceCredentials(page).check({ force: true });
    await credentialsToken(page).check({ force: true });
    await submitCors(page);

    await expectHeaderEquals(
      loggedOutRequest,
      "/",
      "access-control-allow-credentials",
      "true",
    );
  });

  test("disabling the entire CORS mode removes all CORS headers", async ({
    page,
    loggedOutRequest,
  }) => {
    await page.goto(HEADERS_URL);

    await enforceOrigin(page).check({ force: true });
    await originValue(page).fill("example.org");

    await modeSelect(page).selectOption("enabled");
    await submitCors(page);

    await expectHeaderEquals(
      loggedOutRequest,
      "/",
      "access-control-allow-origin",
      "example.org",
    );

    await page.goto(HEADERS_URL);
    await modeSelect(page).selectOption("disabled");
    await submitCors(page);

    await expectHeaderAbsent(
      loggedOutRequest,
      "/",
      "access-control-allow-origin",
    );
  });
});
