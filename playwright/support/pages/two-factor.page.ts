/**
 * Page object + helpers for the Two-Factor Authentication suite.
 *
 * Two surfaces are involved:
 *  - the admin policy page (admin.php?page=sucuriscan_2fa) with the bulk-control
 *    dropdown — wrapped by {@link TwoFactorAdminPage};
 *  - the wp-login 2FA challenge/setup screens — covered by the standalone
 *    challenge helpers below, which operate on a fresh (logged-out) page.
 *
 * Per-user selection targets the stable `twofactor-user-checkbox-<id>` test id
 * (IDs resolved via WP-CLI) rather than matching row text, because the test
 * logins (`sucuri`, `sucuri-admin`, `sucuri-reset`) are substrings of one another.
 */
import { expect, type Page } from "@playwright/test";
import { type WpUser } from "../env";
import { getUserId } from "../wp-cli";
import { totp } from "../totp";

export type BulkMode =
  | "activate_all"
  | "activate_selected"
  | "deactivate_all"
  | "deactivate_selected"
  | "reset_selected"
  | "reset_all"
  | "reset_everything";

const SECRET_CODE = "code.sucuriscan-2fa-secret-code";
const TOTP_INPUT = "#sucuriscan-totp-code";
const TOTP_SUBMIT = "#sucuriscan-totp-submit";

export class TwoFactorAdminPage {
  constructor(private readonly page: Page) {}

  async goto(): Promise<void> {
    await this.page.goto("/wp-admin/admin.php?page=sucuriscan_2fa");
  }

  private get bulkSelect() {
    return this.page.getByTestId("sucuriscan_twofactor_bulk_dropdown");
  }

  private get bulkSubmit() {
    return this.page.getByTestId("sucuriscan_twofactor_bulk_submit_btn");
  }

  /** Select a bulk mode and apply it (submits the form, a full-page POST + reload). */
  async applyBulk(mode: BulkMode): Promise<void> {
    await this.bulkSelect.selectOption(mode);
    await this.bulkSubmit.click();
  }

  /** Wait for the post-submit "Two-Factor" notice — proves the bulk reload has landed. */
  private async expectBulkNotice(): Promise<void> {
    await expect(
      this.page.locator(".sucuriscan-alert, .updated, .notice", {
        hasText: "Two-Factor",
      }),
    ).toBeVisible();
  }

  /** Tick the selection checkbox for each user (by resolved user ID). */
  async selectUsers(users: WpUser[]): Promise<void> {
    for (const user of users) {
      const id = getUserId(user.login);
      // Plain check() (not force): it waits for the box to be stable/actionable
      // and is a no-op if already checked. force:true skips both, which surfaced as
      // "clicking the checkbox did not change its state" when a click landed during
      // the post-bulk reload.
      await this.page.getByTestId(`twofactor-user-checkbox-${id}`).check();
    }
  }

  /** Enforce/disable/reset 2FA for every user; asserts the Two-Factor notice. */
  async setModeAllUsers(mode: BulkMode = "activate_all"): Promise<void> {
    await this.goto();
    await this.applyBulk(mode);
    await this.expectBulkNotice();
  }

  /** Clear the slate, then enforce a "selected users" mode for the given users. */
  async setModeSelectedUsersFor(
    users: WpUser[],
    mode: BulkMode = "activate_selected",
  ): Promise<void> {
    await this.goto();
    // Clear any prior selection state the way the Cypress helper did.
    await this.page.getByTestId("twofactor-user-checkbox-1").check();
    await this.applyBulk("deactivate_all");
    // Wait for the deactivate reload to land before selecting users: otherwise the
    // checks below run on the pre-reload page and are discarded, so the second
    // submit enforces an empty selection (the user then logs in with no challenge).
    await this.expectBulkNotice();

    await this.selectUsers(users);
    await this.applyBulk(mode);
    // Wait for the enforce reload to land before returning. applyBulk only
    // clicks submit (click() does not await the POST/reload), so without this
    // the helper returns while the option write is still in flight. Callers
    // immediately open a fresh context and log in; if that login beats the
    // `twofactor_mode=selected_users` write, the user isn't enforced and lands
    // on wp-admin instead of the 2FA screen — the observed CI flake.
    await this.expectBulkNotice();
  }

  /** Reset (force re-setup) 2FA for the given users without disabling enforcement. */
  async resetForSelectedUsers(users: WpUser[]): Promise<void> {
    await this.goto();
    await this.selectUsers(users);
    await this.applyBulk("reset_selected");
    // Same reasoning as setModeSelectedUsersFor: wait for the reload so the
    // secret-wipe write completes before callers open a fresh context and log
    // in expecting the SETUP screen. Otherwise a still-stored secret sends the
    // login to VERIFY and the assertion fails.
    await this.expectBulkNotice();
  }

  /** Canonical full reset: wipe every secret and disable enforcement. Safe teardown. */
  async resetEverything(): Promise<void> {
    await this.goto();
    await this.applyBulk("reset_everything");
    await expect(
      this.page.locator(".sucuriscan-alert, .updated, .notice", {
        hasText: "All Two-Factor data deleted",
      }),
    ).toBeVisible();
  }
}

/** Whether a login attempt should land on the first-time setup screen or the verify screen. */
export type ChallengeKind = "setup" | "verify";

/** Assert the wp-login page is showing the expected 2FA challenge/setup screen. */
export async function expectChallenge(
  page: Page,
  kind: ChallengeKind,
): Promise<void> {
  if (kind === "setup") {
    await expect(page).toHaveURL(/action=sucuri-2fa-setup/);
    // login_header() renders the title as <h1 class="screen-reader-text"> AND an
    // intro <p class="message"> whose text "Set up two-factor authentication…" also
    // contains the title, so a plain getByText matches both (strict-mode violation).
    // Target the heading role to uniquely select the screen-reader title.
    await expect(
      page.getByRole("heading", { name: "Set up Two-Factor Authentication" }),
    ).toBeVisible();
  } else {
    // `action=sucuri-2fa` (NOT `-setup`) is the verify screen.
    await expect(page).toHaveURL(/action=sucuri-2fa(?!-setup)/);
    await expect(page).not.toHaveURL(/action=sucuri-2fa-setup/);
    await expect(page.locator(TOTP_INPUT)).toBeVisible();
    await expect(page.locator(TOTP_SUBMIT)).toBeVisible();
  }
}

/**
 * Submit the wp-login form for `user` and leave the page on its settled landing:
 * a 2FA challenge (action=sucuri-2fa[-setup]) or wp-admin.
 *
 * Reaching either depends on a server redirect — for enforced users one that mints
 * a one-time login token. Very occasionally the first POST bounces straight back to
 * a bare wp-login.php (no `action=`, no error) instead, a transient login/token
 * race that clears on a re-submit, so retry a few times. The two TERMINAL outcomes
 * are never retried: a real credential error (#login_error) or a settled landing —
 * callers assert the specific expected screen afterward, so a genuine wrong landing
 * still surfaces there rather than being masked.
 */
export async function submitLoginResilient(
  page: Page,
  user: WpUser,
): Promise<void> {
  const maxAttempts = 3;
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    await page.goto("/wp-login.php");
    await page.locator("#user_login").fill(user.login);
    await page.locator("#user_pass").fill(user.pass);
    await page.locator("#wp-submit").click();

    // waitForURL (not waitForNavigation, which can miss a fast redirect and hang)
    // settles on the challenge or wp-admin.
    try {
      await page.waitForURL(/action=sucuri-2fa|\/wp-admin\//, {
        timeout: 10_000,
      });
      return; // settled on a challenge or wp-admin
    } catch {
      if ((await page.locator("#login_error").count()) > 0) return; // real auth error
      // else: bounced to a bare wp-login.php — retry unless this was the last try.
    }
  }
}

/** Submit the login form (on a logged-out page) and assert the resulting 2FA screen. */
export async function loginExpect2FA(
  page: Page,
  user: WpUser,
  kind: ChallengeKind,
): Promise<void> {
  await submitLoginResilient(page, user);
  await expectChallenge(page, kind);
}

/** Read the base32 secret printed on the setup screen. */
export async function extractSecret(page: Page): Promise<string> {
  return (await page.locator(SECRET_CODE).first().innerText()).trim();
}

/** Fill and submit a TOTP code on a setup/verify screen. */
export async function finishWithCode(page: Page, code: string): Promise<void> {
  await page.locator(TOTP_INPUT).fill(code);
  await page.locator(TOTP_SUBMIT).click();
}

/**
 * Complete a first-time setup: read the secret, compute a valid code, submit it,
 * assert the user reaches wp-admin, and return the secret for later reuse.
 */
export async function completeSetupWithGeneratedCode(
  page: Page,
): Promise<string> {
  const secret = await extractSecret(page);
  const code = totp(secret);
  expect(code).toMatch(/^\d{6}$/);
  await finishWithCode(page, code);
  await expect(page).toHaveURL(/\/wp-admin\//);
  return secret;
}
