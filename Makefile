#!/bin/sh

.PHONY: e2e e2e-prepare e2e-scanner e2e-firewall unit-test update-translations

# Define the .htaccess content
define HTACCESS_CONTENT
<FilesMatch "\.(?i:php)$">
  <IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
  </IfModule>
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
</FilesMatch>
endef
export HTACCESS_CONTENT

# Define the .htaccess content
define HTACCESS_LEGACY
<FilesMatch "\.(?i:php)$">
  <IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
  </IfModule>
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
</FilesMatch>

<Files archive-legacy.php>
  <IfModule !mod_authz_core.c>
    Allow from all
  </IfModule>
  <IfModule mod_authz_core.c>
    Require all granted
  </IfModule>
</Files>
endef
export HTACCESS_LEGACY

e2e: e2e-prepare e2e-scanner e2e-firewall

e2e-prepare:
	npx wp-env start
	npx wp-env clean all
	npx wp-env run tests-cli wp user create sucuri sucuri@sucuri.net --role=author --user_pass=password
	npx wp-env run tests-cli wp user create sucuri-admin sucuri-admin@sucuri.net --role=administrator --user_pass=password
	npx wp-env run tests-cli wp user create sucuri-reset sucuri-reset@sucuri.net --role=author --user_pass=password
	npx wp-env run tests-cli wp plugin install akismet --activate
	npx wp-env run tests-cli touch .htaccess
	npx wp-env run tests-cli touch wp-config-test.php
	npx wp-env run tests-cli bash -c 'touch wp-test-file-{1..100}.php'

	# Useful to test hardening
	npx wp-env run tests-cli bash -c 'mkdir wp-includes/test-1'
	npx wp-env run tests-cli bash -c 'touch wp-includes/test-1/test-{1..3}.php'
	npx wp-env run tests-cli touch wp-content/archive-legacy.php
	npx wp-env run tests-cli bash -c 'echo "<?php echo \"Hello, world!\"; ?>" >> wp-content/archive-legacy.php'
	npx wp-env run tests-cli touch wp-content/archive.php
	npx wp-env run tests-cli bash -c 'echo "<?php echo \"Hello, world!\"; ?>" >> wp-content/archive.php'

	# Create .htaccess file inside the wp-content directory with legacy hardening rules:
	npx wp-env run tests-cli touch wp-includes/.htaccess
	npx wp-env run tests-cli bash -c "echo '$$HTACCESS_CONTENT' > wp-includes/.htaccess"
	npx wp-env run tests-cli touch wp-content/.htaccess
	npx wp-env run tests-cli bash -c "echo '$$HTACCESS_LEGACY' > wp-content/.htaccess"

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