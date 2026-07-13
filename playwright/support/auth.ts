/**
 * WordPress login helpers — the Playwright equivalent of the Cypress
 * `cy.login` custom command. The bulk of the suite reuses a saved admin
 * storageState (see global.setup.ts) instead of logging in per test; these
 * helpers are for flows that must authenticate fresh (e.g. 2FA challenges).
 */
import fs from "node:fs";
import path from "node:path";
import {
  expect,
  type Browser,
  type BrowserContext,
  type Page,
} from "@playwright/test";
import {
  ADMIN_STORAGE_STATE,
  BASE_URL,
  WAF_DISMISS_COOKIE,
  adminUser,
  type WpUser,
} from "./env";

/** Seed the WAF-dismiss cookie so the dashboard activation modal never blocks clicks. */
export async function addWafDismissCookie(
  context: BrowserContext,
  value = "1",
): Promise<void> {
  await context.addCookies([
    { name: WAF_DISMISS_COOKIE, value, url: BASE_URL },
  ]);
}

/**
 * Fill and submit the wp-login form, asserting the user lands in wp-admin.
 * Use only for accounts NOT behind a 2FA challenge.
 */
export async function login(page: Page, user: WpUser): Promise<void> {
  await page.goto("/wp-login.php");
  await page.locator("#user_login").fill(user.login);
  await page.locator("#user_pass").fill(user.pass);
  await page.locator("#wp-submit").click();
  await expect(page).toHaveURL(/\/wp-admin\//);
}

/**
 * Submit the wp-login form without asserting the destination — for flows where
 * a 2FA challenge/setup screen is expected instead of the dashboard.
 */
export async function submitLogin(page: Page, user: WpUser): Promise<void> {
  await page.goto("/wp-login.php");
  await page.locator("#user_login").fill(user.login);
  await page.locator("#user_pass").fill(user.pass);
  await page.locator("#wp-submit").click();
}

/**
 * Log in as admin in a fresh context and (re)write the admin storageState file.
 * Used by the setup project and by any spec that invalidates the admin session
 * (e.g. rotating the WordPress secret keys), so later specs re-read valid cookies.
 */
export async function saveAdminStorageState(browser: Browser): Promise<void> {
  const context = await browser.newContext();
  await addWafDismissCookie(context);
  const page = await context.newPage();
  await login(page, adminUser);
  fs.mkdirSync(path.dirname(ADMIN_STORAGE_STATE), { recursive: true });
  await context.storageState({ path: ADMIN_STORAGE_STATE });
  await context.close();
}
