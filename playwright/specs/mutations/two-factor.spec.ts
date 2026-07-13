/**
 * Two-Factor Authentication (TOTP) — the full describe block from the Cypress
 * suite: admin bulk-policy controls (admin.php?page=sucuriscan_2fa), the
 * wp-login challenge/setup flows (action=sucuri-2fa / -setup), the profile-page
 * reset, dashboard self-enrollment with enforce-all, the 5-attempt lockout, and
 * replay rejection.
 *
 * HIGHEST-RISK suite: tests enforce 2FA on the SHARED default admin (id 1). If a
 * test fails mid-flow direct before/after cleanup disables enforcement, removes
 * all TOTP metadata/login transients, and restores named-user session metadata.
 *
 * Bulk-policy actions reuse the inherited admin `page` (its auth cookies were
 * captured before any enforcement and stay valid — enforcement only gates fresh
 * logins). Per-user login challenges run in FRESH browser contexts (one page per
 * user), the equivalent of the Cypress clearAllSavedSessions/clearCookies.
 *
 * TOTP codes are computed immediately before submitting; the plugin's ±2 step
 * allowance covers the round-trip. The replay test deliberately REUSES the exact
 * code string from setup (no recompute) so it still proves replay rejection.
 */
import { test, expect } from "../../support/fixtures";
import type { Browser, Page } from "@playwright/test";
import {
  TwoFactorAdminPage,
  loginExpect2FA,
  submitLoginResilient,
  extractSecret,
  finishWithCode,
  completeSetupWithGeneratedCode,
} from "../../support/pages/two-factor.page";
import { totp } from "../../support/totp";
import { addWafDismissCookie } from "../../support/auth";
import {
  restoreAllUserMeta,
  restorePluginData,
  restoreRawOptionsByPrefix,
  snapshotAllUserMeta,
  snapshotPluginData,
  snapshotRawOptionsByPrefix,
  wpEval,
  type AllUserMetaSnapshot,
  type PluginDataSnapshot,
  type RawOptionSnapshot,
} from "../../support/wp-cli";
import {
  adminUser,
  testAdminUser,
  extraUser,
  type WpUser,
} from "../../support/env";

const TWO_FACTOR_USERS = "[data-cy=sucuriscan_twofactor_users_response]";
const TWO_FACTOR_USER_CHECKBOX =
  'input[name="sucuriscan_twofactor_users[]"]';
let pluginData: PluginDataSnapshot;
let createdBulkUsers: number[] = [];
let userMeta: AllUserMetaSnapshot;
let loginTransients: Map<string, RawOptionSnapshot | null>;
const TRANSIENT_PREFIXES = [
  "_transient_sucuri_2fa_",
  "_transient_timeout_sucuri_2fa_",
] as const;
const USER_META_KEYS = [
  "sucuriscan_topt_secret_key",
  "sucuriscan_topt_last_success",
  "session_tokens",
] as const;

test.use({ preservePluginData: false });

function invalidTotp(secret: string): string {
  const now = Date.now();
  const validCodes = new Set(
    Array.from({ length: 9 }, (_, index) =>
      totp(secret, 30, 6, now + (index - 4) * 30_000),
    ),
  );
  for (let value = 0; value < 1_000_000; value++) {
    const candidate = value.toString().padStart(6, "0");
    if (!validCodes.has(candidate)) return candidate;
  }
  throw new Error("Could not generate an invalid TOTP code");
}

async function waitForUsersTable(page: Page): Promise<void> {
  await expect(
    page.locator(TWO_FACTOR_USERS).locator(TWO_FACTOR_USER_CHECKBOX).first(),
  ).toBeVisible();
}

function resetTwoFactorState(): void {
  wpEval(
    "SucuriScanOption::updateOption(':twofactor_mode','disabled');" +
      "SucuriScanOption::updateOption(':twofactor_users',array());" +
      '$users=get_users(array("fields"=>"ID"));foreach($users as $uid){' +
      "delete_user_meta($uid,'sucuriscan_topt_secret_key');" +
      "delete_user_meta($uid,'sucuriscan_topt_last_success');}" +
      'global $wpdb;' +
      '$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE \'_transient_sucuri_2fa_%\' OR option_name LIKE \'_transient_timeout_sucuri_2fa_%\'");' +
      `foreach(array(${JSON.stringify(testAdminUser.login)},${JSON.stringify(extraUser.login)}) as $login){` +
      '$user=get_user_by("login",$login);if($user){WP_Session_Tokens::get_instance($user->ID)->destroy_all();}}',
  );
}

function ensureBulkUsers(): void {
  createdBulkUsers = JSON.parse(
    wpEval(
      '$created=array();' +
        'for($i=1;$i<=60;$i++){$login=sprintf("bulkuser-%03d",$i);' +
        '$user=get_user_by("login",$login);if(!$user){wp_insert_user(array(' +
        '"user_login"=>$login,"user_email"=>$login."@sucuri.net",' +
        '"user_pass"=>"password","role"=>"subscriber"));$user=get_user_by("login",$login);$created[]=$user->ID;}}' +
        'echo wp_json_encode($created);',
    ),
  ) as number[];
}

/**
 * Run a wp-login flow for `user` in an isolated context (fresh cookies/storage),
 * mirroring Cypress's per-user `cy.session` + clearAllSavedSessions. The WAF
 * dismiss cookie is seeded so the activation modal never blocks the form.
 */
async function withFreshUser(
  browser: Browser,
  fn: (page: Page) => Promise<void>,
): Promise<void> {
  const context = await browser.newContext({
    storageState: { cookies: [], origins: [] },
  });
  await addWafDismissCookie(context);
  const page = await context.newPage();
  try {
    await fn(page);
  } finally {
    await context.close();
  }
}

/**
 * Submit the wp-login form for a user on a fresh page (no challenge assertion).
 * Uses the shared resilient submit so a transient login bounce back to a bare
 * wp-login.php is retried; callers still assert the expected landing themselves.
 */
async function submitLoginRaw(page: Page, user: WpUser): Promise<void> {
  await submitLoginResilient(page, user);
}

test.describe("Two-Factor Authentication", () => {
  test.beforeEach(() => {
    createdBulkUsers = [];
    pluginData = snapshotPluginData();
    userMeta = snapshotAllUserMeta(USER_META_KEYS);
    loginTransients = snapshotRawOptionsByPrefix(TRANSIENT_PREFIXES);
    resetTwoFactorState();
  });

  // ALWAYS-RUNS safety net: fully reset 2FA after every test so the shared admin
  // (id 1) is never left locked behind a challenge for the next test/spec.
  test.afterEach(() => {
    if (createdBulkUsers.length) {
      wpEval(
        `foreach(array(${createdBulkUsers.join(",")}) as $uid){wp_delete_user($uid);}`,
      );
    }
    restorePluginData(pluginData);
    restoreAllUserMeta(userMeta, USER_META_KEYS);
    restoreRawOptionsByPrefix(TRANSIENT_PREFIXES, loginTransients);
  });

  // Belt-and-braces fallback: force disabled mode and drop every relevant
  // user's TOTP meta directly, in case the UI teardown above ever fails.
  test("enforces 2FA for all users and completes verify with a valid code", async ({
    page,
    browser,
  }) => {
    const admin2fa = new TwoFactorAdminPage(page);

    await admin2fa.setModeAllUsers("activate_all");

    // Force re-setup for testAdminUser only (clears any leftover secret).
    await admin2fa.resetForSelectedUsers([testAdminUser]);

    // First login: no secret yet -> SETUP screen; complete it -> wp-admin.
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, testAdminUser, "setup");
      await completeSetupWithGeneratedCode(p);
    });

    // Second login: secret now stored -> VERIFY screen.
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, testAdminUser, "verify");
    });

    // extraUser has no secret -> still SETUP.
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, extraUser, "setup");
    });

    // In-test cleanup (afterEach also resets everything).
    await admin2fa.setModeAllUsers("deactivate_all");
    await admin2fa.resetForSelectedUsers([testAdminUser]);
  });

  test("enforces 2FA for selected users and completes setup for a non-admin user", async ({
    page,
    browser,
  }) => {
    const admin2fa = new TwoFactorAdminPage(page);

    await admin2fa.setModeSelectedUsersFor([extraUser], "activate_selected");

    // extraUser SETUP + complete.
    let secret = "";
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, extraUser, "setup");
      secret = await completeSetupWithGeneratedCode(p);
    });

    // extraUser VERIFY, then submit a wrong code -> Invalid error.
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, extraUser, "verify");
      await finishWithCode(p, invalidTotp(secret));
      await expect(p.locator("#login_error")).toContainText("Invalid");
    });

    await admin2fa.setModeAllUsers("deactivate_all");
    await admin2fa.resetForSelectedUsers([extraUser]);

    // Confirm login works again post-reset (extraUser bypasses challenge).
    await withFreshUser(browser, async (p) => {
      await submitLoginRaw(p, extraUser);
      await expect(p).toHaveURL(/\/wp-admin\//);
    });
  });

  test("resets 2FA from Profile page for non-admin user", async ({
    page,
    browser,
  }) => {
    const admin2fa = new TwoFactorAdminPage(page);

    await admin2fa.setModeSelectedUsersFor([extraUser], "activate_selected");

    // extraUser SETUP + complete, then reset from the profile page (still in
    // the SAME logged-in context, so the profile 2FA section renders).
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, extraUser, "setup");
      await completeSetupWithGeneratedCode(p);

      await p.goto("/wp-admin/profile.php");
      await expect(p.getByTestId("sucuriscan-2fa-status-text")).toContainText(
        "Two-Factor Authentication is enabled for this account.",
      );

      // The confirm dialog must be accepted; register the handler BEFORE the click.
      p.on("dialog", async (dialog) => {
        expect(dialog.message()).toContain(
          "This will disable two-factor for this user. Continue?",
        );
        await dialog.accept();
      });

      // The reset fires an admin-ajax POST that swaps in a fresh setup snippet.
      await Promise.all([
        p.waitForResponse(
          (r) =>
            r.url().includes("admin-ajax.php") &&
            (r.request().postData() ?? "").includes("sucuri_profile_2fa_reset"),
        ),
        p.getByTestId("sucuriscan-2fa-reset-btn").click(),
      ]);

      // The reset swaps in the profile setup snippet (profile-2fa-setup.snippet.tpl),
      // whose secret is a PLAIN <code> with no class (the sucuriscan-2fa-secret-code
      // class is dashboard-only). Scope to that snippet's code — mirrors the Cypress
      // `cy.get('code').first()` check while staying within the freshly-rendered view.
      await expect(
        p.locator(".sucuriscan-profile-2fa-setup code").first(),
      ).toBeVisible();
      await expect(p.locator("#sucuriscan-topt-qr")).toBeVisible();
    });

    // Cleanup as admin (afterEach also resets everything).
    await admin2fa.setModeAllUsers("reset_all");
  });

  test("reset_selected forces re-setup only for the chosen user (selected mode)", async ({
    page,
    browser,
  }) => {
    const admin2fa = new TwoFactorAdminPage(page);

    await admin2fa.setModeSelectedUsersFor(
      [extraUser, testAdminUser],
      "activate_selected",
    );

    // Both users complete their first-time setup.
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, extraUser, "setup");
      await completeSetupWithGeneratedCode(p);
    });
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, testAdminUser, "setup");
      await completeSetupWithGeneratedCode(p);
    });

    // Reset only extraUser; testAdminUser's secret is untouched.
    await admin2fa.resetForSelectedUsers([extraUser]);

    // extraUser back to SETUP; testAdminUser still VERIFY.
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, extraUser, "setup");
    });
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, testAdminUser, "verify");
    });

    await admin2fa.setModeAllUsers("reset_all");
  });

  test("non-selected user bypasses 2FA when only another user is enforced", async ({
    page,
    browser,
  }) => {
    const admin2fa = new TwoFactorAdminPage(page);

    await admin2fa.setModeAllUsers("reset_all");
    await admin2fa.setModeSelectedUsersFor([extraUser], "activate_selected");

    // admin (id 1) is NOT in the selected list -> logs in normally, no challenge.
    await withFreshUser(browser, async (p) => {
      await submitLoginRaw(p, adminUser);
      await expect(p).toHaveURL(/\/wp-admin\//);
      await expect(p).not.toHaveURL(/sucuri-2fa/);
    });

    // extraUser IS enforced -> SETUP.
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, extraUser, "setup");
    });

    // NOTE: the Cypress original omitted cleanup here, leaving extraUser
    // enforced; the afterEach resetEverything below covers it.
  });

  test("activates 2fa for all users and disables it again", async ({
    page,
    browser,
  }) => {
    const admin2fa = new TwoFactorAdminPage(page);

    await admin2fa.setModeAllUsers("activate_all");

    // admin has no secret yet, so the first enforced login lands on SETUP.
    // (The Cypress helper's default 'verify' assertion passed only because its
    // substring checks also match the setup screen; here we assert precisely.)
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, adminUser, "setup");
      await completeSetupWithGeneratedCode(p);
    });

    // In-test cleanup: disable enforcement and wipe the admin's secret.
    await admin2fa.setModeAllUsers("deactivate_all");
    await admin2fa.resetForSelectedUsers([adminUser]);

    // admin logs in normally again.
    await withFreshUser(browser, async (p) => {
      await submitLoginRaw(p, adminUser);
      await expect(p).toHaveURL(/\/wp-admin\//);
    });
  });

  test("locks out after 5 invalid verification attempts", async ({
    page,
    browser,
  }) => {
    const admin2fa = new TwoFactorAdminPage(page);

    await admin2fa.setModeAllUsers("reset_all");
    await admin2fa.setModeSelectedUsersFor([extraUser], "activate_selected");

    // First, complete setup so the next login hits the VERIFY screen.
    let secret = "";
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, extraUser, "setup");
      secret = await completeSetupWithGeneratedCode(p);
    });

    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, extraUser, "verify");

      // Five invalid attempts. Each POST re-renders the form, so re-locate the
      // input every iteration; the first four keep us on the verify screen with
      // an Invalid error, the fifth clears the transient and kicks us back to
      // the plain wp-login.php.
      for (let i = 0; i < 5; i++) {
        await p
          .locator("#sucuriscan-totp-code")
          .fill(invalidTotp(secret));
        await p.locator("#sucuriscan-totp-submit").click();

        if (i < 4) {
          await expect(p.locator("#login_error")).toContainText("Invalid");
          await expect(p).toHaveURL(/action=sucuri-2fa/);
        }
      }

      // After the 5th: back to standard login, no 2fa action param.
      await expect(p).toHaveURL(/wp-login\.php/);
      await expect(p).not.toHaveURL(/action=sucuri-2fa/);
      await expect(p.locator("#user_login")).toBeVisible();
    });

    await admin2fa.setModeAllUsers("reset_all");
  });

  test("verify flow rejects a replayed TOTP code in same timestep", async ({
    page,
    browser,
  }) => {
    const admin2fa = new TwoFactorAdminPage(page);

    await admin2fa.setModeSelectedUsersFor(
      [testAdminUser],
      "activate_selected",
    );

    // Complete setup with code X, then reuse the EXACT same code X on verify.
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, testAdminUser, "setup");
      const secret = await extractSecret(p);
      const code = totp(secret);
      await finishWithCode(p, code);
      await expect(p).toHaveURL(/\/wp-admin\//);

      // Fresh login -> VERIFY, then replay the same code (do NOT recompute).
      await loginExpect2FA(p, testAdminUser, "verify");
      await finishWithCode(p, code);

      await expect(p.locator("#login_error")).toContainText("Invalid");
      await expect(p).toHaveURL(/action=sucuri-2fa/);
    });

    await admin2fa.setModeAllUsers("deactivate_all");
  });

  test("reset_everything wipes all 2FA secrets and disables enforcement (no challenges after)", async ({
    page,
    browser,
  }) => {
    const admin2fa = new TwoFactorAdminPage(page);

    await admin2fa.setModeAllUsers("reset_all");
    await admin2fa.setModeAllUsers("activate_all");

    // Both users enroll.
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, testAdminUser, "setup");
      await completeSetupWithGeneratedCode(p);
    });
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, extraUser, "setup");
      await completeSetupWithGeneratedCode(p);
    });

    // reset_everything: wipe all secrets + disable enforcement.
    await admin2fa.goto();
    await page
      .getByTestId("sucuriscan_twofactor_bulk_dropdown")
      .selectOption("reset_everything");
    await page.getByTestId("sucuriscan_twofactor_bulk_submit_btn").click();
    await expect(
      page.locator(".sucuriscan-alert, .updated, .notice", {
        hasText: "All Two-Factor data deleted",
      }),
    ).toBeVisible();

    // Both users now log in normally, with no sucuri-2fa challenge.
    for (const user of [testAdminUser, extraUser]) {
      await withFreshUser(browser, async (p) => {
        await submitLoginRaw(p, user);
        await expect(p).toHaveURL(/\/wp-admin\//);
        await expect(p).not.toHaveURL(/sucuri-2fa/);
      });
    }

    // The policy page now reports Deactivated.
    await admin2fa.goto();
    await expect(page.getByText("Deactivated").first()).toBeVisible();
  });

  test("enforces 2FA for all users from sucuri dashboard", async ({
    page,
    browser,
  }) => {
    const admin2fa = new TwoFactorAdminPage(page);

    await admin2fa.setModeAllUsers("reset_all");

    // Self-enroll from the dashboard: tick "enforce for all", compute a valid
    // code from the displayed secret, submit. Unlike the login flow, the
    // dashboard submit is an admin-ajax `totp_verify` POST followed by an
    // in-page reload (no redirect), so we wait on the AJAX rather than a URL
    // change, then confirm the page reloaded into the enrolled wp-admin view.
    await admin2fa.goto();
    const secret = await extractSecret(page);
    const code = totp(secret);
    expect(code).toMatch(/^\d{6}$/);
    await page
      .locator('input[name="sucuriscan_2fa_enforce_all"]')
      .check({ force: true });
    await Promise.all([
      page.waitForResponse(
        (r) =>
          r.url().includes("admin-ajax.php") &&
          (r.request().postData() ?? "").includes("totp_verify"),
      ),
      finishWithCode(page, code),
    ]);
    await expect(page).toHaveURL(/\/wp-admin\//);

    // The Cypress original ran this logged in AS the enrolling user, so that
    // user hit VERIFY and the rest SETUP. The Playwright suite's inherited
    // session is the default `admin` (id 1), so admin is the enrollee here:
    // admin -> VERIFY (secret now stored), the other two -> SETUP (no secret).
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, adminUser, "verify");
    });
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, testAdminUser, "setup");
    });
    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, extraUser, "setup");
    });
  });

  test("loads a bounded, paginated users table", async ({ page }) => {
    ensureBulkUsers();
    await new TwoFactorAdminPage(page).goto();
    await waitForUsersTable(page);

    const visibleUsers = page
      .locator(TWO_FACTOR_USERS)
      .locator(TWO_FACTOR_USER_CHECKBOX);
    expect(await visibleUsers.count()).toBeLessThanOrEqual(25);
    await expect(
      page.locator(".sucuriscan-2fa-pagination-panel"),
    ).not.toHaveClass(/sucuriscan-hidden/);
    await expect(
      page.locator(".sucuriscan-2fa-pagination .sucuriscan-pagination-link"),
    ).not.toHaveCount(0);
  });

  test("loads a different 2FA users page through AJAX", async ({ page }) => {
    ensureBulkUsers();
    await new TwoFactorAdminPage(page).goto();
    await waitForUsersTable(page);

    const firstUser = page
      .locator(TWO_FACTOR_USERS)
      .locator(TWO_FACTOR_USER_CHECKBOX)
      .first();
    const firstPageUserId = await firstUser.inputValue();

    await page
      .locator(
        '.sucuriscan-2fa-pagination .sucuriscan-pagination-link[data-page="2"]',
      )
      .click();
    await expect(firstUser).not.toHaveValue(firstPageUserId);
  });

  test("searches and clears the 2FA users table", async ({ page }) => {
    ensureBulkUsers();
    await new TwoFactorAdminPage(page).goto();
    await waitForUsersTable(page);

    await page.getByTestId("sucuriscan_twofactor_search").fill("bulkuser-055");
    await page.getByTestId("sucuriscan_twofactor_search_btn").click();
    await expect(page.locator(TWO_FACTOR_USERS)).toContainText("bulkuser-055");
    await expect(
      page.locator(TWO_FACTOR_USERS).locator(TWO_FACTOR_USER_CHECKBOX),
    ).toHaveCount(1);

    await page.getByTestId("sucuriscan_twofactor_search_clear").click();
    await expect(page.getByTestId("sucuriscan_twofactor_search")).toHaveValue("");
    await expect
      .poll(() =>
        page.locator(TWO_FACTOR_USERS).locator(TWO_FACTOR_USER_CHECKBOX).count(),
      )
      .toBeGreaterThan(1);
  });

  test("applies a selection from the AJAX-rendered users table", async ({
    page,
    browser,
  }) => {
    ensureBulkUsers();
    const admin2fa = new TwoFactorAdminPage(page);

    await admin2fa.resetEverything();
    await admin2fa.goto();
    await waitForUsersTable(page);
    await expect(
      page.locator(".sucuriscan-2fa-pagination-panel"),
    ).not.toHaveClass(/sucuriscan-hidden/);
    await admin2fa.selectUsers([testAdminUser]);
    await admin2fa.applyBulk("activate_selected");
    await expect(
      page.locator(".sucuriscan-alert, .updated, .notice", {
        hasText: "Two-Factor",
      }),
    ).toBeVisible();

    await withFreshUser(browser, async (p) => {
      await loginExpect2FA(p, testAdminUser, "setup");
    });
  });
});
