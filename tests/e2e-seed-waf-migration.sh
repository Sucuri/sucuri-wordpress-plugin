#!/bin/bash
set -e

wp option delete sucuriscan_secret_cloudproxy_apikey_enc --skip-plugins --skip-themes || true
wp option delete sucuriscan_secret_cloudproxy_apikey --skip-plugins --skip-themes || true
wp option delete sucuriscan_cloudproxy_apikey --skip-plugins --skip-themes || true
wp option delete sucuriscan_no_salt_encryption --skip-plugins --skip-themes || true
wp option delete sucuriscan_waf_key_decrypt_error --skip-plugins --skip-themes || true

wp eval --skip-plugins --skip-themes '
$path = defined( "SUCURI_DATA_STORAGE" )
    ? rtrim( SUCURI_DATA_STORAGE, "/" ) . "/sucuri-settings.php"
    : WP_CONTENT_DIR . "/uploads/sucuri/sucuri-settings.php";
$directory = dirname( $path );
if ( ! is_dir( $directory ) ) {
    mkdir( $directory, 0755, true );
}
$settings = array(
    "sucuriscan_cloudproxy_apikey" => "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb",
    "sucuriscan_addr_header" => "REMOTE_ADDR",
    "sucuriscan_notify_to" => "alerts@example.com",
);
file_put_contents( $path, "<?php exit(0); ?>\n" . wp_json_encode( $settings ) . "\n", LOCK_EX );
'
