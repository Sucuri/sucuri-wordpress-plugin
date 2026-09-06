#!/bin/bash
set -e

# Fixtures for the Scanner / WordPress-integrity spec.
#
# Runs inside the wp-env cli container (cwd = WP docroot, /var/www/html),
# invoked from playwright/support/scanner.ts via runPluginScript().
#
#   seed <count>   Create wp-config-test.php plus wp-test-file-1..count.php in
#                  ABSPATH (the files the integrity scanner reports as unknown),
#                  and pin wp_update_plugins to a known schedule.
#   clear          Delete the integrity false-positive cache and the
#                  ignore-scanning data store; the plugin recreates both empty.
#
# `count` is owned by the spec (SCANNER_TEST_FILE_COUNT) and passed in, because
# the same number drives the snapshot/restore file list on the Playwright side.
# Keeping one source of truth is what stops the two lists from drifting apart.
#
# `wp eval` runs WITHOUT --skip-plugins, unlike the WAF seeds next door: `clear`
# calls SucuriScan::dataStorePath(), which does not exist until the plugin has
# loaded. Adding the flag here would fail with an undefined-class fatal.
#
# EDITING NOTE: each PHP block is passed to `wp eval` inside single quotes, so
# the PHP itself must not contain a single quote.

case "${1:-}" in
    seed)
        SUCURI_SCANNER_FILE_COUNT="${2:-}" wp eval '
$count = (int) getenv("SUCURI_SCANNER_FILE_COUNT");

if ($count < 1) {
    WP_CLI::error("scanner fixture: seed needs a positive file count");
}

touch(ABSPATH . "wp-config-test.php");

for ($i = 1; $i <= $count; $i++) {
    touch(ABSPATH . "wp-test-file-" . $i . ".php");
}

wp_clear_scheduled_hook("wp_update_plugins");
wp_schedule_event(time() + 3600, "twicedaily", "wp_update_plugins");
'
        ;;
    clear)
        wp eval '
@unlink(SucuriScan::dataStorePath("sucuri-integrity.php"));
@unlink(SucuriScan::dataStorePath("sucuri-ignorescanning.php"));
'
        ;;
    *)
        echo "usage: $(basename "$0") <seed|clear> [file-count]" >&2
        exit 64
        ;;
esac
