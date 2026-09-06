# Playwright e2e suite — authoring guide

End-to-end tests for the Sucuri Security WordPress plugin, running against a
single shared `wp-env` instance at `http://localhost:8889`.

## Layout

```
playwright.config.ts          # projects, testIdAttribute='data-cy', timeouts
playwright/
  support/                    # shared helpers + fixtures (this dir)
    env.ts                    # users, URLs, cookie/option names, plugin slug
    fixtures.ts               # extended `test` (`loggedOutRequest`, `cacheControlContent`)
    auth.ts                   # login / submitLogin / withFreshUser / addWafDismissCookie
    notices.ts                # expectNotice / notice / expectNoErrorNotice
    http.ts                   # response-header / 403 / 200 assertions
    audit-logs.ts             # seed seam + CSV export link resolution
    cache-control.ts          # typed seam over tests/e2e-seed-cache-control.sh
    scanner.ts                # typed seam over tests/e2e-seed-scanner.sh
    settings-general.ts       # typed seam over tests/e2e-seed-settings-general.sh
    two-factor-state.ts       # typed seam over tests/e2e-seed-two-factor.sh
    wp-cli.ts                 # wp-env cli helpers (options, eval, seeds)
    totp.ts                   # RFC-6238 TOTP generator (2FA)
    pages/two-factor.page.ts  # 2FA admin page object, login-challenge + backup-code helpers
    global.setup.ts           # `setup` project: provision users + admin storageState
  data/                       # JSON fixtures (audit logs)
  specs/
    unit/                     # pure logic, no browser/wp-env -> project "unit"
    features/                 # disjoint, non-destructive  -> project "features"
    mutations/                # global-destructive / auth-affecting -> project "mutations"
```

## Projects & ordering

Both `features` and `mutations` depend only on `setup`, so selecting one mutation
does not run every feature test first.
`workers: 1`, `fullyParallel: false` — the suite shares one mutable WordPress
instance, so in-process parallelism is unsafe. Each CI matrix entry uses its own
isolated wp-env.

- **unit/**: pure functions from `support/`. No browser, no `setup` dependency,
  no Docker — these run even when wp-env is down. Keep them that way; anything
  that needs WordPress belongs in one of the two projects below.
- **features/**: touch a disjoint slice of state, no global wipe, no auth change.
- **mutations/**: wipe/overwrite many options, change auth/2FA/passwords/keys,
  toggle the plugin, or need a dedicated seed.

## Conventions

- **Selectors**: `data-cy` is the configured test id → `page.getByTestId('…')`.
  For non-`data-cy` elements prefer `getByRole`/`getByLabel`; keep
  `input[name="…"]` when the field name is the natural stable hook. Avoid
  nth-child / class chains.
- **No fixed waits**: never `waitForTimeout`. Use web-first assertions
  (`expect(locator).toContainText/toHaveValue/toBeVisible`), `page.waitForResponse`
  for AJAX, and `page.route` to stub admin-ajax calls.
- **Notices**: `await expectNotice(page, 'exact substring')` — tolerant of the
  admin-notice prefix and of multiple notices on one page.
- **Auth**: specs inherit the admin `storageState` (no per-test login). For 2FA
  challenge flows use `browser.newContext()` with an explicit empty
  `storageState` and the helpers in `pages/two-factor.page.ts`.
- **Response headers (logged-out)**: use the `loggedOutRequest` fixture +
  `http.ts` helpers. For logged-in checks pass the authenticated `request` fixture.
- **No browser artifacts**: traces, screenshots, videos, HTML reports, and
  failure snapshots can expose credentials, TOTP secrets, or WordPress salts.
- **WP-side setup**: `wpEval()` is for a single self-contained statement. Anything
  multi-statement, or that would splice a JS value into PHP source, belongs in a
  `tests/e2e-seed-*.sh` script reached through `runPluginScript()` — with values
  passed in the environment and read with `getenv()`. The PHP stays readable and
  reviewable instead of turning into escaped string concatenation, and it keeps
  working when the same fixture is needed from `tests/e2e-prepare.sh` too. See
  `tests/e2e-corrupt-salt.sh` for the original statement of the rule.
  **Exception:** the generic snapshot/restore helpers in `wp-cli.ts` build
  multi-statement PHP inline and stay that way. They take arbitrary arguments
  rather than seeding a fixed fixture, and every value crosses the boundary as
  `JSON.stringify()` or a base64 payload, so nothing is spliced into PHP source.
  Converting them would mean inventing a JSON protocol per helper for no gain.
  The rule is about splicing and readability, not about the word "multi-statement".
- **Destructive shell**: anything that deletes or moves files runs from a script
  under `tests/`, not an inline `sh -c`. `tests/e2e-snapshot-datastore.sh` is the
  model: the path guard is written once, re-checked before the delete rather than
  only at capture time, and the whole operation is one `wp-env run` instead of
  four — which matters when it wraps all 79 tests.
- **Idempotency**: the shared fixture snapshots/restores `uploads/sucuri` around
  every test. Specs must additionally own `wp-config.php`, cron, users/posts, and
  files outside that directory through the helpers below.
- **Targeted runs**: use normal project dependencies when possible. `--no-deps`
  is safe only after `npm run test:e2e:setup` has refreshed authentication.
- **Single environment**: never run separate Playwright processes concurrently
  against one wp-env. A worker-scoped lock queues accidental overlap.

## Helper quick-reference

```ts
import { test, expect } from '../../support/fixtures';      // gives `loggedOutRequest`, `cacheControlContent`
// or: import { test, expect } from '@playwright/test';      // when no extra fixture needed

import { seedAuditQueue, auditLogsDownloadUrl } from '../../support/audit-logs';
import { quarantineFuturePosts, restoreFuturePosts } from '../../support/cache-control';
import { clearScannerDataStores, seedScannerFixtures } from '../../support/scanner';
import { writeIntegrityDatastore } from '../../support/settings-general';
import { resetTwoFactorState, createBulkUsers, deleteUsers } from '../../support/two-factor-state';

import { expectNotice, expectNoErrorNotice } from '../../support/notices';
import { login, submitLogin, withFreshUser, addWafDismissCookie } from '../../support/auth';
import {
  getOption, updateOption, deleteOption, wp, wpEval, runPluginScript,
  readWpConfig, readSettingsFileJson, ensureUser, getUserId,
  snapshotPluginData, restorePluginData, snapshotWpFiles, restoreWpFiles,
  snapshotCron, restoreCron, snapshotRawOptions, restoreRawOptions,
} from '../../support/wp-cli';
import { totp } from '../../support/totp';
import {
  expectHeaderEquals, expectHeaderContains, expectHeaderAbsent,
  expectForbidden, expectHelloWorld,
} from '../../support/http';
import {
  TwoFactorAdminPage, loginExpect2FA, expectChallenge,
  extractSecret, finishWithCode, completeSetupWithGeneratedCode,
  completeSetupWithBackupCodes, readBackupCodesFromModal,
  dismissBackupCodesModal, dismissBackupCodesModalIfPresent,
} from '../../support/pages/two-factor.page';
import { adminUser, testAdminUser, extraUser, resetUser } from '../../support/env';
```

See `specs/features/audit-logs.spec.ts` (route mocking + `waitForResponse`) and
`specs/mutations/settings-general.spec.ts` (notices + `wp-cli` pinning +
idempotent destructive flows) as canonical examples.

## Local commands

```bash
make e2e                         # preserve environment, run everything
make e2e-reset                   # destructive tests-DB reset + canonical seed
npm run test:e2e -- --project=unit     # pure logic, no Docker needed
npm run test:e2e -- --project=features --grep='header'
npm run test:e2e -- --project=mutations --grep='password'
npm run test:e2e:setup           # refresh auth before --no-deps/UI debugging
```

## Environment prerequisites

These are environment facts the suite depends on; when a spec fails for no
obvious reason, check them first.

- **`wp-config.php` must be writable** by the wp-env `cli` user, and
  `openssl aes-256-gcm` must be available — otherwise the plugin silently falls
  back to plaintext storage and the WAF plug-salt assertions fail for reasons
  that have nothing to do with the code under test.
- The integrity diff-utility toggle needs the Unix `diff` binary on the wp-env host.
- The "test alert" emails invoke real `wp_mail`, which is a silent no-op in
  wp-env. They are not stubbed, and nothing asserts delivery.
- `SUCURI_BASE_URL` must point at the local wp-env port (8889, or `WP_ENV_PORT`
  if set): the browser and the WP-CLI cleanup have to target the same
  installation, and `support/env.ts` throws if they diverge.
- The project runs a **single** wp-env environment, declared by
  `testsEnvironment: false` in `.wp-env.json`, so the containers are `cli` /
  `wordpress` / `mysql` and there are no `tests-*` counterparts.

## Deliberate coverage gaps

- **No live-WAF tests.** Exercising a real firewall API key mutates a shared
  external Sucuri account (blocklist, cache flush) with no way to guarantee
  remote cleanup, and it exposes the key to failure artifacts. Do not add tests
  that talk to the live WAF; stub `admin-ajax.php` with `page.route` instead.
- **Three `test.skip` tests** are kept written out rather than deleted, each with
  its reason and what enabling it would take, in the file header or above the
  test: "toggle hardening options" (`mutations/hardening.spec.ts`), "reset
  installed plugins" (`mutations/secret-keys.spec.ts`), and "last logins"
  (`features/last-logins.spec.ts`). They need dedicated fixture work and should
  not be switched on for a local subset run.
