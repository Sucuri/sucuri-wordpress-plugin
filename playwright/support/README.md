# Playwright e2e suite — authoring guide

End-to-end tests for the Sucuri Security WordPress plugin, running against a
single shared `wp-env` instance at `http://localhost:8889`.

## Layout

```
playwright.config.ts          # projects, testIdAttribute='data-cy', timeouts
playwright/
  support/                    # shared helpers + fixtures (this dir)
    env.ts                    # users, URLs, cookie/option names, plugin slug
    fixtures.ts               # extended `test` (adds `loggedOutRequest`)
    auth.ts                   # login / submitLogin / addWafDismissCookie
    notices.ts                # expectNotice / notice / expectNoErrorNotice
    http.ts                   # response-header / 403 / 200 assertions
    wp-cli.ts                 # wp-env tests-cli helpers (options, eval, seeds)
    totp.ts                   # RFC-6238 TOTP generator (2FA)
    pages/two-factor.page.ts  # 2FA admin page object + login-challenge helpers
    global.setup.ts           # `setup` project: provision users + admin storageState
  data/                       # JSON fixtures (audit logs)
  specs/
    features/                 # disjoint, non-destructive  -> project "features"
    mutations/                # global-destructive / auth-affecting -> project "mutations"
```

## Projects & ordering

Both `features` and `mutations` depend only on `setup`, so selecting one mutation
does not run every feature test first.
`workers: 1`, `fullyParallel: false` — the suite shares one mutable WordPress
instance, so in-process parallelism is unsafe. Each CI matrix entry uses its own
isolated wp-env.

- **features/**: touch a disjoint slice of state, no global wipe, no auth change.
- **mutations/**: wipe/overwrite many options, change auth/2FA/passwords/keys,
  toggle the plugin, or need a dedicated seed.

## Conventions

- **Selectors**: `data-cy` is the configured test id → `page.getByTestId('…')`.
  For non-`data-cy` elements prefer `getByRole`/`getByLabel`; keep
  `input[name="…"]` when the field name is the natural stable hook. Avoid
  nth-child / class chains.
- **No fixed waits**: never port `cy.wait(ms)`. Use web-first assertions
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
- **Idempotency**: the shared fixture snapshots/restores `uploads/sucuri` around
  every test. Specs must additionally own `wp-config.php`, cron, users/posts, and
  files outside that directory through the helpers below.
- **Targeted runs**: use normal project dependencies when possible. `--no-deps`
  is safe only after `npm run test:e2e:setup` has refreshed authentication.
- **Single environment**: never run separate Playwright processes concurrently
  against one wp-env. A worker-scoped lock queues accidental overlap.

## Helper quick-reference

```ts
import { test, expect } from '../../support/fixtures';      // gives `loggedOutRequest`
// or: import { test, expect } from '@playwright/test';      // when no extra fixture needed

import { expectNotice, expectNoErrorNotice } from '../../support/notices';
import { login, submitLogin, addWafDismissCookie } from '../../support/auth';
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
npm run test:e2e -- --project=features --grep='header'
npm run test:e2e -- --project=mutations --grep='password'
npm run test:e2e:setup           # refresh auth before --no-deps/UI debugging
```

The intentionally skipped hardening-all, plugin-reinstall, and last-login tests
still require dedicated fixture work and should not be enabled for arbitrary
local subsets.
