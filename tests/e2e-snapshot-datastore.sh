#!/bin/bash
set -e

# Snapshot and restore the plugin datastore directory (wp-content/uploads/sucuri).
#
# Runs inside the wp-env cli container (cwd = WP docroot, /var/www/html),
# invoked from playwright/support/wp-cli.ts via runPluginScript().
#
#   snapshot <yes|no>                      Copy the datastore aside. Prints
#                                          {"dataPath","backupPath","existed"}.
#   restore  <dataPath> <backupPath> <yes|no>
#                                          Put it back and delete the backup.
#
# The <yes|no> argument to `snapshot` is whether to load the plugin while
# resolving the path: the suite's global setup runs before the plugin is known
# to be active, so it resolves SUCURI_DATA_STORAGE / WP_CONTENT_DIR directly
# instead of calling SucuriScan::dataStorePath(). The trailing <yes|no> on
# `restore` is the `existed` value returned by the matching snapshot.
#
# Why this exists at all: the plugin keeps every sucuriscan_* option in
# sucuri-settings.php inside this directory rather than in wp_options, so there
# is no database-level undo for plugin state. Copying the directory is the only
# general "put it back how you found it" the suite has, and nearly every test
# depends on it.
#
# Why it is one script rather than a handful of helper calls: each `wp-env run`
# is a docker exec costing roughly a second, and this runs around every test.
# Everything in here is a single invocation; the wp/PHP calls below are free by
# comparison.
#
# EDITING NOTE: each PHP block is passed to `wp eval` inside single quotes, so
# the PHP itself must not contain a single quote.

# Resolve the datastore path and refuse anything that is not safe to delete
# from. Reads SUCURI_LOAD_PLUGIN and SUCURI_DATA_PATH from the environment;
# prints the resolved path on line 1 and yes/no (directory exists) on line 2.
#
# SUCURI_DATA_PATH short-circuits resolution so `restore` can re-check the exact
# path it is about to remove files from, rather than trusting its caller. The
# destructive half must not be the unguarded half.
RESOLVE_PHP='
$given = getenv("SUCURI_DATA_PATH");

if ($given !== false && $given !== "") {
    $raw = $given;
} elseif (getenv("SUCURI_LOAD_PLUGIN") === "yes") {
    $raw = SucuriScan::dataStorePath();
} else {
    $raw = defined("SUCURI_DATA_STORAGE")
        ? SUCURI_DATA_STORAGE
        : WP_CONTENT_DIR . "/uploads/sucuri";
}

// realpath() the parent, not the directory itself: the directory may legitimately
// not exist yet, but its parent must resolve for the path to mean anything.
$parent = realpath(dirname($raw));
$path = $parent ? $parent . "/" . basename($raw) : $raw;
$path = rtrim($path, "/");

$unsafe = array(
    rtrim(ABSPATH, "/"),
    rtrim(WP_CONTENT_DIR, "/"),
    rtrim(WP_PLUGIN_DIR, "/"),
    rtrim(dirname(ABSPATH), "/"),
);

// A symlink is rejected because the copy-back would write through it to a
// directory that was never validated.
if ($path === ""
    || strpos($path, "/") !== 0
    || in_array($path, $unsafe, true)
    || is_link($path)
) {
    WP_CLI::error("refusing to touch unsafe datastore path: " . $path);
}

echo $path . "\n";
echo is_dir($path) ? "yes\n" : "no\n";
'

# Only these entries belong to the plugin. Globbing rather than wiping the whole
# directory is what keeps a mistake in path resolution from being destructive.
copy_datastore_entries() {
    local source="$1"
    local target="$2"

    for entry in "$source"/sucuri-* "$source"/.htaccess "$source"/index.html; do
        [ ! -e "$entry" ] || cp -a "$entry" "$target/"
    done
}

resolve_datastore() {
    # $1 = yes|no (load the plugin), $2 = optional explicit path to validate
    if [ "$1" = yes ]; then
        SUCURI_LOAD_PLUGIN=yes SUCURI_DATA_PATH="${2:-}" wp eval "$RESOLVE_PHP"
    else
        SUCURI_LOAD_PLUGIN=no SUCURI_DATA_PATH="${2:-}" \
            wp eval --skip-plugins --skip-themes "$RESOLVE_PHP"
    fi
}

snapshot_datastore() {
    local load_plugin="$1"
    local resolved data_path existed backup_path

    resolved="$(resolve_datastore "$load_plugin")"
    data_path="$(printf '%s\n' "$resolved" | sed -n 1p)"
    existed="$(printf '%s\n' "$resolved" | sed -n 2p)"

    backup_path="$(mktemp -d /tmp/sucuri-playwright-data.XXXXXX)"

    if [ "$existed" = yes ]; then
        mkdir -p "$backup_path/data"
        copy_datastore_entries "$data_path" "$backup_path/data"
    fi

    # json_encode rather than hand-rolled bash quoting: these are filesystem
    # paths and the caller parses the result as JSON.
    SUCURI_DATA_PATH="$data_path" \
    SUCURI_BACKUP_PATH="$backup_path" \
    SUCURI_EXISTED="$existed" \
        wp eval --skip-plugins --skip-themes '
echo wp_json_encode(array(
    "dataPath"   => getenv("SUCURI_DATA_PATH"),
    "backupPath" => getenv("SUCURI_BACKUP_PATH"),
    "existed"    => getenv("SUCURI_EXISTED") === "yes",
));
'
}

restore_datastore() {
    local data_path="$1"
    local backup_path="$2"
    local existed="$3"
    local stage

    # Re-validate before deleting anything. resolve_datastore exits non-zero via
    # WP_CLI::error if the path is not safe, and `set -e` stops us here.
    resolve_datastore no "$data_path" >/dev/null

    # Stage the restore next door first so a failure midway cannot leave the
    # datastore half-populated from two different tests.
    stage="$(dirname "$data_path")/.sucuri-data-restore.$$"
    rm -rf "$stage"

    if [ "$existed" = yes ]; then
        mkdir -p "$stage"
        # "$backup_path/data"/* does not match dotfiles, so .htaccess is named.
        for entry in "$backup_path/data"/* "$backup_path/data"/.htaccess; do
            [ ! -e "$entry" ] || cp -a "$entry" "$stage/"
        done
    fi

    if [ -d "$data_path" ]; then
        for entry in "$data_path"/sucuri-* "$data_path"/.htaccess "$data_path"/index.html; do
            [ ! -e "$entry" ] || rm -rf "$entry"
        done
    fi

    if [ "$existed" = yes ]; then
        mkdir -p "$data_path"
        for entry in "$stage"/* "$stage"/.htaccess; do
            [ ! -e "$entry" ] || mv "$entry" "$data_path/"
        done
    else
        # rmdir, not rm -rf: if anything unexpected is in there, leave it and
        # let the next run fail loudly rather than delete a stranger's files.
        rmdir "$data_path" 2>/dev/null || true
    fi

    rm -rf "$stage" "$backup_path"
}

case "${1:-}" in
    snapshot)
        snapshot_datastore "${2:-yes}"
        ;;
    restore)
        if [ -z "${2:-}" ] || [ -z "${3:-}" ] || [ -z "${4:-}" ]; then
            echo "restore requires <dataPath> <backupPath> <yes|no>" >&2
            exit 64
        fi
        restore_datastore "$2" "$3" "$4"
        ;;
    *)
        echo "usage: $(basename "$0") snapshot <yes|no>" >&2
        echo "       $(basename "$0") restore <dataPath> <backupPath> <yes|no>" >&2
        exit 64
        ;;
esac
