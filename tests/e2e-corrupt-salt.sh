#!/bin/bash
# Corrupt the SUCURI_PLUG_* constants in wp-config.php for the corrupt-salt
# stability E2E test: strip the existing SUCURI_PLUG_KEY / SUCURI_PLUG_SALT
# define() lines and append deterministic replacement values.
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

// Append deterministic 64-character hexadecimal defines.
$bad = substr( str_repeat( "badbad", 11 ), 0, 64 );
$content .= "define(\x27SUCURI_PLUG_KEY\x27,  \x27" . $bad . "\x27);\n";
$content .= "define(\x27SUCURI_PLUG_SALT\x27, \x27" . $bad . "\x27);\n";

file_put_contents( $config_path, $content, LOCK_EX );

echo "salt corrupted\n";
'
