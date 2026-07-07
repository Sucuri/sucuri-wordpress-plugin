/**
 * Shared test fixtures. Re-export `test`/`expect` from here so specs get the
 * extra fixtures without pulling in heavy page-object machinery they don't need.
 *
 * `loggedOutRequest` is an unauthenticated APIRequestContext used by the header
 * specs to assert response headers as an anonymous visitor (the Playwright
 * equivalent of `cy.clearCookies()` + `cy.request('/')`).
 */
import {
  test as base,
  request as playwrightRequest,
  type APIRequestContext,
} from "@playwright/test";
import { BASE_URL } from "./env";

interface Fixtures {
  loggedOutRequest: APIRequestContext;
}

export const test = base.extend<Fixtures>({
  // Truly anonymous request context. The crux: any context created inside a
  // test (browser.newContext() OR request.newContext()) inherits the project's
  // `use.storageState` — here the admin session — so it is silently logged IN.
  // That makes WordPress's is_user_logged_in() true and the Cache-Control
  // library serve its no-cache fallback even for front-end visits, masking the
  // per-tier max-age the header specs assert. Passing an explicit empty
  // storageState clears the inherited admin cookies so the request is anonymous,
  // matching Cypress's cy.clearCookies() + cy.request('/').
  loggedOutRequest: async ({}, use) => {
    const context = await playwrightRequest.newContext({
      baseURL: BASE_URL,
      storageState: { cookies: [], origins: [] },
    });
    await use(context);
    await context.dispose();
  },
});

export { expect } from "@playwright/test";
