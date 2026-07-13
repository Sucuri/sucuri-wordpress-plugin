/**
 * `setup` project: provisions the named test users (idempotent safety net on
 * top of tests/e2e-prepare.sh) and captures an authenticated admin session as
 * a reusable storageState. Every functional project depends on this, so the
 * bulk of the suite never re-runs the UI login (the main speedup over Cypress's
 * per-test `cy.session`).
 */
import { test as setup } from "./fixtures";
import {
  adminUser,
  extraUser,
  PLUGIN_SLUG,
  resetUser,
  testAdminUser,
} from "./env";
import { saveAdminStorageState } from "./auth";
import {
  ensureUser,
  restorePluginData,
  snapshotPluginData,
  wp,
  wpEval,
} from "./wp-cli";

setup.use({ preservePluginData: false });

setup("provision users and authenticate admin", async ({ browser }) => {
  const pluginData = snapshotPluginData(false);
  try {
    // Recover if an interrupted plugin-lifecycle test left Sucuri deactivated.
    wp("plugin", "activate", PLUGIN_SLUG);

    // Repair the configured administrator without depending on a clean database.
    try {
      wp(
        "user",
        "update",
        adminUser.login,
        "--role=administrator",
        `--user_pass=${adminUser.pass}`,
      );
    } catch {
      wp(
        "user",
        "create",
        adminUser.login,
        `${adminUser.login}@example.com`,
        "--role=administrator",
        `--user_pass=${adminUser.pass}`,
      );
    }

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

    // Temporarily disable enforcement to create reusable auth without erasing any
    // user's enrollment. Restore the exact plugin datastore immediately after.
    wpEval("SucuriScanOption::updateOption(':twofactor_mode','disabled');");
    // Bakes the WAF-dismiss cookie into the saved state so the dashboard modal
    // never blocks clicks. The waf-modal spec overrides it to '0'.
    await saveAdminStorageState(browser);
  } finally {
    restorePluginData(pluginData);
  }
});
