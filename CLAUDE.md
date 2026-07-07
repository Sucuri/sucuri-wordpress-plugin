# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Sucuri Security is a WordPress plugin (procedural bootstrap + static "class-as-namespace" PHP,
no framework, no autoloader/Composer classes at runtime — everything is `require_once`d explicitly
in `sucuri.php`). It provides file integrity monitoring, malware scanning (via the remote SiteCheck
API), a firewall/WAF integration, security hardening, audit logging, and post-hack recovery tools.

## Commands

### PHP unit tests (PHPUnit 9 + Brain\Monkey for mocking WP functions)
```sh
composer install               # installs phpunit, brain/monkey, phpcs, wpcs
./vendor/bin/phpunit                          # run full suite
./vendor/bin/phpunit tests/TwoFactorTest.php  # run a single test file
./vendor/bin/phpunit --filter testMethodName  # run a single test method
make unit-test                 # same as ./vendor/bin/phpunit
```
Tests run with `processIsolation="true"` (see `phpunit.xml`) since the plugin relies heavily on
global constants/state defined once at bootstrap. Bootstrap is `tests/autoload.php`, which defines
WP constants (`tests/constants.php`), stubs a minimal set of WordPress functions, and then
`require`s the same `src/*.lib.php` files loaded by the real plugin (in the same order dependencies
demand). When a test needs a WP function not already stubbed, either add a `Functions\when(...)`
mock inside the test (Brain\Monkey), or add a global stub in `tests/autoload.php` if many tests need it.

### End-to-end tests (Cypress + wp-env / Docker)
```sh
cp cypress.config.js.example cypress.config.js   # first-time setup, gitignored
make e2e                # = e2e-prepare + e2e-scanner + e2e-firewall
make e2e-prepare        # starts wp-env, cleans DB, runs tests/e2e-prepare.sh inside the container
make e2e-scanner        # npx cypress run --spec cypress/e2e/sucuri-scanner.cy.js
make e2e-firewall       # npx cypress run --spec cypress/e2e/sucuri-scanner-firewall.cy.js
make e2e-waf-migration  # seeds via tests/e2e-seed-waf-migration.sh, then runs waf-migration spec
make e2e-waf-plug-salt  # seeds via tests/e2e-seed-waf-plug-salt.sh, then runs waf-plug-salt spec
npm run start / npm run stop   # start/stop the wp-env Docker environment directly
```
E2E requires Docker (wp-env). Firewall E2E only runs in CI when a `WAF_API_KEY` secret is present.

### Translations
```sh
make update-translations   # wp i18n make-pot . lang/sucuri-scanner.pot
```

### CI
- `.github/workflows/unit-tests.yml` — `composer install` + `make unit-test` on PHP 8.5.
- `.github/workflows/end-to-end-tests.yml` — matrix over PHP 7.4/8.0 × latest WordPress, runs the
  Cypress suites above via wp-env in Docker.
- `.github/workflows/deploy-to-wordpress-org.yml` — publishes tagged releases to wordpress.org
  (uses `.wordpress-org/` assets and `.gitattributes` `export-ignore` rules to strip dev files).

There is no lint/format npm script wired up; `composer.json` pulls in `squizlabs/php_codesniffer` and
`wp-coding-standards/wpcs` as dev dependencies, and `package.json` has `prettier` — apply the
WordPress PHP coding standard by convention (see existing file headers/style) when phpcs isn't
directly invocable.

## Architecture

### Bootstrap and load order
`sucuri.php` is the single plugin entry point read by WordPress. It:
1. Defines `SUCURISCAN_INIT` and other global constants (paths, cache lifetimes, API URLs/versions).
   Every other PHP file in `src/` guards itself with `if (!defined('SUCURISCAN_INIT') ...) { 403 }` at
   the top — these files are never meant to be requested directly.
2. `require_once`s all `src/*.lib.php` classes in dependency order, then page/ajax handlers
   (`src/pagehandler.php`), then per-page controllers (`src/lastlogins*.php`, `src/settings*.php`),
   then `src/globals.php` (hook wiring), then conditionally `src/cli.lib.php` for WP-CLI.
3. Registers WordPress action/filter hooks, the deactivation/uninstall hooks, and security headers.

`tests/autoload.php` mirrors this require order (a subset — only what's needed for testing) against
stubbed WP constants/functions, so if you add a new `src/*.lib.php` file with cross-file dependencies,
you likely need to add its `require` to both `sucuri.php` and `tests/autoload.php`.

### Code organization
- `src/*.lib.php` — core "library" classes, one class per file, named `SucuriScan<Thing>`
  (e.g. `SucuriScanOption`, `SucuriScanEvent`, `SucuriScanRequest`, `SucuriScanHardening`,
  `SucuriScanFirewall`, `SucuriScanIntegrity`, `SucuriScanSiteCheck`). Most public behavior is exposed
  via `public static function` methods — these are effectively namespaces/modules, not objects with
  instance state (a few exceptions like `SucuriScanFileInfo` are instantiated).
- `src/settings-*.php`, `src/lastlogins*.php` — per-admin-page controllers that assemble template data
  and register that page's own AJAX actions.
- `src/pagehandler.php` — single AJAX dispatch point. `sucuriscan_ajax_handlers()` maps a
  `form_action` string to a `[ClassName, method]` (or function) callable; `sucuriscan_ajax()` validates
  the nonce/capability then dispatches. Adding a new AJAX action means adding an entry here.
- `src/globals.php` — where all `add_action`/`add_filter` hooks are wired (menu registration, the
  file/user/theme/plugin change hooks routed through `SucuriScanHook::hook*`, cron schedules).
- `inc/tpl/*.tpl` — HTML templates rendered by `SucuriScanTemplate`, which does simple pseudo-variable
  substitution (`%%SUCURI.keyname%%` escaped, `%%%SUCURI.keyname%%%` raw) rather than a real template
  engine. Naming convention: `*.html.tpl` (full page/section), `*.snippet.tpl` (repeatable row/fragment).
- `src/option.lib.php` (`SucuriScanOption`) — central settings store. Options are addressed with a
  `:`-prefixed short name (e.g. `:headers_cors`) that gets expanded to `sucuriscan_<name>` internally;
  a subset are treated as "secret options" with separate encrypted get/update paths. Read via
  `SucuriScanOption::getOption(':foo')`, write via `SucuriScanOption::updateOption(':foo', $value)`.
- `src/event.lib.php` (`SucuriScanEvent`) — audit/event reporting, used throughout to log security
  events both locally and to the remote Sucuri API.
- `src/cli.lib.php` — WP-CLI command surface, only loaded when `WP_CLI` is defined.
- `cypress/` — E2E specs (`cypress/e2e/*.cy.js` for the current Cypress format, plus one legacy spec
  under `cypress/integration/`), fixtures, and a `plugins/index.js` that wires custom Cypress tasks
  (e.g. TOTP code generation for 2FA flows).

### Testing conventions
- Unit tests stub WordPress core functions with `Brain\Monkey\Functions\when(...)` per test in
  `setUp()`, rather than a full WP test framework — keeps tests fast and dependency-free.
- Fixtures live in `tests/fixtures/`; `SUCURI_DATA_STORAGE` in `tests/constants.php` points the
  plugin's data-storage path there during tests.
- Multiple `*Test.php` files test the same class from different angles (e.g. `IntegrityTest.php` +
  `IntegrityFilePathTest.php`, `HardeningTest.php` + `HardeningAllowlistRegexTest.php`) — check for a
  sibling test file before assuming a class is untested.
