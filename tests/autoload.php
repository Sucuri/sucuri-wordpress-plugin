<?php
// bootstrap file used only by tests

// Define the constant with an empty value to stop
// the `undefined constant WP_CONTENT_DIR` errors when running tests.
define('WP_CONTENT_DIR', '');
define('SUCURISCAN_INIT', true);
define('BASE_DIR', __DIR__ . '/..');

// TODO: Copy fixtures to a temporary location in the future,
// so they can be mutated when needed.
define('SUCURI_DATA_STORAGE', BASE_DIR . '/tests/fixtures');

require BASE_DIR . '/src/base.lib.php';
require BASE_DIR . '/src/cache.lib.php';
