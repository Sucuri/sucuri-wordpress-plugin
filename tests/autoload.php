<?php
// bootstrap file used only by tests

require __DIR__ . '/constants.php';

error_reporting((E_ALL | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE) & ~E_DEPRECATED & ~E_USER_DEPRECATED);

$GLOBALS['wp_version'] = $GLOBALS['wp_version'] ?? '6.4.0';
$GLOBALS['locale'] = $GLOBALS['locale'] ?? 'en_US';

if (!function_exists('translate')) {
    function translate($text, $domain = null)
    {
        return $text;
    }
}


if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return (string) $url;
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($html)
    {
        // In tests, pass-through to simplify; production will use WP's kses.
        return (string) $html;
    }
}

// Patchwork must be loaded before any stub functions that Brain\Monkey
// will need to mock, so its stream wrapper is active when those files
// are require'd and can preprocess the function definitions.
$patchworkPath = BASE_DIR . '/vendor/antecedent/patchwork/Patchwork.php';
if (file_exists($patchworkPath)) {
    require_once $patchworkPath;
}

if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
    require BASE_DIR . '/vendor/autoload.php';
}

// WordPress stubs in a separate file so Patchwork's stream wrapper
// preprocesses them, allowing Brain\Monkey to mock them via Functions\when().
require __DIR__ . '/wp-stubs.php';

require BASE_DIR . '/src/base.lib.php';
require BASE_DIR . '/src/request.lib.php';
require BASE_DIR . '/src/fileinfo.lib.php';
require BASE_DIR . '/src/cache.lib.php';
require BASE_DIR . '/src/option.lib.php';
require BASE_DIR . '/src/cron.lib.php';
require BASE_DIR . '/src/event.lib.php';
require BASE_DIR . '/src/hook.lib.php';
require BASE_DIR . '/src/api.lib.php';
require BASE_DIR . '/src/mail.lib.php';
require BASE_DIR . '/src/command.lib.php';
require BASE_DIR . '/src/template.lib.php';
require BASE_DIR . '/src/permissions.lib.php';
if (!function_exists('sucuriscanMainPages')) {
    function sucuriscanMainPages()
    {
        return array(
            'sucuriscan' => 'Dashboard',
            'sucuriscan_firewall' => 'Firewall',
            'sucuriscan_settings' => 'Settings',
        );
    }
}
require BASE_DIR . '/src/fsscanner.lib.php';
require BASE_DIR . '/src/hardening.lib.php';
require BASE_DIR . '/src/interface.lib.php';
require BASE_DIR . '/src/auditlogs.lib.php';
require BASE_DIR . '/src/sitecheck.lib.php';
require BASE_DIR . '/src/wordpress-recommendations.lib.php';
require BASE_DIR . '/src/integrity.lib.php';
require BASE_DIR . '/src/firewall.lib.php';
require BASE_DIR . '/src/installer-skin.lib.php';
require BASE_DIR . '/src/cachecontrol.lib.php';
require BASE_DIR . '/src/topt.lib.php';