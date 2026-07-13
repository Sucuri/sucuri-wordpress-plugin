#!/bin/bash
set -e

# Create or repair users.
ensure_user() {
    local login="$1"
    local email="$2"
    local role="$3"

    if wp user get "$login" --field=ID >/dev/null 2>&1; then
        wp user update "$login" --user_email="$email" --role="$role" --user_pass=password >/dev/null
    else
        wp user create "$login" "$email" --role="$role" --user_pass=password >/dev/null
    fi
}

ensure_user sucuri sucuri@sucuri.net author
ensure_user sucuri-admin sucuri-admin@sucuri.net administrator
ensure_user sucuri-reset sucuri-reset@sucuri.net author

# Create a large batch of users to exercise the paginated/searchable
# Two-Factor users table (mirrors WooCommerce-scale sites). These are created
# AFTER the named users above so the named users stay on the first page when
# ordered by ID ascending (25 users per page).
for i in $(seq 1 60); do
    n=$(printf '%03d' "$i")
    ensure_user "bulkuser-$n" "bulkuser-$n@sucuri.net" subscriber
done

# Install plugins
if wp plugin is-installed akismet; then
    wp plugin activate akismet >/dev/null
else
    wp plugin install akismet --activate
fi

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
printf '%s\n' '<?php echo "Hello, world!"; ?>' > 'wp-content/literal.(a|b)*.php'
printf '%s\n' '<?php echo "Hello, world!"; ?>' > 'wp-content/literal.a.php'

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
