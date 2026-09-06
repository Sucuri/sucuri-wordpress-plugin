#!/bin/bash
set -e

# Fixtures for the Audit Logs spec.
#
# Runs inside the wp-env cli container (cwd = WP docroot, /var/www/html),
# invoked from playwright/support/audit-logs.ts via runPluginScript().
#
#   seed-queue   Drop the queue and log datastores, then report one warning and
#                one notice event so the filter assertions have a known corpus.
#
# The two events are recreated before every test rather than relied on from
# tests/e2e-prepare.sh: the filter test asserts on both an event type and a
# message substring, so a run that happened to log something else first would
# otherwise shift the expected rows.
#
# Deleting the datastores first is what keeps this idempotent — reportEvent()
# appends, so re-seeding without the unlink would grow the corpus on every test.
#
# `wp eval` runs WITHOUT --skip-plugins: every call below is a plugin class.
#
# EDITING NOTE: the PHP block is passed to `wp eval` inside single quotes, so
# the PHP itself must not contain a single quote.

case "${1:-}" in
    seed-queue)
        wp eval '
@unlink(SucuriScan::dataStorePath("sucuri-auditqueue.php"));
@unlink(SucuriScan::dataStorePath("sucuri-auditlogs.php"));

SucuriScanEvent::reportWarningEvent("Plugin activated: Akismet Anti-spam");
SucuriScanEvent::reportNoticeEvent("User authentication succeeded: admin");
'
        ;;
    *)
        echo "usage: $(basename "$0") <seed-queue>" >&2
        exit 64
        ;;
esac
