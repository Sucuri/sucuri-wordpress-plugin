.PHONY: e2e e2e-prepare e2e-scanner e2e-firewall unit-test

e2e: e2e-prepare e2e-scanner e2e-firewall

e2e-prepare:
	npx wp-env start
	npx wp-env clean all
	npx wp-env run tests-cli "wp user create sucuri sucuri@sucuri.net --role=author --user_pass=password"
	npx wp-env run tests-cli "wp plugin install akismet --activate"
	npx wp-env run tests-cli "touch .htaccess"

e2e-scanner:
	npx cypress run --spec cypress/integration/sucuri-scanner.js

e2e-firewall:
	npx cypress run --spec cypress/integration/sucuri-scanner-firewall.js

unit-test:
	./vendor/bin/phpunit
