/**
 * Helpers for asserting the plugin's server-rendered admin notices.
 *
 * The plugin emits notices as `.sucuriscan-alert` (with a type modifier such as
 * `.sucuriscan-alert-updated` / `.sucuriscan-alert-error`) and prefixes the body
 * with an admin-notice prefix, so assertions are always substring (hasText),
 * never exact equality. Multiple notices can render on one page, so we locate the
 * specific notice that contains the expected text rather than a single element.
 */
import { expect, type Locator, type Page } from "@playwright/test";

const ALERT = ".sucuriscan-alert";
const ERROR_ALERT = ".sucuriscan-alert-error, .notice-error";

/** Locator for the admin notice that contains `text` (auto-waits via expect). */
export function notice(page: Page, text: string | RegExp): Locator {
  return page.locator(ALERT, { hasText: text });
}

/** Assert a success/info/error admin notice containing `text` becomes visible. */
export async function expectNotice(
  page: Page,
  text: string | RegExp,
): Promise<void> {
  await expect(notice(page, text)).toBeVisible();
}

/** Assert no decrypt/error admin notice is present (web-first; waits for the page to settle). */
export async function expectNoErrorNotice(page: Page): Promise<void> {
  await expect(page.locator(ERROR_ALERT)).toHaveCount(0);
}
