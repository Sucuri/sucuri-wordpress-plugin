/**
 * Legacy WAF key migration: a plaintext key stored in the settings file
 * (sucuri-settings.php) is promoted into the encrypted DB option on the first
 * firewall page render.
 *
 * The migration is one-way (file -> DB) and server-side: visiting the firewall
 * page runs getKey() -> getSecretOptionValue(), which encrypts the legacy key
 * into sucuriscan_secret_cloudproxy_apikey_enc (aes-256-gcm) and removes the
 * plaintext entry from the settings file. Because it cannot be re-run without
 * resetting state, beforeAll re-seeds the plaintext key and deletes both secret
 * options via tests/e2e-seed-waf-migration.sh, making the test re-runnable.
 *
 * The live WAF AJAX is fully neutralised with page.route — the old Cypress
 * `get_firewall_settings` intercept was a dead no-op (the real page POSTs
 * form_action='firewall_settings'); migration does not depend on it.
 */
import { test, expect } from "@playwright/test";
import {
  getOption,
  tryGetOption,
  deleteRawOption,
  readSettingsFileJson,
  readWpConfig,
  replaceOptions,
  restoreWpConfig,
  runPluginScript,
  updateRawOption,
} from "../../support/wp-cli";

const FIREWALL_URL = "/wp-admin/admin.php?page=sucuriscan_firewall";

const LEGACY_WAF_KEY =
  "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb";
const RAW_OPTIONS = [
  "sucuriscan_secret_cloudproxy_apikey_enc",
  "sucuriscan_secret_cloudproxy_apikey",
  "sucuriscan_no_salt_encryption",
  "sucuriscan_waf_key_decrypt_error",
] as const;

interface EncryptedPayload {
  alg: string;
  iv: string;
  tag: string;
  ct: string;
}

test.describe("WAF key migration", () => {
  let originalOptions: Record<string, unknown>;
  let originalWpConfig: string;
  let originalRawOptions: Map<string, unknown>;

  test.beforeAll(() => {
    originalOptions = readSettingsFileJson();
    originalWpConfig = readWpConfig();
    originalRawOptions = new Map(
      RAW_OPTIONS.map((name) => [name, tryGetOption(name)]),
    );
    // Re-seed the plaintext key + addr_header REMOTE_ADDR + notify_to and delete
    // both secret options so the one-way migration can run from a clean slate.
    runPluginScript("tests/e2e-seed-waf-migration.sh");
  });

  test.afterAll(() => {
    replaceOptions(originalOptions);
    restoreWpConfig(originalWpConfig);
    for (const name of RAW_OPTIONS) {
      deleteRawOption(name);
      const value = originalRawOptions.get(name);
      if (value !== null && value !== undefined) {
        updateRawOption(name, value);
      }
    }
  });

  test("migrates legacy WAF key to encrypted DB option", async ({ page }) => {
    // PRE: the seed left the plaintext key and REMOTE_ADDR header in the file.
    const before = readSettingsFileJson();
    expect(before.sucuriscan_cloudproxy_apikey).toBe(LEGACY_WAF_KEY);
    expect(before.sucuriscan_addr_header).toBe("REMOTE_ADDR");

    // Neutralise every live WAF AJAX call so the page render never hangs on the
    // real Sucuri API. Migration is triggered server-side by the page render,
    // not by this AJAX (the old get_firewall_settings stub was a dead no-op).
    await page.route("**/admin-ajax.php**", (route) =>
      route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ ok: true, settings: {} }),
      }),
    );

    // Await full navigation so the server-side promotion (file write + option
    // update) has completed before we read state via WP-CLI.
    await page.goto(FIREWALL_URL, { waitUntil: "networkidle" });

    // POST: the plaintext key was removed from the settings file; addr_header
    // is untouched.
    const after = readSettingsFileJson();
    expect(after).not.toHaveProperty("sucuriscan_cloudproxy_apikey");
    expect(after.sucuriscan_addr_header).toBe("REMOTE_ADDR");

    // POST: the encrypted DB option holds an aes-256-gcm payload with iv/tag/ct.
    const encrypted = getOption<EncryptedPayload>(
      "sucuriscan_secret_cloudproxy_apikey_enc",
    );
    expect(encrypted).not.toBeNull();
    expect(typeof encrypted).toBe("object");
    const payload = encrypted as EncryptedPayload;
    expect(payload.alg).toBe("aes-256-gcm");
    expect(payload).toHaveProperty("iv");
    expect(payload).toHaveProperty("tag");
    expect(payload).toHaveProperty("ct");

    // POST: no plaintext fallback was written (the encrypted path succeeded).
    // tryGetOption tolerates the option being absent (returns null) — a missing
    // option makes WP-CLI exit non-zero, which the strict getOption would throw on.
    const plaintextFallback = tryGetOption(
      "sucuriscan_secret_cloudproxy_apikey",
    );
    expect(plaintextFallback ?? "").toBe("");
  });
});
