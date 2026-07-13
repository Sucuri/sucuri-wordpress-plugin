/**
 * Settings · General tab: timezone, IP-address discovery, reverse proxy,
 * datastore file deletion, JSON settings import, and the reset action.
 *
 * State pinning: the toggle tests assert specific Enable/Disable button labels
 * and paired notices that depend on the option value at page load, so a
 * beforeEach pins the relevant options to a known baseline — making every test
 * order-independent and safe to re-run.
 *
 * `import` overwrites the whole option set; `reset` clears the scheduled-scan
 * hook and logs the deletion (it does NOT remove options or deactivate the
 * plugin in the current implementation). Both run last and the afterAll
 * restores a sane baseline.
 */
import { test, expect } from "@playwright/test";
import { expectNotice } from "../../support/notices";
import {
  readSettingsFileJson,
  replaceOptions,
  updateOption,
  wpEval,
} from "../../support/wp-cli";

const GENERAL_URL = "/wp-admin/admin.php?page=sucuriscan_settings#general";

const IMPORT_PAYLOAD =
  '{"sucuriscan_addr_header":"REMOTE_ADDR","sucuriscan_api_protocol":"https","sucuriscan_api_service":"enabled","sucuriscan_cloudproxy_apikey":"","sucuriscan_diff_utility":"disabled","sucuriscan_dns_lookups":"enabled","sucuriscan_email_subject":"Sucuri Alert, :domain, :event, :remoteaddr","sucuriscan_emails_per_hour":5,"sucuriscan_ignored_events":"","sucuriscan_lastlogin_redirection":"enabled","sucuriscan_maximum_failed_logins":30,"sucuriscan_notify_available_updates":"disabled","sucuriscan_notify_bruteforce_attack":"disabled","sucuriscan_notify_failed_login":"disabled","sucuriscan_notify_plugin_activated":"enabled","sucuriscan_notify_plugin_change":"enabled","sucuriscan_notify_plugin_deactivated":"disabled","sucuriscan_notify_plugin_deleted":"disabled","sucuriscan_notify_plugin_installed":"disabled","sucuriscan_notify_plugin_updated":"disabled","sucuriscan_notify_post_publication":"enabled","sucuriscan_notify_scan_checksums":"disabled","sucuriscan_notify_settings_updated":"enabled","sucuriscan_notify_success_login":"disabled","sucuriscan_notify_theme_activated":"enabled","sucuriscan_notify_theme_deleted":"disabled","sucuriscan_notify_theme_editor":"enabled","sucuriscan_notify_theme_installed":"disabled","sucuriscan_notify_theme_updated":"disabled","sucuriscan_notify_to":"wordpress@example.com","sucuriscan_notify_user_registration":"disabled","sucuriscan_notify_website_updated":"disabled","sucuriscan_notify_widget_added":"disabled","sucuriscan_notify_widget_deleted":"disabled","sucuriscan_prettify_mails":"disabled","sucuriscan_revproxy":"enabled","sucuriscan_selfhosting_fpath":"","sucuriscan_selfhosting_monitor":"disabled","sucuriscan_use_wpmail":"enabled","trusted_ips":[]}';

test.describe.configure({ mode: "serial" });

test.describe("Settings · General", () => {
  let originalOptions: Record<string, unknown>;

  test.beforeAll(() => {
    originalOptions = readSettingsFileJson();
  });

  test.beforeEach(() => {
    // Deterministic starting point for the label-flip / paired-notice toggles.
    updateOption("sucuriscan_dns_lookups", "enabled");
    updateOption("sucuriscan_revproxy", "enabled");
    updateOption("sucuriscan_addr_header", "HTTP_X_SUCURI_CLIENTIP");
    // The "delete datastore files" test needs a writable integrity file present.
    wpEval(
      '$d=WP_CONTENT_DIR."/uploads/sucuri";if(!is_dir($d)){mkdir($d,0755,true);}' +
        'file_put_contents($d."/sucuri-integrity.php","<?php exit(0); ?>\\n[]\\n");',
    );
  });

  test.afterAll(() => {
    replaceOptions(originalOptions);
    wpEval(
      "wp_clear_scheduled_hook('sucuriscan_scheduled_scan');" +
        "SucuriScanEvent::installScheduledTask();",
    );
  });

  test("updates the timezone setting", async ({ page }) => {
    await page.goto(GENERAL_URL);
    await page
      .getByTestId("sucuriscan_timezone_select")
      .selectOption("UTC-07.00");
    await page.getByTestId("sucuriscan_timezone_submit").click();
    await expectNotice(
      page,
      "The timezone for the date and time in the audit logs has been changed",
    );
  });

  test("updates IP address discovery (DNS lookups + header)", async ({
    page,
  }) => {
    await page.goto(GENERAL_URL);
    const toggle = page.getByTestId(
      "sucuriscan_ip_address_discovery_toggle_submit",
    );

    // Starts enabled -> first toggle disables it, so the button now offers "Enable".
    await toggle.click();
    await expectNotice(
      page,
      "The status of the DNS lookups for the reverse proxy detection has been changed",
    );
    await expect(toggle).toContainText("Enable");

    await toggle.click();
    await expectNotice(
      page,
      "The status of the DNS lookups for the reverse proxy detection has been changed",
    );
    await expect(toggle).toContainText("Disable");

    await page
      .getByTestId("sucuriscan_addr_header_select")
      .selectOption("HTTP_X_REAL_IP");
    await page.getByTestId("sucuriscan_addr_header_proceed").click();
    await expectNotice(page, "HTTP header was set to HTTP_X_REAL_IP");
    await expectNotice(page, "Reverse proxy support was set to enabled");
  });

  test("updates the reverse proxy setting", async ({ page }) => {
    await page.goto(GENERAL_URL);
    const toggle = page.getByTestId("sucuriscan_reverse_proxy_toggle");

    // Starts enabled -> first click disables and switches to REMOTE_ADDR.
    await toggle.click();
    await expectNotice(page, "Reverse proxy support was set to disabled");
    await expectNotice(page, "HTTP header was set to REMOTE_ADDR");

    await toggle.click();
    await expectNotice(page, "Reverse proxy support was set to enabled");
    await expectNotice(page, "HTTP header was set to HTTP_X_SUCURI_CLIENTIP");
  });

  test("deletes datastore files", async ({ page }) => {
    await page.goto(GENERAL_URL);

    // Exactly one writable file selected -> "1 out of 1".
    await page
      .locator(
        'input[name="sucuriscan_filename[]"][value="sucuri-integrity.php"]',
      )
      .check();
    await page
      .getByTestId("sucuriscan_general_datastore_delete_button")
      .click();
    await expectNotice(page, "1 out of 1 files have been deleted.");

    // Select-all then delete; the exact count is environment-dependent.
    await page
      .getByTestId("sucuriscan_general_datastore_delete_checkbox")
      .check();
    await page
      .getByTestId("sucuriscan_general_datastore_delete_button")
      .click();
    await expectNotice(page, "files have been deleted.");
  });

  test("imports JSON settings", async ({ page }) => {
    await page.goto(GENERAL_URL);

    await page
      .getByTestId("sucuriscan_import_export_settings_textarea")
      .fill(IMPORT_PAYLOAD);
    await page
      .getByTestId("sucuriscan_import_export_settings_checkbox")
      .check();
    await page.getByTestId("sucuriscan_import_export_settings_submit").click();

    await page.reload();
    // The imported addr_header makes REMOTE_ADDR the selected option.
    await expect(page.getByTestId("sucuriscan_addr_header_select")).toHaveValue(
      "REMOTE_ADDR",
    );
  });

  test("requires explicit confirmation before the reset action", async ({ page }) => {
    await page.goto(GENERAL_URL);
    await page.getByTestId("sucuriscan_reset_submit").click();
    await expect(page.locator(".sucuriscan-alert-error")).toContainText(
      "You need to confirm that you understand the risk of this operation.",
    );

    await page.goto(GENERAL_URL);
    await page.getByTestId("sucuriscan_reset_checkbox").check();
    await page.getByTestId("sucuriscan_reset_submit").click();
    await expectNotice(
      page,
      "Local security logs, hardening and settings were deleted",
    );
  });
});
