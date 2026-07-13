/**
 * Central environment + test-data configuration for the Playwright e2e suite.
 *
 * Values mirror the old `cypress.config.js.example` env block and the users
 * created by `tests/e2e-prepare.sh`. Everything is overridable via process.env
 * so the same suite runs locally and in CI without code changes.
 */

/** Base URL of the wp-env WordPress instance (no trailing slash). */
export const BASE_URL = (
  process.env.SUCURI_BASE_URL || "http://localhost:8889"
).replace(/\/$/, "");

const baseUrl = new URL(BASE_URL);
const localHosts = new Set(["localhost", "127.0.0.1", "::1"]);

if (!localHosts.has(baseUrl.hostname)) {
  throw new Error(
    `Refusing to run destructive E2E tests against ${baseUrl.hostname}; ` +
      "this suite can only clean up the local wp-env instance.",
  );
}

/** Host used for cookies (matches BASE_URL host without scheme/port handling done by Playwright). */
export const BASE_HOST = new URL(BASE_URL).host;

export interface WpUser {
  login: string;
  pass: string;
}

/** Default wp-env administrator. */
export const adminUser: WpUser = {
  login: process.env.WP_USER || "admin",
  pass: process.env.WP_PASS || "password",
};

/** Secondary administrator created by tests/e2e-prepare.sh (`sucuri-admin`). */
export const testAdminUser: WpUser = {
  login: process.env.TEST_ADMIN_USER || "sucuri-admin",
  pass: process.env.TEST_ADMIN_PASS || "password",
};

/** Non-admin author created by tests/e2e-prepare.sh (`sucuri`). */
export const extraUser: WpUser = {
  login: process.env.EXTRA_USER || "sucuri",
  pass: process.env.EXTRA_USER_PASS || "password",
};

/** Author whose password is reset by the post-hack reset-password test (`sucuri-reset`). */
export const resetUser: WpUser = {
  login: process.env.RESET_USER || "sucuri-reset",
  pass: process.env.RESET_USER_PASS || "password",
};

/** Cookie the plugin reads to suppress the "activate your WAF key" dashboard modal. */
export const WAF_DISMISS_COOKIE = "sucuriscan_waf_dismissed";

/**
 * Plugin directory name inside the wp-env container. `.wp-env.json` mounts the
 * repo via `plugins: ["."]`, so the directory name equals the repo folder name.
 * NOTE: the old Cypress salt seed defaulted to `sucuri-wordpress-plugin`, which
 * was wrong for this repo — keep this as `sucuri-scanner`.
 */
export const PLUGIN_SLUG = process.env.PLUGIN_SLUG || "sucuri-scanner";

/** Storage-state file holding the authenticated admin session. */
export const ADMIN_STORAGE_STATE = "playwright/.auth/admin.json";

/** Path (inside the container, relative to the WP docroot) of the plugin settings file. */
export const SETTINGS_FILE_PATH =
  "wp-content/uploads/sucuri/sucuri-settings.php";
