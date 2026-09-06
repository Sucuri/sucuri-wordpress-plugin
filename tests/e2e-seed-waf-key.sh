#!/bin/bash
set -e

# Saves a WAF API key through the plugin's own option path, for the
# SUCURI_PLUG_* salt spec.
#
# Runs inside the wp-env cli container (cwd = WP docroot, /var/www/html),
# invoked from playwright/specs/mutations/waf-plug-salt.spec.ts via
# runPluginScript(), alongside e2e-seed-waf-plug-salt.sh and e2e-corrupt-salt.sh.
#
#   save <key>   SucuriScanOption::updateOption(":cloudproxy_apikey", <key>)
#
# Deliberately goes through updateOption() rather than writing the option
# directly: the whole point of the spec is that the save path encrypts as v:2
# and writes SUCURI_PLUG_KEY/SALT to wp-config.php. Bypassing it would seed the
# state the test is supposed to be producing.
#
# The key arrives through the environment, never spliced into PHP source, so a
# value containing quotes or a backslash cannot reshape the statement.
#
# `wp eval` runs WITHOUT --skip-plugins: SucuriScanOption is a plugin class, and
# the encryption side effects under test only happen with the plugin loaded.
#
# EDITING NOTE: the PHP block is passed to `wp eval` inside single quotes, so
# the PHP itself must not contain a single quote.

case "${1:-}" in
    save)
        if [ -z "${2:-}" ]; then
            echo "save requires an API key" >&2
            exit 64
        fi

        SUCURI_WAF_KEY="$2" wp eval '
SucuriScanOption::updateOption(":cloudproxy_apikey", getenv("SUCURI_WAF_KEY"));
'
        ;;
    *)
        echo "usage: $(basename "$0") save <api-key>" >&2
        exit 64
        ;;
esac
