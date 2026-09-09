#!/bin/bash
set -e

# Fixtures for the Settings · General spec.
#
# Runs inside the wp-env cli container (cwd = WP docroot, /var/www/html),
# invoked from playwright/support/settings-general.ts via runPluginScript().
#
#   write-integrity-datastore   Create an empty sucuri-integrity.php datastore,
#                               creating wp-content/uploads/sucuri/ if needed.
#
# Why the spec needs this: the datastore-deletion test asserts the exact string
# "1 out of 1 files have been deleted.", which only holds when precisely one
# writable datastore file exists at page load. Guaranteeing that file is present
# is cheaper and steadier than asserting on whatever the environment happens to
# have accumulated.
#
# The "<?php exit(0); ?>" prefix is not decoration — every file under
# dataStorePath() is PHP so a direct HTTP request returns nothing. A plain
# "[]" here would both break that guarantee and stop the plugin from reading it.
#
# `wp eval` runs WITHOUT --skip-plugins: dataStorePath() is a plugin method.
#
# EDITING NOTE: the PHP block is passed to `wp eval` inside single quotes, so
# the PHP itself must not contain a single quote.

case "${1:-}" in
    write-integrity-datastore)
        wp eval '
$path      = SucuriScan::dataStorePath("sucuri-integrity.php");
$directory = dirname($path);

if (!is_dir($directory)) {
    mkdir($directory, 0755, true);
}

file_put_contents($path, "<?php exit(0); ?>\n[]\n");
'
        ;;
    *)
        echo "usage: $(basename "$0") <write-integrity-datastore>" >&2
        exit 64
        ;;
esac
