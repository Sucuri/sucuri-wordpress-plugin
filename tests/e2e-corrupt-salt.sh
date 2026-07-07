#!/bin/bash
# Corrupt the SUCURI_PLUG_* constants in wp-config.php for the corrupt-salt
# recovery E2E test: strip the existing valid SUCURI_PLUG_KEY / SUCURI_PLUG_SALT
# define() lines and append two obviously-invalid "badbad...(x64)" defines.
#
# The subsequent key re-save (SucuriScanOption::updateOption) must replace these
# garbage defines with freshly-derived valid 64-hex constants and re-encrypt the
# key, proving the plugin recovers from a corrupted plug-salt.
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

// Append two deliberately-invalid 64-char defines (not valid derived hex).
$bad = substr( str_repeat( "badbad", 11 ), 0, 64 );
$content .= "define(\x27SUCURI_PLUG_KEY\x27,  \x27" . $bad . "\x27);\n";
$content .= "define(\x27SUCURI_PLUG_SALT\x27, \x27" . $bad . "\x27);\n";

file_put_contents( $config_path, $content, LOCK_EX );

echo "salt corrupted\n";
'
