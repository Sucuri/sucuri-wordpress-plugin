/**
 * Post-hack actions · Reset user password.
 *
 * Flow (page: sucuriscan_post_hack_actions):
 *   1. Confirm the seeded `sucuri-reset` author can still log in with the old
 *      password ('password') — done in a fresh logged-out context.
 *   2. As admin (inherited storageState), tick the `sucuri-reset` row checkbox,
 *      submit the reset, and assert the row response field reads
 *      'sucuri-reset (Done)' — the AJAX returns "Done", the inline JS wraps it
 *      as '(' + data + ')'.
 *   3. In another fresh logged-out context, confirm the old password no longer
 *      works (WP core #login_error).
 *
 * Logged-out contexts: steps 1 and 3 MUST run logged out, so they pass an empty
 * `storageState` to browser.newContext(). Under @playwright/test the `browser`
 * fixture's newContext() otherwise inherits the project `use` options — including
 * the admin storageState — so a bare newContext() carries the admin
 * `wordpress_logged_in` cookie (see support/fixtures.ts, which documents the same
 * footgun for loggedOutRequest). WordPress then no-ops the login form on correct
 * creds (no redirect to wp-admin) and masks the failed-login #login_error,
 * breaking both assertions. This is the Playwright analog of the Cypress
 * original's Cypress.session.clearAllSavedSessions()/clearCurrentSessionData().
 *
 * Idempotency: the reset randomizes sucuri-reset's password, so step 1 would
 * fail on re-run. The precondition is (re)seeded in beforeEach — NOT beforeAll,
 * which would not re-run on a Playwright retry and would leave every retry
 * starting from the already-randomized password. afterAll restores it to
 * 'password' for later specs. The admin account is never touched here (its row
 * checkbox is rendered disabled).
 */
import { test, expect } from "@playwright/test";
import { addWafDismissCookie, login } from "../../support/auth";
import { wp } from "../../support/wp-cli";
import { resetUser } from "../../support/env";

const POST_HACK_URL = "/wp-admin/admin.php?page=sucuriscan_post_hack_actions";

test.describe("Post-hack · Reset user password", () => {
  test.beforeEach(() => {
    // Pin the precondition: sucuri-reset must start with the known old password
    // so the initial "can log in" step is deterministic. beforeEach (not
    // beforeAll) so a retry re-seeds it — the test body randomizes the password,
    // and beforeAll would not re-run, dooming every retry at step 1.
    wp("user", "update", resetUser.login, `--user_pass=${resetUser.pass}`);
  });

  test.afterAll(() => {
    // The reset randomized sucuri-reset's password; restore it so the next run's
    // initial login step passes. Never touch the admin account.
    wp("user", "update", resetUser.login, `--user_pass=${resetUser.pass}`);
  });

  test("resets a user password and invalidates the old one", async ({
    page,
    browser,
  }) => {
    // 1. sucuri-reset can log in with the old password (fresh logged-out context).
    // Empty storageState drops the inherited admin session — see file header.
    const beforeContext = await browser.newContext({
      storageState: { cookies: [], origins: [] },
    });
    await addWafDismissCookie(beforeContext);
    const beforePage = await beforeContext.newPage();
    await login(beforePage, resetUser);
    await beforeContext.close();

    // 2. As admin, reset the sucuri-reset row's password.
    await page.goto(POST_HACK_URL);

    const row = page.getByRole("row", { name: /sucuri-reset/i });
    await row.getByRole("checkbox").check();

    // Await the reset AJAX so the "(Done)" injection is settled before asserting.
    await Promise.all([
      page.waitForResponse(
        (r) =>
          r.url().includes("admin-ajax.php") &&
          (r.request().postData() ?? "").includes("reset_user_password"),
      ),
      page.getByTestId("sucuriscan-reset-password-button").click(),
    ]);

    // The response em is injected with the AJAX payload, so the user cell reads
    // "sucuri-reset (Done)". Filter by username — the test id repeats per row.
    await expect(
      page
        .getByTestId("sucuriscan-reset-password-user-field")
        .filter({ hasText: "sucuri-reset" }),
    ).toContainText("sucuri-reset (Done)");

    // 3. The old password no longer works (fresh logged-out context).
    // Empty storageState drops the inherited admin session — see file header.
    const afterContext = await browser.newContext({
      storageState: { cookies: [], origins: [] },
    });
    const afterPage = await afterContext.newPage();
    await afterPage.goto("/wp-login.php");
    await afterPage.locator("#user_login").fill(resetUser.login);
    await afterPage.locator("#user_pass").fill(resetUser.pass);
    await afterPage.locator("#wp-submit").click();

    await expect(afterPage.locator("#login_error")).toContainText(
      "The password you entered for the username sucuri-reset is incorrect.",
    );
    await afterContext.close();
  });
});
