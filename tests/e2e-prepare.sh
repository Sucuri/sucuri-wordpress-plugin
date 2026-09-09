#!/bin/bash
set -e

# Canonical wp-env baseline. Run once by tests/e2e-reset-env.sh, after
# `wp-env reset all`, with cwd = the WP docroot.
#
# Per-spec fixtures are NOT reimplemented here. Each one is owned by the seed
# script its spec calls, and this file only warms them up so a full-suite run
# starts where a targeted run would arrive on its own. Adding or changing a
# fixture means editing its seed script; this file just delegates.

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

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

# A large batch of subscribers, to exercise the paginated/searchable Two-Factor
# users table (mirrors WooCommerce-scale sites). Seeded AFTER the named users
# above so those stay on the first page when ordered by ID ascending (25 per
# page). Owned by the two-factor seed script, which prints the IDs it created.
bash "$SCRIPT_DIR/e2e-seed-two-factor.sh" seed-bulk-users 60 >/dev/null

# Install plugins
if wp plugin is-installed akismet; then
    wp plugin activate akismet >/dev/null
else
    wp plugin install akismet --activate
fi

# ABSPATH .htaccess — read by the Website Info access-file-integrity panel.
# The spec touches this itself and snapshots/restores it; seeded here so the
# panel has something to report on a freshly reset environment.
touch .htaccess

# Scanner integrity baseline: wp-config-test.php + wp-test-file-1..100.php.
# NOTE: this also pins the wp_update_plugins cron, exactly as the scanner spec's
# beforeEach does. Harmless — the spec re-pins and restores it per test.
bash "$SCRIPT_DIR/e2e-seed-scanner.sh" seed 100

# Hardening fixtures: wp-includes/test-1/*.php, the hello-world PHP files under
# wp-content, and the deny-all .htaccess pair (with the legacy archive-legacy.php
# grant that the legacy-rule-removal test strips).
bash "$SCRIPT_DIR/e2e-seed-hardening.sh"
