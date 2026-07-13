/**
 * Audit logs: sending logs to the Sucuri servers (AJAX stubbed with fixtures)
 * and filtering the local audit log (plugins / logins / time).
 *
 * The send-logs test stubs the two admin-ajax actions so it never calls the
 * real Sucuri API; the filter test runs against the real seeded audit data
 * (akismet activation events + admin logins from tests/e2e-prepare.sh).
 */
import path from "node:path";
import { test, expect } from "../../support/fixtures";
import type { Page } from "@playwright/test";
import { wpEval } from "../../support/wp-cli";

const DATA_DIR = path.join(__dirname, "../../data");
const AUDIT_LOGS_FIXTURE = path.join(DATA_DIR, "audit_logs.json");
const SEND_LOGS_FIXTURE = path.join(DATA_DIR, "auditlogs_send_logs.json");

const REPORTING_URL =
  "/wp-admin/admin.php?page=sucuriscan_events_reporting#auditlogs";

// These tests share the same page state and reload the same list; keep them ordered.
test.describe.configure({ mode: "serial" });

function seedAuditQueue(): void {
  wpEval(
    '$d=WP_CONTENT_DIR."/uploads/sucuri";' +
      '@unlink($d."/sucuri-auditqueue.php");' +
      '@unlink($d."/sucuri-auditlogs.php");' +
      'SucuriScanEvent::reportWarningEvent("Plugin activated: Akismet Anti-spam");' +
      'SucuriScanEvent::reportNoticeEvent("User authentication succeeded: admin");',
  );
}

test.beforeEach(() => {
  seedAuditQueue();
});

/** Click the filter button and wait for the audit-log list AJAX to come back. */
async function applyFilter(page: Page): Promise<void> {
  await Promise.all([
    page.waitForResponse(
      (r) =>
        r.url().includes("admin-ajax.php") &&
        (r.request().postData() ?? "").includes("get_audit_logs"),
    ),
    page.getByTestId("sucuriscan_auditlogs_filter_button").click(),
  ]);
}

async function clearFilter(page: Page): Promise<void> {
  // Clearing re-runs get_audit_logs, which re-renders BOTH the entry list and the
  // #sucuriscan-filters <select> block. Await that response so the next
  // selectOption acts on the fresh select nodes (not the about-to-be-replaced
  // ones) and the following applyFilter can't latch onto this in-flight response.
  await Promise.all([
    page.waitForResponse(
      (r) =>
        r.url().includes("admin-ajax.php") &&
        (r.request().postData() ?? "").includes("get_audit_logs"),
    ),
    page.getByTestId("sucuriscan_auditlogs_clear_filter_button").click(),
  ]);
}

/** Assert every rendered entry title matches `pattern`. */
async function expectAllEntryTitles(
  page: Page,
  pattern: RegExp,
): Promise<void> {
  const titles = page.locator(
    ".sucuriscan-auditlog-entry .sucuriscan-auditlog-entry-title",
  );
  await expect(titles.first()).toBeVisible();
  const count = await titles.count();
  expect(count).toBeGreaterThan(0);
  for (let i = 0; i < count; i++) {
    await expect(titles.nth(i)).toContainText(pattern);
  }
}

test("sends audit logs to the Sucuri servers (AJAX stubbed)", async ({
  page,
}) => {
  await page.route("**/admin-ajax.php**", async (route) => {
    const body = route.request().postData() ?? "";
    if (body.includes("get_audit_logs")) {
      return route.fulfill({ path: AUDIT_LOGS_FIXTURE });
    }
    if (body.includes("auditlogs_send_logs")) {
      // Small delay so the "Loading..." state is observable, mirroring the
      // original assertion that the user sees progress before results.
      await new Promise((resolve) => setTimeout(resolve, 300));
      return route.fulfill({ path: SEND_LOGS_FIXTURE });
    }
    return route.fallback();
  });

  await page.goto(REPORTING_URL);

  // Use Promise.all so we reliably catch the auditlogs_send_logs round-trip
  // before asserting the result; the element does not transition through a
  // "Loading..." state during send (it's only set during the initial page-load
  // get_audit_logs call, which the stub resolves immediately).
  const sent = page.waitForResponse(
    (r) =>
      r.url().includes("admin-ajax.php") &&
      (r.request().postData() ?? "").includes("auditlogs_send_logs"),
  );
  await page.getByTestId("sucuriscan_dashboard_send_audit_logs_submit").click();
  await expect(
    page.locator(".sucuriscan-auditlogs-sendlogs-response"),
  ).toContainText("Loading...");
  await sent;

  await expect(
    page.locator(".sucuriscan-auditlog-entry-title").first(),
  ).toContainText("User authentication succeeded: admin");
});

test("filters audit logs by plugin, login, and time", async ({ page }) => {
  await page.goto(REPORTING_URL);

  await expect(page.locator(".sucuriscan-auditlog-response")).toBeVisible();
  await expect(
    page.locator(".sucuriscan-auditlog-entry").first(),
  ).toBeVisible();

  // Plugins filter -> every entry is a plugin activation.
  await page.locator("#plugins").selectOption({ label: "Activated" });
  await applyFilter(page);
  await expectAllEntryTitles(page, /Plugin activated/);
  await clearFilter(page);

  // Logins filter -> every entry is a successful authentication.
  await page.locator("#logins").selectOption({ label: "Succeeded" });
  await applyFilter(page);
  await expectAllEntryTitles(page, /User authentication succeeded/);
  await clearFilter(page);

  // Combined plugins + logins -> each entry matches one of the two.
  await page.locator("#plugins").selectOption({ label: "Activated" });
  await page.locator("#logins").selectOption({ label: "Succeeded" });
  await applyFilter(page);
  await expectAllEntryTitles(
    page,
    /Plugin activated|User authentication succeeded/,
  );
  await clearFilter(page);

  // Combined time + login -> only successful authentications.
  await page.locator("#time").selectOption({ label: "Last 7 Days" });
  await page.locator("#logins").selectOption({ label: "Succeeded" });
  await applyFilter(page);
  await expectAllEntryTitles(page, /User authentication succeeded/);
  await clearFilter(page);

  // NOTE: the 'Custom' date-range branch was intentionally disabled (commented
  // out) in the Cypress source and is therefore not migrated. See MIGRATION.md.
});
