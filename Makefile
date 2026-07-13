#!/bin/sh

.PHONY: e2e e2e-prepare e2e-install e2e-test e2e-features e2e-mutations e2e-ui unit-test update-translations git-archive

# Full local run: (re)provision a clean WordPress, then run the Playwright suite.
e2e: e2e-prepare e2e-test

# Start wp-env, reset to a clean state, and seed users / files / hardening
# fixtures. `wp-env clean all` makes this idempotent across runs.
e2e-prepare:
	npx wp-env start
	npx wp-env clean all
	chmod +x tests/*.sh
	npx wp-env run tests-cli bash wp-content/plugins/$(notdir $(CURDIR))/tests/e2e-prepare.sh

# Install the Playwright browser (chromium) and its OS dependencies.
e2e-install:
	npx playwright install --with-deps chromium

# Run the whole Playwright suite (setup -> features -> mutations).
e2e-test:
	npx playwright test

# Run only the non-destructive feature specs (setup runs automatically).
e2e-features: e2e-prepare
	npx playwright test --project=features

# Run the destructive / auth-affecting specs (setup + features run as deps).
e2e-mutations: e2e-prepare
	npx playwright test --project=mutations

# Interactive UI mode for debugging.
e2e-ui:
	npx playwright test --ui

unit-test:
	./vendor/bin/phpunit

update-translations:
	wp i18n make-pot . lang/sucuri-scanner.pot

git-archive:
	git archive -o ~/Desktop/sucuri-scanner.zip HEAD
