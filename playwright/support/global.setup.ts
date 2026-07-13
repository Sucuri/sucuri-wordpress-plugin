/**
 * `setup` project: provisions the named test users (idempotent safety net on
 * top of tests/e2e-prepare.sh) and captures an authenticated admin session as
 * a reusable storageState. Every functional project depends on this, so the
 * bulk of the suite never re-runs the UI login (the main speedup over Cypress's
 * per-test `cy.session`).
 */
import { test as setup } from "@playwright/test";
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
  getUserId,
  runPluginScript,
  wp,
  wpEval,
} from "./wp-cli";

setup("provision test users", async () => {
  // Recover if an interrupted plugin-lifecycle test left Sucuri deactivated.
  wp("plugin", "activate", PLUGIN_SLUG);

  // Repair the full fixture set when running Playwright without `make e2e`.
  try {
    wp("plugin", "is-installed", "akismet");
    const fixtures = wpEval(
      '$ready=true;' +
        'for($i=1;$i<=60;$i++){' +
        '$ready=$ready&&(bool)get_user_by("login",sprintf("bulkuser-%03d",$i));}' +
        'for($i=1;$i<=100;$i++){' +
        '$ready=$ready&&file_exists(ABSPATH.sprintf("wp-test-file-%d.php",$i));}' +
        'echo ($ready' +
        '&&file_exists(ABSPATH."wp-config-test.php")' +
        '&&file_exists(ABSPATH.".htaccess")' +
        '&&file_exists(WP_CONTENT_DIR."/archive-legacy.php")' +
        '&&file_exists(WP_CONTENT_DIR."/literal.(a|b)*.php")' +
        '&&file_exists(ABSPATH."wp-includes/.htaccess"))?"ready":"missing";',
    );
    if (fixtures !== "ready") {
      throw new Error("fixtures missing");
    }
  } catch {
    runPluginScript("tests/e2e-prepare.sh");
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

  // Recover from a locally interrupted 2FA run before attempting admin login.
  const ids = [adminUser, testAdminUser, extraUser]
    .map((user) => getUserId(user.login))
    .join(",");
  wpEval(
    "SucuriScanOption::updateOption(':twofactor_mode','disabled');" +
      "SucuriScanOption::updateOption(':twofactor_users',array());" +
      `foreach(array(${ids}) as $uid){` +
      "delete_user_meta($uid,'sucuriscan_topt_secret_key');" +
      "delete_user_meta($uid,'sucuriscan_topt_last_success');}",
  );
});

setup("authenticate admin", async ({ browser }) => {
  // Bakes the WAF-dismiss cookie into the saved state so the dashboard modal
  // never blocks clicks in specs that visit page=sucuriscan. The waf-modal spec
  // deliberately overrides that cookie to '0' in its own context.
  await saveAdminStorageState(browser);
});
