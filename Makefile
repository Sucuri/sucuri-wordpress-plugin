#!/bin/sh

.PHONY: e2e e2e-prepare e2e-scanner e2e-firewall unit-test update-translations

e2e: e2e-prepare e2e-scanner e2e-firewall

e2e-prepare:
	npx wp-env start
	npx wp-env clean all
	chmod +x tests/e2e-prepare.sh
	npx wp-env run tests-cli bash wp-content/plugins/$(notdir $(CURDIR))/tests/e2e-prepare.sh

e2e-scanner:
	npx cypress run --spec cypress/e2e/sucuri-scanner.cy.js

e2e-firewall:
	npx cypress run --spec cypress/e2e/sucuri-scanner-firewall.cy.js

unit-test:
	./vendor/bin/phpunit

update-translations:
	wp i18n make-pot . lang/sucuri-scanner.pot

git-archive:
	git archive -o ~/Desktop/sucuri-scanner.zip HEAD