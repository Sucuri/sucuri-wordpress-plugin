/**
 * SUCURI_PLUG_* salt scheme: v:1 (AUTH_SALT) -> v:2 migration, plug-salt
 * constants persisted to wp-config.php, fresh-key save, and corrupt-salt
 * recovery.
 *
 * Two independent serial groups:
 *
 *   1. "salt migration" — seeds a v:1 (AUTH_SALT-encrypted) payload and strips
 *      SUCURI_PLUG_* from wp-config.php (tests/e2e-seed-waf-plug-salt.sh), then
 *      loads the firewall page so getSecretOption() decrypts with the auth key
 *      and re-encrypts as v:2 (aes-256-gcm), writing SUCURI_PLUG_KEY/SALT to
 *      wp-config.php (NOT wp_options) before the stop-editing marker, exactly
 *      once, with no decrypt-error notice.
 *
 *   2. "fresh key save and decrypt" — deletes the encrypted/secret options, then
 *      saves a brand-new key via SucuriScanOption::updateOption (stored v:2 from
 *      the start, single constants), decrypts it back, and finally verifies the
 *      stable-salt behavior after deterministic constants are installed: a
 *      re-saved key remains decryptable without rotating the constants.
 *
 * Each test seeds its own wp-config.php / option preconditions. Live WAF AJAX is neutralised with
 * page.route — the old Cypress get_firewall_settings intercept was a dead no-op
 * (the real page POSTs form_action='firewall_settings'); migration is triggered
 * server-side by the page render, not the AJAX.
 *
 * Preconditions (asserted/relied upon): wp-config.php is writable by the wp-env
 * tests-cli user and contains the canonical "/* That's all, stop editing!"
 * marker. The seed/corrupt scripts live under tests/ and run inside the plugin
 * dir (slug "sucuri-scanner" — the old Cypress default "sucuri-wordpress-plugin"
 * was wrong for this repo; runPluginScript uses PLUGIN_SLUG).
 */
import { test, expect } from "@playwright/test";
import type { Page } from "@playwright/test";
import {
  getOption,
  tryGetOption,
  deleteRawOption,
  readWpConfig,
  restoreWpConfig,
  runPluginScript,
  updateRawOption,
  wpEnvRun,
  wpEval,
  deleteOption,
} from "../../support/wp-cli";
import { expectNoErrorNotice } from "../../support/notices";

const FIREWALL_URL = "/wp-admin/admin.php?page=sucuriscan_firewall";

const NEW_KEY =
  "cccccccccccccccccccccccccccccccc/dddddddddddddddddddddddddddddddd";
const CORRUPT_RECOVERY_KEY =
  "eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee/ffffffffffffffffffffffffffffffff";

const PLUG_KEY_DEFINE = /define\('SUCURI_PLUG_KEY',\s*'[0-9a-f]{64}'\)/;
const PLUG_SALT_DEFINE = /define\('SUCURI_PLUG_SALT',\s*'[0-9a-f]{64}'\)/;

const RAW_OPTIONS = [
  "sucuriscan_secret_cloudproxy_apikey_enc",
  "sucuriscan_secret_cloudproxy_apikey",
  "sucuriscan_no_salt_encryption",
  "sucuriscan_waf_key_decrypt_error",
] as const;

let originalWpConfig: string;
let originalRawOptions: Map<string, unknown>;

test.beforeAll(() => {
  originalWpConfig = readWpConfig();
  originalRawOptions = new Map(
    RAW_OPTIONS.map((name) => [name, tryGetOption(name)]),
  );
});

test.afterAll(() => {
  restoreWpConfig(originalWpConfig);
  for (const name of RAW_OPTIONS) {
    deleteRawOption(name);
    const value = originalRawOptions.get(name);
    if (value !== null && value !== undefined) {
      updateRawOption(name, value);
    }
  }
});

/** v:1 / v:2 encrypted-payload shape stored in sucuriscan_secret_cloudproxy_apikey_enc. */
interface EncryptedPayload {
  v: number;
  alg: string;
  iv: string;
  tag: string;
  ct: string;
}

/**
 * Read the payload without booting this plugin. A normal `wp option get` loads
 * active plugins, and this plugin reads the WAF key during bootstrap, which
 * would perform the v:1-to-v:2 migration before the test triggers it.
 */
function getEncryptedPayload(): EncryptedPayload {
  return JSON.parse(
    wpEnvRun(
      "wp",
      "option",
      "get",
      "sucuriscan_secret_cloudproxy_apikey_enc",
      "--format=json",
      "--skip-plugins",
      "--skip-themes",
    ),
  ) as EncryptedPayload;
}

/** Count non-overlapping occurrences of a literal substring. */
function countOccurrences(haystack: string, needle: string): number {
  return haystack.split(needle).length - 1;
}

/** Neutralise every live WAF AJAX call so the firewall render never hangs on the real Sucuri API. */
async function blockLiveWaf(page: Page): Promise<void> {
  await page.route("**/admin-ajax.php**", (route) =>
    route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({ ok: true, settings: {} }),
    }),
  );
}

test.describe("SUCURI_PLUG_* salt migration", () => {
  test.beforeEach(async ({ page }) => {
    runPluginScript("tests/e2e-seed-waf-plug-salt.sh");
    await blockLiveWaf(page);
  });

  test("migrates a v:1 payload to v:2 on first firewall page load", async ({
    page,
  }) => {
    // Seed after Playwright has initialized this test's page. Earlier mutation
    // specs may leave an open request that reads and migrates the payload, so a
    // beforeAll seed is not isolated enough for this one-way migration scenario.
    // PRE: the seed left a v:1 payload in place.
    const before = getEncryptedPayload();
    expect(typeof before).toBe("object");
    expect(before.v).toBe(1);

    // The firewall render calls getSecretOption() server-side, which decrypts the
    // v:1 payload with the auth key and re-encrypts it as v:2. Await full
    // navigation so the update_option write completes before reading via WP-CLI.
    await page.goto(FIREWALL_URL, { waitUntil: "networkidle" });

    // POST: the stored payload is now v:2 / aes-256-gcm with iv/tag/ct.
    const after = getEncryptedPayload();
    expect(typeof after).toBe("object");
    const payload = after;
    expect(payload.v).toBe(2);
    expect(payload.alg).toBe("aes-256-gcm");
    expect(payload).toHaveProperty("iv");
    expect(payload).toHaveProperty("tag");
    expect(payload).toHaveProperty("ct");
  });

  test("writes SUCURI_PLUG_KEY and SUCURI_PLUG_SALT to wp-config.php (not wp_options)", async ({ page }) => {
    await page.goto(FIREWALL_URL, { waitUntil: "networkidle" });
    const config = readWpConfig();
    // Exact written format (option.lib.php:1007-1011): two spaces after the KEY
    // comma, one after SALT; each value is 64-hex (hash_hmac sha256 hex output).
    // Assert booleans so a failure never prints the full wp-config.php and salts.
    expect(PLUG_KEY_DEFINE.test(config)).toBe(true);
    expect(PLUG_SALT_DEFINE.test(config)).toBe(true);

    // The constants live in wp-config.php only — never in wp_options.
    expect(tryGetOption("sucuriscan_plug_key")).toBeNull();
    expect(tryGetOption("sucuriscan_plug_salt")).toBeNull();
  });

  test("inserts constants before the stop-editing marker in wp-config.php", async ({ page }) => {
    await page.goto(FIREWALL_URL, { waitUntil: "networkidle" });
    const config = readWpConfig();
    const plugPos = config.indexOf("SUCURI_PLUG_KEY");
    const stopPos = config.indexOf("That's all");

    // If the wp-env wp-config.php lacks the canonical "/* That's all, stop
    // editing!" marker, the plugin falls back to the ABSPATH-guard insertion and
    // this assertion would not hold; standard wp-env installs carry the marker.
    expect(plugPos).toBeGreaterThan(-1);
    expect(stopPos).toBeGreaterThan(-1);
    expect(plugPos).toBeLessThan(stopPos);
  });

  test("does not duplicate constants on repeated reads", async ({ page }) => {
    await page.goto(FIREWALL_URL, { waitUntil: "networkidle" });
    // Reading the key does not regenerate constants: getPluginSaltRaw returns the
    // already-defined SUCURI_PLUG_KEY.SUCURI_PLUG_SALT without writing.
    wpEval('SucuriScanOption::getOption(":cloudproxy_apikey");');
    wpEval('SucuriScanOption::getOption(":cloudproxy_apikey");');

    const config = readWpConfig();
    expect(countOccurrences(config, "SUCURI_PLUG_KEY")).toBe(1);
    expect(countOccurrences(config, "SUCURI_PLUG_SALT")).toBe(1);
  });

  test("no decrypt-error notice is shown after migration", async ({ page }) => {
    await page.goto(FIREWALL_URL, { waitUntil: "networkidle" });
    // The "could not be decrypted" notice renders as .sucuriscan-alert-error /
    // .notice-error; after a clean migration it must be absent.
    await expectNoErrorNotice(page);
  });
});

test.describe("SUCURI_PLUG_* salt - fresh key save and decrypt", () => {
  test.beforeEach(() => {
    // Remove the encrypted key + plaintext fallback so save exercises the
    // v:2-from-scratch path; wp-config.php constants are regenerated on save.
    deleteOption("sucuriscan_secret_cloudproxy_apikey_enc");
    deleteOption("sucuriscan_secret_cloudproxy_apikey");
    wpEval(
      `SucuriScanOption::updateOption(":cloudproxy_apikey", "${NEW_KEY}");`,
    );
  });

  test("stores a newly saved key as v:2 and replaces constants in wp-config.php", () => {
    // Save always encrypts as v:2 (encryptSecretValue, option.lib.php:1504-1508).
    const payload = getOption<EncryptedPayload>(
      "sucuriscan_secret_cloudproxy_apikey_enc",
    );
    expect(typeof payload).toBe("object");
    expect((payload as EncryptedPayload).v).toBe(2);

    // Constants are replaced, not duplicated — each appears exactly once.
    const config = readWpConfig();
    expect(countOccurrences(config, "SUCURI_PLUG_KEY")).toBe(1);
    expect(countOccurrences(config, "SUCURI_PLUG_SALT")).toBe(1);
  });

  test("can decrypt the freshly saved key back to the original value", () => {
    const output = wpEval(
      'echo SucuriScanOption::getOption(":cloudproxy_apikey");',
    );
    expect(output.trim()).toBe(NEW_KEY);
  });

  test("re-save after corrupt salt keeps the saved key decryptable (salt is not rotated on save)", () => {
    // Overwrite the SUCURI_PLUG_* defines with garbage hex (e2e-corrupt-salt.sh).
    runPluginScript("tests/e2e-corrupt-salt.sh");

    // Re-save the key. By design the plugin NEVER rotates the plug-salt on save
    // (option.lib.php:1480-1483 — rotating rewrote wp-config.php on every insert and
    // caused within-request key mismatches); it encrypts with whatever SUCURI_PLUG_*
    // is currently defined. So "recovery" here means the freshly saved key still
    // round-trips, NOT that the constants are regenerated. (Regenerating constants is
    // a separate path, maybeHealMisplacedPluginSalt, which only fires for defines
    // placed OUTSIDE the <?php block — the historical key-leak bug.)
    wpEval(
      `SucuriScanOption::updateOption(":cloudproxy_apikey", "${CORRUPT_RECOVERY_KEY}");`,
    );

    // The new key reads back cleanly: encrypted and decrypted with the same salt.
    const output = wpEval(
      'echo SucuriScanOption::getOption(":cloudproxy_apikey");',
    );
    expect(output.trim()).toBe(CORRUPT_RECOVERY_KEY);

    // It is stored as a v:2 payload, and the constants are not duplicated — re-saving
    // replaces the encrypted option and leaves exactly one define of each constant.
    const payload = getOption<EncryptedPayload>(
      "sucuriscan_secret_cloudproxy_apikey_enc",
    );
    expect((payload as EncryptedPayload).v).toBe(2);

    const config = readWpConfig();
    expect(countOccurrences(config, "SUCURI_PLUG_KEY")).toBe(1);
    expect(countOccurrences(config, "SUCURI_PLUG_SALT")).toBe(1);
  });
});
