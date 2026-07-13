#!/bin/bash
# Corrupt the SUCURI_PLUG_* constants in wp-config.php for the corrupt-salt
# stability E2E test: strip the existing SUCURI_PLUG_KEY / SUCURI_PLUG_SALT
# define() lines and insert deterministic replacement values before WordPress
# boots, so the next request uses them while loading the plugin.
#
# The subsequent key re-save must use the loaded constants without rotating them,
# proving that a save and read use the same stable salt in one request.
#
# Lives in a script (invoked via runPluginScript) to avoid the nested single/
# double-quote escaping hell of passing this multi-statement PHP through
# `npx wp-env run tests-cli wp eval '...'` and a shell.
set -e

wp eval '
$config_path = ABSPATH . "wp-config.php";
if ( ! file_exists( $config_path ) ) {
    $config_path = ABSPATH . "../wp-config.php";
}
$config_path = realpath( $config_path );
if ( ! $config_path ) {
    fwrite( STDERR, "wp-config.php not found\n" );
    exit( 1 );
}

$content = file_get_contents( $config_path );

// Remove any existing SUCURI_PLUG_KEY / SUCURI_PLUG_SALT define() lines.
$content = preg_replace(
    "/^[^\n]*define\s*\(\s*[\x27\"]SUCURI_PLUG_(?:KEY|SALT)[\x27\"][^\n]*\n?/m",
    "",
    $content
);

// Insert deterministic 64-character hexadecimal defines before wp-settings.php
// is loaded. Appending after require_once makes the constants unavailable while
// active plugins bootstrap and behaves differently across wp-env versions.
$bad = substr( str_repeat( "badbad", 11 ), 0, 64 );
$block = "define(\x27SUCURI_PLUG_KEY\x27,  \x27" . $bad . "\x27);\n";
$block .= "define(\x27SUCURI_PLUG_SALT\x27, \x27" . $bad . "\x27);\n";

$marker = "/* That\x27s all, stop editing!";
$position = strpos( $content, $marker );
if ( $position === false ) {
    $marker = "require_once ABSPATH . \x27wp-settings.php\x27;";
    $position = strpos( $content, $marker );
}
if ( $position === false ) {
    fwrite( STDERR, "wp-config.php insertion marker not found\n" );
    exit( 1 );
}

$content = substr( $content, 0, $position ) . $block . substr( $content, $position );

file_put_contents( $config_path, $content, LOCK_EX );

echo "salt corrupted\n";
'
