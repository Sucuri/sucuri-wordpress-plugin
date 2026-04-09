#!/bin/bash
# Seed a v:1 (AUTH_SALT-encrypted) WAF key payload so the E2E test can verify
# that the plugin auto-migrates it to v:2 (SUCURI_PLUG_*-encrypted) on first read.
#
# Also removes any existing SUCURI_PLUG_KEY / SUCURI_PLUG_SALT define() lines from
# wp-config.php so the migration path is exercised from a clean state.
set -e

wp eval '
$key_str = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb";
$context  = "sucuriscan_waf_key_v1";
$raw_salt = wp_salt( "auth" );
$enc_key  = substr( hash_hmac( "sha256", $context, $raw_salt, true ), 0, 32 );
$iv       = random_bytes( 12 );
$tag      = "";
$ct       = openssl_encrypt( $key_str, "aes-256-gcm", $enc_key, OPENSSL_RAW_DATA, $iv, $tag );
$payload  = array(
    "v"   => 1,
    "alg" => "aes-256-gcm",
    "iv"  => base64_encode( $iv ),
    "tag" => base64_encode( $tag ),
    "ct"  => base64_encode( $ct ),
);
update_option( "sucuriscan_secret_cloudproxy_apikey_enc", $payload, false );
delete_option( "sucuriscan_secret_cloudproxy_apikey" );

// Remove SUCURI_PLUG_* constants from wp-config.php so the first-run write
// path is exercised from scratch.
$config_path = ABSPATH . "wp-config.php";
if ( ! file_exists( $config_path ) ) {
    $config_path = ABSPATH . "../wp-config.php";
}
$config_path = realpath( $config_path );
if ( $config_path ) {
    $content = file_get_contents( $config_path );
    $content = preg_replace( "/^define\(['\"]SUCURI_PLUG_(KEY|SALT)['\"].*\n/m", "", $content );
    file_put_contents( $config_path, $content, LOCK_EX );
}

echo "v1 payload seeded\n";
'
