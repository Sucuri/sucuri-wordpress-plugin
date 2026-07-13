/**
 * Headers management · Cache-Control header.
 *
 * Drives the mode dropdown (Busy / Frequent / Disabled / auto-"custom") on the
 * Headers Management admin page, then asserts the actual `Cache-Control`
 * response header that the plugin emits on front-end and wp-admin requests.
 *
 * Auth coupling is the crux of these tests: the plugin serves the no-cache
 * directive to logged-IN visitors and the tier max-age to logged-OUT visitors
 * (cachecontrol.lib.php isNoCacheable() -> is_user_logged_in()). So the
 * anonymous header reads use the `loggedOutRequest` fixture (a fresh,
 * cookie-less APIRequestContext, the equivalent of Cypress's cy.clearCookies()),
 * while the single logged-in check uses the authenticated `request` fixture,
 * which inherits the admin storageState from the `features` project.
 *
 * The per-post-type custom max-age and old-age-multiplier values are persisted
 * by a background jQuery $.post fired when a row collapses (Edit -> Update). That
 * POST is awaited via waitForResponse before reading the header / reloading, so
 * the GET never races the option write (the top flakiness source per blueprint).
 *
 * All five tests mutate the same two global options, so they run serially and an
 * afterAll resets the mode to Disabled and restores the cache options to plugin
 * defaults — keeping re-runs and any later header spec starting from clean state.
 */
import { test, expect } from "../../support/fixtures";
import type { Page } from "@playwright/test";
import { expectHeaderEquals } from "../../support/http";
import {
  deleteOption,
  readSettingsFileJson,
  updateOption,
} from "../../support/wp-cli";

const HEADERS_URL = "/wp-admin/admin.php?page=sucuriscan_headers_management";

test.beforeEach(() => {
  deleteOption("sucuriscan_headers_cache_control_options");
  updateOption("sucuriscan_headers_cache_control", "disabled");
});

/**
 * Select a cache mode by its (capitalised) label, submit the full-page POST,
 * and wait for the resulting alert. Option values are lowercase ('busy',
 * 'disabled', 'frequent') while labels are ucfirst, so we match by label.
 */
async function setCacheMode(
  page: Page,
  label: "Busy" | "Frequent" | "Disabled",
): Promise<void> {
  await page
    .getByTestId("sucuriscan_headers_cache_control_dropdown")
    .selectOption({ label });
  await page.getByTestId("sucuriscan_headers_cache_control_submit_btn").click();
  const expectedAlert =
    label === "Disabled"
      ? "Cache-Control header was deactivated."
      : "Cache-Control header was activated.";
  // Full-page reload; the web-first toContainText auto-waits for the new render.
  await expect(page.locator(".sucuriscan-alert")).toContainText(expectedAlert);
}

/**
 * Collapse the Posts row (Edit -> Update), awaiting the background AJAX $.post
 * that persists the per-post-type option values, so subsequent header reads /
 * reloads observe the committed value rather than racing the write.
 */
async function collapsePostsRowAndPersist(page: Page): Promise<void> {
  await Promise.all([
    page.waitForResponse(
      (r) =>
        r.url().includes("page=sucuriscan_headers_management") &&
        r.request().method() === "POST",
    ),
    page.getByTestId("sucuriscan-row-posts").click(),
  ]);
}

/**
 * Assert the Cache-Control status box flipped to the green "Enabled" state.
 * Scoped to the cache box's data-cy on purpose: the Headers page also renders
 * CSP and CORS status boxes that share the `.sucuriscan-hstatus-1` class, and
 * the row-collapse JS (`$('.sucuriscan-double-box-update')`) flips all three at
 * once — so a bare `.sucuriscan-hstatus-1` locator matches 3 elements and trips
 * Playwright strict mode (Cypress's `.contains()` silently took the first).
 */
async function expectCacheControlEnabled(page: Page): Promise<void> {
  const box = page.getByTestId("sucuriscan_headers_cache_control");
  await expect(box).toHaveClass(/sucuriscan-hstatus-1/);
  await expect(box).toContainText("Enabled");
}

test.afterAll(() => {
  // Robust reset: drop both options so the plugin regenerates defaults
  // (mode 'disabled', posts.max_age=43200, posts.old_age_multiplier=0) on next
  // read, then pin the mode explicitly. This guarantees re-runnability even if
  // a test failed mid-flight leaving 'busy'/'custom' + posts.max_age=12345.
  deleteOption("sucuriscan_headers_cache_control_options");
  updateOption("sucuriscan_headers_cache_control", "disabled");
});

test("Can toggle the header cache control setting", async ({ page }) => {
  await page.goto(HEADERS_URL);

  await setCacheMode(page, "Busy");
  await setCacheMode(page, "Disabled");
});

test("Can set the Cache-Control header properly", async ({
  page,
  loggedOutRequest,
}) => {
  await page.goto(HEADERS_URL);

  await setCacheMode(page, "Busy");

  // Logged-OUT reads (loggedOutRequest is cookie-less) so isNoCacheable() is
  // false and the busy-tier max-age values are served.
  await expectHeaderEquals(
    loggedOutRequest,
    "/",
    "cache-control",
    "max-age=300",
  ); // home / front_page
  await expectHeaderEquals(
    loggedOutRequest,
    "/?p=1",
    "cache-control",
    "max-age=600",
  ); // single post
  await expectHeaderEquals(
    loggedOutRequest,
    "/?page_id=2",
    "cache-control",
    "max-age=600",
  ); // page
  await expectHeaderEquals(
    loggedOutRequest,
    "/?cat=1",
    "cache-control",
    "max-age=600",
  ); // category archive
  await expectHeaderEquals(
    loggedOutRequest,
    "/?author=1",
    "cache-control",
    "max-age=600",
  ); // author archive
  await expectHeaderEquals(
    loggedOutRequest,
    "/?p=12",
    "cache-control",
    "max-age=600",
    { status: 404 },
  ); // 404 (busy tier)
});

test("Can customize the Cache-Control header properly", async ({
  page,
  loggedOutRequest,
}) => {
  await page.goto(HEADERS_URL);

  // Deactivate first so the custom edit is the only source of the served value.
  await setCacheMode(page, "Disabled");

  // Expand the Posts row (Edit), type a custom max-age, and confirm the edit
  // handler auto-switched the mode dropdown to 'custom' (synchronous JS).
  await page.getByTestId("sucuriscan-row-posts").click();
  await page.locator('input[name="sucuriscan_posts_max_age"]').fill("12345");
  await expect(
    page.getByTestId("sucuriscan_headers_cache_control_dropdown"),
  ).toHaveValue("custom");

  // Collapse the row (Update) -> AJAX persists posts.max_age=12345 and flips the
  // status box to Enabled. Await the POST so the header read below is committed.
  await collapsePostsRowAndPersist(page);
  await expectCacheControlEnabled(page);

  await expectHeaderEquals(
    loggedOutRequest,
    "/?p=1",
    "cache-control",
    "max-age=12345",
  );
});

test("Can customize the old age multiplier for the Cache-Control header", async ({
  page,
}) => {
  await page.goto(HEADERS_URL);

  await setCacheMode(page, "Disabled");

  const oldAgeMultiplier = page.locator(
    'input[name="sucuriscan_posts_old_age_multiplier"]',
  );
  await expect(oldAgeMultiplier).not.toBeChecked();

  // The checkbox is rendered disabled and only becomes editable while the row is
  // expanded, so toggle it strictly between the two row-expander clicks.
  await page.getByTestId("sucuriscan-row-posts").click();
  await expect(oldAgeMultiplier).toBeEnabled();
  await oldAgeMultiplier.evaluate((element) => {
    const input = element as HTMLInputElement;
    input.checked = true;
    input.dispatchEvent(new Event("change", { bubbles: true }));
  });
  await expect(oldAgeMultiplier).toHaveValue("1");
  await collapsePostsRowAndPersist(page);

  await expectCacheControlEnabled(page);
  expect(
    (
      readSettingsFileJson().sucuriscan_headers_cache_control_options as {
        posts: { old_age_multiplier: number };
      }
    ).posts.old_age_multiplier,
  ).toBe(1);

  // Reload to confirm the checked state persisted to the option store.
  await page.goto(HEADERS_URL);
  await expect(oldAgeMultiplier).toBeChecked();

  // Toggle it back off.
  await page.getByTestId("sucuriscan-row-posts").click();
  await oldAgeMultiplier.evaluate((element) => {
    const input = element as HTMLInputElement;
    input.checked = false;
    input.dispatchEvent(new Event("change", { bubbles: true }));
  });
  await expect(oldAgeMultiplier).toHaveValue("0");
  await collapsePostsRowAndPersist(page);

  await expect(oldAgeMultiplier).not.toBeChecked();
  await expectCacheControlEnabled(page);
});

test("Cache-Control header functionality pages protected by log in", async ({
  page,
  request,
  loggedOutRequest,
}) => {
  await page.goto(HEADERS_URL);

  await setCacheMode(page, "Frequent");

  // Logged-IN read: the authenticated `request` fixture inherits the admin
  // storageState, so is_admin()+is_user_logged_in() force the no-cache directive.
  await expectHeaderEquals(
    request,
    "/wp-admin/index.php",
    "cache-control",
    "no-cache, must-revalidate, max-age=0, no-store, private",
  );

  // Logged-OUT read: cookie-less context gets the frequent front_page tier.
  await expectHeaderEquals(
    loggedOutRequest,
    "/",
    "cache-control",
    "max-age=1800",
  );
});
