# Cypress → Playwright e2e migration

This document summarizes the migration of the end-to-end suite from Cypress to
Playwright. The goal was not a 1:1 syntax rewrite but a stronger, faster, more
maintainable suite with **no loss of coverage**.

## What was migrated

Every Cypress test became a Playwright test. The single large
`cypress/e2e/sucuri-scanner.cy.js` (one giant `describe` + a Two-Factor block)
was split into focused, feature-scoped spec files; the three smaller specs were
migrated 1:1.

| Cypress source | Playwright spec(s) |
| --- | --- |
| `sucuri-scanner.cy.js` — WAF modal | `mutations/waf-modal.spec.ts` |
| …Light/Dark theme | `mutations/dashboard-theme.spec.ts` |
| …General settings (reset, IP discovery, reverse proxy, datastore, import, timezone) | `mutations/settings-general.spec.ts` |
| …Plugin de/activate | `mutations/plugin-lifecycle.spec.ts` |
| …Scanner / integrity diff utility | `features/scanner-integrity.spec.ts` |
| …Hardening allowlist | `mutations/hardening.spec.ts` |
| …Secret keys + autoupdater | `mutations/secret-keys.spec.ts` |
| …Alerts (recipients, IPs, subject, per-hour, brute-force, events, post-types) | `features/alerts.spec.ts` |
| …API service + checksum API + **malware scan target** | `features/api-service.spec.ts` |
| …Website info | `features/website-info.spec.ts` |
| …Audit logs (send + filter) | `features/audit-logs.spec.ts` |
| …Reset password | `mutations/reset-password.spec.ts` |
| …Cache-Control headers | `features/headers-cache-control.spec.ts` |
| …CSP headers | `features/headers-csp.spec.ts` |
| …CORS headers | `features/headers-cors.spec.ts` |
| …Last logins (was `it.skip`) | `features/last-logins.spec.ts` (`test.skip`) |
| …Two-Factor Authentication (10 tests) | `mutations/two-factor.spec.ts` |
| `sucuri-scanner-firewall.js` (live WAF key) | `mutations/firewall-waf-key.spec.ts` (gated) |
| `sucuri-scanner-waf-migration.cy.js` | `mutations/waf-migration.spec.ts` |
| `sucuri-scanner-waf-plug-salt.cy.js` | `mutations/waf-plug-salt.spec.ts` |

**80 tests across 21 files.** Coverage was verified test-by-test and assertion-by-assertion
against the Cypress source via an adversarial review pass (0 missing scenarios).

## Abstractions introduced

- **`playwright.config.ts`** — `testIdAttribute: 'data-cy'` (so the plugin's 116
  existing `data-cy` hooks map to `getByTestId`), three projects with
  dependencies, retries/traces/screenshots/video.
- **`support/global.setup.ts`** (`setup` project) — logs in once and saves an
  admin `storageState`, replacing Cypress's per-test `cy.session` login (the
  single biggest speedup). Also idempotently provisions the named test users.
- **`support/auth.ts`** — `login`/`submitLogin`/`addWafDismissCookie` and
  `saveAdminStorageState` (re-used to regenerate auth after secret-key rotation).
- **`support/totp.ts`** — RFC-6238 TOTP generator (port of the Cypress `totp` task).
- **`support/wp-cli.ts`** — `wp-env run tests-cli` wrappers (`getOption`/
  `updateOption`/`deleteOption`/`wpEval`/`readWpConfig`/`readSettingsFileJson`/
  `runPluginScript`/`getUserId`/…), replacing the Cypress `exec` task.
- **`support/notices.ts` / `support/http.ts`** — admin-notice and
  response-header/403/200 assertion helpers.
- **`support/pages/two-factor.page.ts`** — page object for the 2FA admin bulk
  controls + login-challenge flow (the only area complex enough to warrant one).
- **`support/fixtures.ts`** — adds a `loggedOutRequest` fixture for anonymous
  header checks (`cy.clearCookies()` + `cy.request('/')` equivalent).

## Parallelism & speed model

All tests run against **one shared `wp-env` WordPress instance** with global
mutable plugin state, so unrestricted in-process parallelism is unsafe. The
config therefore uses `workers: 1`, `fullyParallel: false`, and enforces ordering
via project dependencies:

```
setup  →  features (disjoint, non-destructive)  →  mutations (destructive / auth-affecting)
```

Speed gains over Cypress come from: reused `storageState` (no per-test UI login),
elimination of every fixed `cy.wait(ms)` in favour of web-first assertions /
`waitForResponse` / route stubbing, and Chromium-only execution. **Horizontal
scaling is via CI sharding** (`--shard=i/n`) — each shard is a separate job with
its own isolated `wp-env` (see `.github/workflows/end-to-end-tests.yml`, which
keeps the PHP 7.4 / 8.0 matrix as two isolated environments).

## Re-runnability / idempotency

Specs pin their preconditions in `beforeEach`/`beforeAll` (via `wp-cli`) and clean
up created entities in `afterAll`/`afterEach`, so repeated runs don't fail on
leftover state. Notable cases:

- **Two-Factor**: an always-run `afterEach` resets all 2FA (`reset_everything`)
  plus a `wp-cli` fallback that wipes per-user secrets/mode, so a mid-test failure
  can never leave the shared admin account locked behind 2FA.
- **Secret keys**: rotating keys invalidates the admin session for the whole site,
  so the spec regenerates the admin `storageState` in `afterAll`.
- **Hardening**: the legacy-rule-removal test permanently changes `.htaccess`, so
  `tests/e2e-seed-hardening.sh` re-seeds the fixtures in `beforeAll`.
- **Dashboard theme / WAF specs**: delete the WAF key and reset proxy/header
  options after running so the freemium baseline is restored.
- **WAF migration / plug-salt**: re-seed via `tests/e2e-seed-waf-migration.sh` /
  `tests/e2e-seed-waf-plug-salt.sh` / `tests/e2e-corrupt-salt.sh` each run.

## Coverage added / changed vs Cypress

- **Robust selectors**: `data-cy` → `getByTestId`; brittle `td:nth-child(n)` and
  global `*[class^=…]` selectors replaced with row-scoped / counted locators;
  checkbox state asserted with `toBeChecked()` instead of literal `checked` attrs.
- **Stale Cypress selectors fixed (these tests were silently broken/gated):**
  - `button[data-cy=sucuriscan-delete-wafkey]` no longer exists — delete is now
    driven through the firewall Options dropdown (`option[value="delete"]`).
  - "Clear cache by path" now asserts on `#firewall-page-clear-cache-response`
    (where the handler actually writes), not the stale `#firewall-clear-cache-response`.
- **Dead intercept removed**: the Cypress `get_firewall_settings` stub never
  matched the real `form_action='firewall_settings'`; replaced with a real
  `page.route` that neutralizes live WAF calls.
- **2FA**: the suite is actually **10 tests** (the dashboard self-enrollment test
  was migrated too); loose substring URL checks were tightened to distinguish the
  `…-2fa` (verify) vs `…-2fa-setup` (setup) screens.
- **Idempotency**: added pinning/cleanup that the Cypress suite relied on
  cross-test ordering for (e.g. API-service expected an earlier import test to
  have enabled it).

## Tests that could not be migrated exactly (with rationale)

- **Firewall live-WAF suite** (`firewall-waf-key.spec.ts`) — `test.skip`s itself
  unless `WAF_API_KEY` is set, because it calls the live Sucuri WAF API. Same
  gating Cypress used (`env.cypress_waf_api_key`). Assertions restored the
  `SUCURI:` notice prefix and replaced the stale delete-key/response selectors.
- **Three `it.skip` tests** were preserved as `test.skip` (not dropped) so their
  intent stays visible: "toggle hardening options" (premium/firewall + fragile
  filesystem/wp-config/cron state), "reset installed plugins" (re-installs akismet
  from the live network), and "last logins" (deletes on-disk log files and depends
  on a real failed-login record being captured).

## How to run

```bash
make e2e            # clean wp-env, seed fixtures, run the whole suite
make e2e-install    # one-time: install the Chromium browser
make e2e-features   # only the non-destructive feature specs
make e2e-mutations  # the destructive / auth-affecting specs
npm run typecheck   # tsc --noEmit
npx playwright test --ui   # interactive debugging
```

Set `WAF_API_KEY=<32hex>/<32hex>` to also exercise the live firewall suite.

## Follow-ups / environment notes

- **`wp-config.php` must be writable** by the `wp-env` `tests-cli` user and
  `openssl aes-256-gcm` must be available, or the WAF plug-salt assertions fail
  for environment reasons (the plugin falls back to plaintext storage).
- The integrity diff-utility toggle needs the Unix `diff` binary on the wp-env host.
- The "test alert" emails invoke real `wp_mail` (a silent no-op in wp-env); not stubbed.
- Live validation against a running `wp-env`/Docker was not performed in the
  migration environment (Docker was unavailable); the suite was validated
  statically (`tsc`, `playwright test --list`, prettier) and by adversarial
  coverage review. Run `make e2e` once in an environment with Docker to confirm
  green end-to-end.
