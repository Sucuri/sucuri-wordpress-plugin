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
import net from "node:net";
import path from "node:path";
import { BASE_URL } from "./env";
import { restorePluginData, snapshotPluginData } from "./wp-cli";

interface Fixtures {
  loggedOutRequest: APIRequestContext;
  preservePluginData: boolean;
  pluginDataSnapshot: void;
}

interface WorkerFixtures {
  sharedEnvironmentLock: void;
}

function lockPort(): number {
  const key = `${path.resolve(__dirname, "../..")}::${new URL(BASE_URL).port}`;
  let hash = 0;
  for (const char of key) hash = (hash * 31 + char.charCodeAt(0)) >>> 0;
  return 20_000 + (hash % 20_000);
}

async function acquireSharedEnvironmentLock(): Promise<net.Server> {
  while (true) {
    try {
      return await new Promise<net.Server>((resolve, reject) => {
        const server = net.createServer();
        server.once("error", reject);
        server.listen(lockPort(), "127.0.0.1", () => resolve(server));
      });
    } catch (error) {
      const code = (error as NodeJS.ErrnoException).code;
      if (code !== "EADDRINUSE") throw error;
      await new Promise((resolve) => setTimeout(resolve, 250));
    }
  }
}

export const test = base.extend<Fixtures, WorkerFixtures>({
  sharedEnvironmentLock: [
    async ({}, use) => {
      if (process.env.SUCURI_E2E_LOCK_OWNER) {
        await use();
        return;
      }
      const server = await acquireSharedEnvironmentLock();
      await use();
      await new Promise<void>((resolve) => server.close(() => resolve()));
    },
    { auto: true, scope: "worker", timeout: 0 },
  ],
  preservePluginData: [true, { option: true }],
  pluginDataSnapshot: [
    async ({ preservePluginData }, use) => {
      if (!preservePluginData) {
        await use();
        return;
      }
      const snapshot = snapshotPluginData();
      await use();
      restorePluginData(snapshot);
    },
    { auto: true },
  ],
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
