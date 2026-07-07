#!/bin/bash
set -e

# Idempotent re-seed of the "Allow Blocked PHP Files" hardening fixtures.
#
# Runs inside the wp-env tests-cli container (cwd = WP docroot, /var/www/html),
# invoked from the Playwright hardening spec via runPluginScript() in beforeAll.
#
# The legacy-rule-removal test PERMANENTLY rewrites wp-content/.htaccess (drops
# the <Files archive-legacy.php> grant), flipping archive-legacy.php from 200 to
# 403. Re-running this script restores the seeded state so the spec is
# re-runnable from a clean baseline on every run. Mirrors the hardening block of
# tests/e2e-prepare.sh (lines 17-58).

# PHP files served by Apache (echo "Hello, world!" so expectHelloWorld passes).
mkdir -p wp-includes/test-1
touch wp-includes/test-1/test-1.php wp-includes/test-1/test-2.php wp-includes/test-1/test-3.php

printf '%s\n' '<?php echo "Hello, world!"; ?>' > wp-content/archive-legacy.php
printf '%s\n' '<?php echo "Hello, world!"; ?>' > wp-content/archive.php

# wp-includes/.htaccess: deny-all so wp-includes/*.php returns 403 until allowlisted.
cat <<'EOF' > wp-includes/.htaccess
<FilesMatch "\.(?i:php)$">
  <IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
  </IfModule>
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
</FilesMatch>
EOF

# wp-content/.htaccess: deny-all + the legacy <Files archive-legacy.php> grant so
# archive-legacy.php returns 200 before the legacy-removal test runs.
cat <<'EOF' > wp-content/.htaccess
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
EOF
