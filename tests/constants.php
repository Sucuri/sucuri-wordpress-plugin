<?php

// Define the constant with an empty value to stop
// the `undefined constant WP_CONTENT_DIR` errors when running tests.
define('WP_CONTENT_DIR', '');
define('SUCURISCAN_INIT', true);
define('SUCURISCAN', 'sucuriscan');
define('SUCURISCAN_VERSION', 'test');
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define('BASE_DIR', __DIR__ . '/..');
define('SUCURISCAN_URL', 'https://example.com/wp-content/plugins/sucuri-scanner');
define('SUCURISCAN_PLUGIN_PATH', BASE_DIR);

// Copy fixtures to a per-run temp directory so tests that write audit events
// or cache entries cannot mutate the committed fixture files.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
$sucuriTmpDataStore = __DIR__ . '/tmp-' . getmypid();
mkdir($sucuriTmpDataStore, 0755, true);
foreach (glob(BASE_DIR . '/tests/fixtures/*') as $sucuriFixture) {
    if (is_file($sucuriFixture)) {
        copy($sucuriFixture, $sucuriTmpDataStore . '/' . basename($sucuriFixture));
    }
}
register_shutdown_function(function () use ($sucuriTmpDataStore) {
    foreach (glob($sucuriTmpDataStore . '/*') ?: [] as $f) {
        @unlink($f); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
    }
    @rmdir($sucuriTmpDataStore);
});
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define('SUCURI_DATA_STORAGE', $sucuriTmpDataStore);
define('ABSPATH', __DIR__ . '/../');
if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

