/**
 * Settings · Alerts tab: recipients (test email / add / delete), trusted IPs
 * (add / duplicate error / table columns / delete), alert subject (preset +
 * custom), max alerts per hour, brute-force threshold, alert events
 * (notify_plugin_deleted checkbox persistence), and per-post-type alerts
 * (ignore / duplicate / toggle list / save monitored).
 *
 * Every test here mutates a global sucuriscan_* alert option on the single
 * shared WordPress DB, so the file runs serially and pins its preconditions in
 * beforeAll/beforeEach:
 *   - notify_post_publication must be enabled or the post-type panel renders
 *     disabled and its inputs cannot be interacted with.
 *   - The trusted-IP test asserts an empty table ("no data available"), so the
 *     `trustip` cache datastore is cleared before/after.
 *   - The alert-subject success notice only fires when the submitted value
 *     differs from the stored one, so the subject is normalised to a known
 *     value that differs from the preset before each run.
 * afterAll restores the recipients, subject, ignored-events and toggled options
 * to a sane baseline so repeated runs never trip over leftover state.
 */
import { test, expect } from "../../support/fixtures";
import type { Page } from "@playwright/test";
import { expectNotice } from "../../support/notices";
import { updateOption, wpEval } from "../../support/wp-cli";

const ALERTS_URL = "/wp-admin/admin.php?page=sucuriscan_settings#alerts";
// The recipients flow in the original Cypress source deep-links with
// &sucuriscan_lastlogin=1; preserved verbatim (cosmetic, but kept for parity).
const ALERTS_URL_RECIPIENTS =
  "/wp-admin/admin.php?page=sucuriscan_settings&sucuriscan_lastlogin=1#alerts";

const DEFAULT_RECIPIENT = "wordpress@example.com";
const ADDED_RECIPIENT = "admin@sucuri.net";
const TRUSTED_IP = "182.190.190.0/24";
const TRUSTED_IP_ADDR = "182.190.190.0";
const PRESET_SUBJECT = "Sucuri Alert, :event, :hostname";
const CUSTOM_SUBJECT = "Security alert: :event";
const CUSTOM_POST_TYPE = "new_sucuri_post_type";

/** Remove the file-based `trustip` cache datastore so the table starts empty. */
function clearTrustedIpDatastore(): void {
  wpEval(
    '$f=SucuriScan::dataStorePath("sucuri-trustip.php");if(file_exists($f)){@unlink($f);}',
  );
}

/** Error-only admin notice (duplicate IP / duplicate post-type). */
async function expectErrorNotice(page: Page, text: string): Promise<void> {
  await expect(
    page.locator(".sucuriscan-alert-error", { hasText: text }),
  ).toBeVisible();
}

test.describe("Settings · Alerts", () => {
  test.beforeEach(() => {
    // The post-type panel is disabled (and its inputs unusable) unless the
    // "new site content" alert is enabled.
    updateOption("sucuriscan_notify_post_publication", "enabled");
    // Start with only the default recipient so the add/delete round-trip and
    // the test-email checkbox (input[value="wordpress@example.com"]) hold.
    updateOption("sucuriscan_notify_to", DEFAULT_RECIPIENT);
    // The subject-success notice only fires on a value change; seed a value that
    // differs from the preset the first save submits.
    updateOption("sucuriscan_email_subject", CUSTOM_SUBJECT);
    // The post-type tests are non-idempotent if new_sucuri_post_type lingers.
    updateOption("sucuriscan_ignored_events", "[]");
    // Pin the events-test checkbox to its default so a worker killed mid-test
    // (between its ON-save and OFF-save) cannot leak an enabled state into a re-run.
    updateOption("sucuriscan_notify_plugin_deleted", "disabled");
    updateOption("sucuriscan_emails_per_hour", "5");
    updateOption("sucuriscan_maximum_failed_logins", "30");
    // The trusted-IP test asserts an empty table.
    clearTrustedIpDatastore();
  });

  test("can modify alerts recipients", async ({ page }) => {
    await page.goto(ALERTS_URL_RECIPIENTS);

    // Test alert to the default recipient.
    const defaultRecipient = page.locator(
      `input[name="sucuriscan_recipients[]"][value="${DEFAULT_RECIPIENT}"]`,
    );
    await expect(defaultRecipient).toBeVisible();
    await defaultRecipient.check();
    await page.getByTestId("sucuriscan_alerts_test_recipient_submit").click();
    await expectNotice(
      page,
      "A test alert was sent to your email, check your inbox",
    );

    // Add a new recipient.
    await page
      .getByTestId("sucuriscan_alerts_recipient_input")
      .fill(ADDED_RECIPIENT);
    await page
      .getByTestId("sucuriscan_alerts_recipient_add_email_submit")
      .click();
    await expectNotice(
      page,
      `The email alerts will be sent to: ${ADDED_RECIPIENT}`,
    );

    // Delete the added recipient.
    const addedRecipient = page.locator(
      `input[name="sucuriscan_recipients[]"][value="${ADDED_RECIPIENT}"]`,
    );
    await expect(addedRecipient).toBeVisible();
    await addedRecipient.check();
    await page.getByTestId("sucuriscan_alerts_delete_recipient_submit").click();
    await expectNotice(
      page,
      `These emails will stop receiving alerts: ${ADDED_RECIPIENT}`,
    );

    // Re-send a test alert to the default recipient.
    const defaultRecipientAgain = page.locator(
      `input[name="sucuriscan_recipients[]"][value="${DEFAULT_RECIPIENT}"]`,
    );
    await expect(defaultRecipientAgain).toBeVisible();
    await defaultRecipientAgain.check();
    await page.getByTestId("sucuriscan_alerts_test_recipient_submit").click();
    await expectNotice(
      page,
      "A test alert was sent to your email, check your inbox",
    );
  });

  test("can modify trusted ip addresses", async ({ page }) => {
    await page.goto(ALERTS_URL);

    const table = page.getByTestId("sucuriscan_trusted_ip_table");
    await expect(table).toContainText("no data available");

    // Add a CIDR IP.
    await page.getByTestId("sucuriscan_trusted_ip_input").fill(TRUSTED_IP);
    await page.getByTestId("sucuriscan_trusted_ip_add_ip_submit").click();
    await expectNotice(
      page,
      `Events generated from this IP will be ignored: ${TRUSTED_IP}`,
    );

    // Re-add the same IP -> duplicate error.
    await page.getByTestId("sucuriscan_trusted_ip_input").fill(TRUSTED_IP);
    await page.getByTestId("sucuriscan_trusted_ip_add_ip_submit").click();
    await expectErrorNotice(
      page,
      "The IP specified address was already added.",
    );

    // Verify the IP (RemoteAddr) and CIDR columns of the added row.
    const row = table.locator("tbody tr", { hasText: TRUSTED_IP_ADDR });
    await expect(row.locator("td").nth(0)).toContainText(TRUSTED_IP_ADDR);
    await expect(row.locator("td").nth(1)).toContainText(TRUSTED_IP);

    // Delete the IP.
    await page.locator('input[name="sucuriscan_del_trust_ip[]"]').check();
    await page.getByTestId("sucuriscan_trusted_ip_delete_ip_submit").click();
    await expectNotice(
      page,
      "The selected IP addresses were successfully deleted.",
    );
  });

  test("can modify alert subject", async ({ page }) => {
    await page.goto(ALERTS_URL);

    // Preset subject -> success notice (beforeAll seeded a different value).
    await page
      .locator(
        `input[name="sucuriscan_email_subject"][value="${PRESET_SUBJECT}"]`,
      )
      .check();
    await page.getByTestId("sucuriscan_alerts_subject_submit").click();
    await expectNotice(page, "The email subject has been successfully updated");

    // Custom subject -> success notice (reliably differs from the preset).
    await page
      .locator('input[name="sucuriscan_email_subject"][value="custom"]')
      .check();
    await page
      .getByTestId("sucuriscan_alerts_subject_input")
      .fill(CUSTOM_SUBJECT);
    await page.getByTestId("sucuriscan_alerts_subject_submit").click();
    await expectNotice(page, "The email subject has been successfully updated");
  });

  test("can update max alerts per hour", async ({ page }) => {
    await page.goto(ALERTS_URL);

    await page
      .getByTestId("sucuriscan_alerts_per_hour_select")
      .selectOption({ label: "Maximum 160 per hour" });
    await page.getByTestId("sucuriscan_alerts_per_hour_submit").click();
    await expectNotice(
      page,
      "The maximum number of alerts per hour has been updated",
    );
  });

  test("can update value after a brute force attack is considered", async ({
    page,
  }) => {
    await page.goto(ALERTS_URL);

    await page
      .getByTestId("sucuriscan_max_failed_logins_select")
      .selectOption({ label: "480 failed logins per hour" });
    await page.getByTestId("sucuriscan_max_failed_logins_submit").click();
    await expectNotice(
      page,
      "The plugin will assume that your website is under a brute-force attack after 480 failed logins are detected during the same hour",
    );
  });

  test("can update the events that fire security alerts", async ({ page }) => {
    await page.goto(ALERTS_URL);

    // value="1" is the real checkbox; a sibling hidden value="0" shares the
    // name, so scope strictly to value="1".
    const pluginDeleted = page.locator(
      'input[name="sucuriscan_notify_plugin_deleted"][value="1"]',
    );

    // Toggle ON and save -> persisted as checked.
    await pluginDeleted.check();
    await page.getByTestId("sucuriscan_save_alert_events_submit").click();
    await expectNotice(page, "The alert settings have been updated");
    await expect(
      page.locator('input[name="sucuriscan_notify_plugin_deleted"][value="1"]'),
    ).toBeChecked();

    // Toggle OFF and save -> persisted as unchecked.
    await page
      .locator('input[name="sucuriscan_notify_plugin_deleted"][value="1"]')
      .uncheck();
    await page.getByTestId("sucuriscan_save_alert_events_submit").click();
    await expectNotice(page, "The alert settings have been updated");
    await expect(
      page.locator('input[name="sucuriscan_notify_plugin_deleted"][value="1"]'),
    ).not.toBeChecked();
  });

  test("can update alerts per post type", async ({ page }) => {
    await page.goto(ALERTS_URL);

    // Add a custom (non-registered) post-type to the ignore list.
    await page
      .getByTestId("sucuriscan_alerts_post_type_input")
      .fill(CUSTOM_POST_TYPE);
    await page.getByTestId("sucuriscan_alerts_post_type_submit").click();
    await expectNotice(page, "Post-type has been successfully ignored.");

    // The new type only appears in the table after a fresh render.
    await page.reload();
    await page
      .getByTestId("sucuriscan_alerts_post_type_toggle_post_type_list")
      .click();

    // Ignored types render unchecked.
    const customRow = page.locator(
      `input[name="sucuriscan_posttypes[]"][value="${CUSTOM_POST_TYPE}"]`,
    );
    await expect(customRow).toBeVisible();
    await expect(customRow).not.toBeChecked();

    // Re-adding the same type errors as a duplicate.
    await page
      .getByTestId("sucuriscan_alerts_post_type_input")
      .fill(CUSTOM_POST_TYPE);
    await page.getByTestId("sucuriscan_alerts_post_type_submit").click();
    await expectErrorNotice(
      page,
      "The post-type is already being ignored (duplicate).",
    );

    // Toggle a registered post-type and batch-save the monitored list.
    await page
      .getByTestId("sucuriscan_alerts_post_type_toggle_post_type_list")
      .click();
    await page
      .locator('input[name="sucuriscan_posttypes[]"][value="nav_menu_item"]')
      .click();
    await page.getByTestId("sucuriscan_alerts_post_type_save_submit").click();
    await expectNotice(page, "List of monitored post-types has been updated.");
  });
});
