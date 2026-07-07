/**
 * `setup` project: provisions the named test users (idempotent safety net on
 * top of tests/e2e-prepare.sh) and captures an authenticated admin session as
 * a reusable storageState. Every functional project depends on this, so the
 * bulk of the suite never re-runs the UI login (the main speedup over Cypress's
 * per-test `cy.session`).
 */
import { test as setup } from "@playwright/test";
import { extraUser, resetUser, testAdminUser } from "./env";
import { saveAdminStorageState } from "./auth";
import { ensureUser } from "./wp-cli";

setup("provision test users", async () => {
  // e2e-prepare.sh normally creates these; re-assert idempotently so the suite
  // can run against an environment that was only `wp-env start`-ed.
  ensureUser(
    testAdminUser.login,
    `${testAdminUser.login}@sucuri.net`,
    "administrator",
    testAdminUser.pass,
  );
  ensureUser(
    extraUser.login,
    `${extraUser.login}@sucuri.net`,
    "author",
    extraUser.pass,
  );
  ensureUser(
    resetUser.login,
    `${resetUser.login}@sucuri.net`,
    "author",
    resetUser.pass,
  );
});

setup("authenticate admin", async ({ browser }) => {
  // Bakes the WAF-dismiss cookie into the saved state so the dashboard modal
  // never blocks clicks in specs that visit page=sucuriscan. The waf-modal spec
  // deliberately overrides that cookie to '0' in its own context.
  await saveAdminStorageState(browser);
});
