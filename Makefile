#!/bin/sh

.PHONY: e2e e2e-start e2e-reset e2e-setup e2e-install e2e-test e2e-features e2e-mutations e2e-ui unit-test update-translations git-archive

# Normal runs preserve the existing tests environment and hold the workspace lock.
e2e:
	bash tests/e2e-with-lock.sh bash tests/e2e-run.sh

# Start the shared local environment without resetting either database.
e2e-start:
	bash tests/e2e-with-lock.sh npx wp-env start

# Explicit destructive recovery for CI or an irreparably dirty tests environment.
e2e-reset:
	bash tests/e2e-with-lock.sh bash tests/e2e-reset-env.sh

# Refresh essential users, 2FA state, and authenticated storageState.
e2e-setup:
	bash tests/e2e-with-lock.sh bash tests/e2e-run.sh --project=setup

# Install the Playwright browser (chromium) and its OS dependencies.
e2e-install:
	npx playwright install --with-deps chromium

# Run the whole Playwright suite (setup -> features -> mutations).
e2e-test:
	npx playwright test

# Run only the non-destructive feature specs (setup runs automatically).
e2e-features:
	bash tests/e2e-with-lock.sh bash tests/e2e-run.sh --project=features

# Run the destructive / auth-affecting specs (setup runs automatically).
e2e-mutations:
	bash tests/e2e-with-lock.sh bash tests/e2e-run.sh --project=mutations

# Interactive UI mode for debugging.
e2e-ui:
	bash tests/e2e-with-lock.sh bash tests/e2e-ui.sh

unit-test:
	./vendor/bin/phpunit

update-translations:
	wp i18n make-pot . lang/sucuri-scanner.pot

git-archive:
	git archive -o ~/Desktop/sucuri-scanner.zip HEAD
