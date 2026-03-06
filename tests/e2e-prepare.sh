#!/bin/bash
set -e

# Create users
wp user create sucuri sucuri@sucuri.net --role=author --user_pass=password
wp user create sucuri-admin sucuri-admin@sucuri.net --role=administrator --user_pass=password
wp user create sucuri-reset sucuri-reset@sucuri.net --role=author --user_pass=password

# Install plugins
wp plugin install akismet --activate

# Create test files in WP root
touch .htaccess
touch wp-config-test.php
touch wp-test-file-{1..100}.php

# Hardening tests setup
mkdir -p wp-includes/test-1
touch wp-includes/test-1/test-{1..3}.php
touch wp-content/archive-legacy.php
echo '<?php echo "Hello, world!"; ?>' >> wp-content/archive-legacy.php
touch wp-content/archive.php
echo '<?php echo "Hello, world!"; ?>' >> wp-content/archive.php

# Create .htaccess file inside the wp-includes directory
cat <<EOF > wp-includes/.htaccess
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

# Create .htaccess file inside the wp-content directory with legacy hardening rules
cat <<EOF > wp-content/.htaccess
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

# Seed legacy settings file with WAF API key for migration tests
mkdir -p wp-content/uploads/sucuri
cat <<'EOF' > wp-content/uploads/sucuri/sucuri-settings.php
<?php exit(0); ?>
{"sucuriscan_cloudproxy_apikey":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb","sucuriscan_addr_header":"REMOTE_ADDR","sucuriscan_notify_to":"alerts@example.com"}
EOF

# Ensure secret options are cleared so migration runs
wp option delete sucuriscan_secret_cloudproxy_apikey_enc
wp option delete sucuriscan_secret_cloudproxy_apikey
