import { defineConfig, devices } from "@playwright/test";
import { ADMIN_STORAGE_STATE, BASE_URL } from "./playwright/support/env";

// Failed-page accessibility snapshots can include credentials and secret values.
process.env.PLAYWRIGHT_NO_COPY_PROMPT = "1";

/**
 * Playwright e2e configuration for the Sucuri Security plugin.
 *
 * Parallelism model — important: every test runs against ONE shared wp-env
 * WordPress instance (single DB + filesystem), and many flows mutate global
 * plugin state (settings, options, wp-config.php, auth/2FA). In-process
 * parallelism on that single instance is therefore unsafe, so `workers: 1` and
 * `fullyParallel: false` are the correct defaults. Each CI PHP matrix job has
 * its own isolated wp-env — see .github/workflows/end-to-end-tests.yml.
 *
 * Ordering is enforced via project dependencies:
 *   setup  ->  features (disjoint, non-destructive)  ->  mutations (destructive / auth-affecting)
 */
export default defineConfig({
  testDir: "./playwright/specs",
  outputDir: "./playwright/.results",

  fullyParallel: false,
  workers: 1,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,

  // Generous per-test budget: integrity processing (50+ files) and the
  // multi-step 2FA flows are legitimately slow.
  timeout: 90_000,
  expect: { timeout: 15_000 },

  reporter: process.env.CI ? [["github"], ["list"]] : [["list"]],

  use: {
    baseURL: BASE_URL,
    testIdAttribute: "data-cy",
    actionTimeout: 15_000,
    navigationTimeout: 30_000,
    // Browser artifacts can capture passwords, TOTP secrets, and wp-config.php
    // keys. GitHub annotations and console output are the only CI diagnostics.
    trace: "off",
    screenshot: "off",
    video: "off",
  },

  projects: [
    {
      name: "setup",
      testDir: "./playwright/support",
      testMatch: /global\.setup\.ts$/,
    },
    {
      name: "features",
      testDir: "./playwright/specs/features",
      dependencies: ["setup"],
      use: { ...devices["Desktop Chrome"], storageState: ADMIN_STORAGE_STATE },
    },
    {
      name: "mutations",
      testDir: "./playwright/specs/mutations",
      // Run after the non-destructive feature specs so global wipes / auth
      // changes can't corrupt them.
      dependencies: ["setup", "features"],
      use: { ...devices["Desktop Chrome"], storageState: ADMIN_STORAGE_STATE },
    },
  ],
});
