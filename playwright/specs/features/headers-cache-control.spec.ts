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
 * Each test resets cache options, temporarily quarantines future posts, and uses
 * test-owned content IDs. The shared fixture restores plugin options afterward.
 */
import { test, expect } from "../../support/fixtures";
import type { Page } from "@playwright/test";
import { expectHeaderEquals } from "../../support/http";
import {
  deleteOption,
  readSettingsFileJson,
  updateOption,
  wpEval,
} from "../../support/wp-cli";

const HEADERS_URL = "/wp-admin/admin.php?page=sucuriscan_headers_management";
const FUTURE_MARKER = "_sucuri_e2e_original_future";

let postId: number;
let pageId: number;
let categoryId: number;
const categorySlug = `sucuri-e2e-cache-${process.pid}`;

function restoreFuturePosts(): void {
  wpEval(
    `$q=new WP_Query(array("post_type"=>"any","post_status"=>"draft","posts_per_page"=>-1,"meta_key"=>"${FUTURE_MARKER}"));` +
      `global $wpdb;foreach($q->posts as $p){$wpdb->update($wpdb->posts,array("post_status"=>"future"),array("ID"=>$p->ID));delete_post_meta($p->ID,"${FUTURE_MARKER}");clean_post_cache($p->ID);}`,
  );
}

function quarantineFuturePosts(): void {
  restoreFuturePosts();
  wpEval(
    '$q=new WP_Query(array("post_type"=>"any","post_status"=>"future","posts_per_page"=>-1));' +
      `global $wpdb;foreach($q->posts as $p){update_post_meta($p->ID,"${FUTURE_MARKER}",1);` +
      '$wpdb->update($wpdb->posts,array("post_status"=>"draft"),array("ID"=>$p->ID));clean_post_cache($p->ID);}',
  );
}

test.beforeAll(() => {
  const fixture = JSON.parse(
    wpEval(
      '$old=get_posts(array("post_type"=>array("post","page"),"post_status"=>"any","meta_key"=>"_sucuri_e2e_cache_fixture","posts_per_page"=>-1));' +
        'foreach($old as $p){wp_delete_post($p->ID,true);}' +
        '$terms=get_terms(array("taxonomy"=>"category","hide_empty"=>false,"meta_key"=>"_sucuri_e2e_cache_fixture","meta_value"=>1));' +
        'foreach($terms as $term){wp_delete_term($term->term_id,"category");}' +
        `$cat=wp_insert_term("Sucuri E2E Cache","category",array("slug"=>${JSON.stringify(categorySlug)}));` +
        '$cid=(int)(is_array($cat)?$cat["term_id"]:$cat);' +
        'update_term_meta($cid,"_sucuri_e2e_cache_fixture",1);' +
        '$post=wp_insert_post(array("post_title"=>"Sucuri E2E Cache Post","post_name"=>"sucuri-e2e-cache-post","post_status"=>"publish","post_type"=>"post","post_category"=>array($cid),"meta_input"=>array("_sucuri_e2e_cache_fixture"=>1)));' +
        '$page=wp_insert_post(array("post_title"=>"Sucuri E2E Cache Page","post_name"=>"sucuri-e2e-cache-page","post_status"=>"publish","post_type"=>"page","meta_input"=>array("_sucuri_e2e_cache_fixture"=>1)));' +
        'echo wp_json_encode(array("post"=>$post,"page"=>$page,"category"=>$cid));',
    ),
  ) as { post: number; page: number; category: number };
  postId = fixture.post;
  pageId = fixture.page;
  categoryId = fixture.category;
});

test.beforeEach(() => {
  quarantineFuturePosts();
  deleteOption("sucuriscan_headers_cache_control_options");
  updateOption("sucuriscan_headers_cache_control", "disabled");
});

test.afterEach(() => {
  restoreFuturePosts();
});

test.afterAll(() => {
  restoreFuturePosts();
  wpEval(
    `wp_delete_post(${postId},true);wp_delete_post(${pageId},true);` +
      `wp_delete_term(${categoryId},"category");`,
  );
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
    `/?p=${postId}`,
    "cache-control",
    "max-age=600",
  ); // single post
  await expectHeaderEquals(
    loggedOutRequest,
    `/?page_id=${pageId}`,
    "cache-control",
    "max-age=600",
  ); // page
  await expectHeaderEquals(
    loggedOutRequest,
    `/?cat=${categoryId}`,
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
    "/?p=999999999",
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
    `/?p=${postId}`,
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
