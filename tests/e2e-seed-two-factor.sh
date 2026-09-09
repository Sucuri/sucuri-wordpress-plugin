#!/bin/bash
set -e

# Fixtures for the Two-Factor Authentication spec.
#
# Runs inside the wp-env cli container (cwd = WP docroot, /var/www/html),
# invoked from playwright/support/two-factor-state.ts via runPluginScript().
#
#   reset <json-logins>      Force 2FA back to a disabled, un-enrolled state.
#   seed-bulk-users <count>  Ensure bulkuser-001..count exist; print (as a JSON
#                            array) only the IDs this call actually created.
#   delete-users <json-ids>  Delete the given user IDs.
#
# List arguments arrive as one JSON string and are decoded inside PHP, so no
# caller-supplied value is ever spliced into PHP source.
#
# `wp eval` runs WITHOUT --skip-plugins, unlike the WAF seeds next door: `reset`
# calls SucuriScanOption::updateOption(), which does not exist until the plugin
# has loaded. Adding the flag here would fail with an undefined-class fatal.
#
# EDITING NOTE: each PHP block is passed to `wp eval` inside single quotes, so
# the PHP itself must not contain a single quote. Use double-quoted PHP strings,
# and $wpdb->prepare() rather than inline SQL literals.

case "${1:-}" in
    reset)
        SUCURI_2FA_LOGINS="${2:-}" wp eval '
$logins = json_decode(getenv("SUCURI_2FA_LOGINS"), true);

if (!is_array($logins)) {
    WP_CLI::error("two-factor reset: expected a JSON array of logins");
}

SucuriScanOption::updateOption(":twofactor_mode", "disabled");
SucuriScanOption::updateOption(":twofactor_users", array());

// Backup codes are dropped alongside the secret, and both matter. Leaving them
// behind breaks two things: a user whose secret was wiped could still complete a
// challenge with a stale code, and maybe_generate_for_user() is a no-op when a
// set already exists -- so the next enrollment would generate nothing and the
// one-time reveal modal the backup-codes spec asserts would never appear.
foreach (get_users(array("fields" => "ID")) as $userId) {
    delete_user_meta($userId, "sucuriscan_topt_secret_key");
    delete_user_meta($userId, "sucuriscan_topt_last_success");
    delete_user_meta($userId, "sucuriscan_topt_backup_codes");
}

// A pending-login challenge lives in a transient. Leaving a stale one behind
// lets the next test skip the very challenge it is trying to assert.
global $wpdb;

$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        "_transient_sucuri_2fa_%",
        "_transient_timeout_sucuri_2fa_%"
    )
);

foreach ($logins as $login) {
    $user = get_user_by("login", $login);

    if ($user) {
        WP_Session_Tokens::get_instance($user->ID)->destroy_all();
    }
}
'
        ;;
    seed-bulk-users)
        SUCURI_BULK_USER_COUNT="${2:-}" wp eval '
$count = (int) getenv("SUCURI_BULK_USER_COUNT");

if ($count < 1) {
    WP_CLI::error("two-factor fixture: seed-bulk-users needs a positive count");
}

$created = array();

for ($i = 1; $i <= $count; $i++) {
    $login = sprintf("bulkuser-%03d", $i);

    // Already present from an earlier run: leave it alone AND leave it out of
    // the result, so the caller only ever deletes users this call brought into
    // being. That is what makes repeated runs non-destructive.
    if (get_user_by("login", $login)) {
        continue;
    }

    $userId = wp_insert_user(array(
        "user_login" => $login,
        "user_email" => $login . "@sucuri.net",
        "user_pass"  => "password",
        "role"       => "subscriber",
    ));

    if (is_wp_error($userId)) {
        WP_CLI::error("two-factor fixture: could not create $login");
    }

    $created[] = (int) $userId;
}

echo wp_json_encode($created);
'
        ;;
    delete-users)
        SUCURI_USER_IDS="${2:-}" wp eval '
$ids = json_decode(getenv("SUCURI_USER_IDS"), true);

if (!is_array($ids)) {
    WP_CLI::error("two-factor fixture: delete-users expected a JSON array of IDs");
}

foreach ($ids as $id) {
    wp_delete_user((int) $id);
}
'
        ;;
    *)
        echo "usage: $(basename "$0") <reset|seed-bulk-users|delete-users> <argument>" >&2
        exit 64
        ;;
esac
