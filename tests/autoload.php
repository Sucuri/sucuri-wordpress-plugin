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

if (file_exists(BASE_DIR . '/vendor/autoload.php')) {
    require BASE_DIR . '/vendor/autoload.php';
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($s)
    {
        return 'nonce';
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '')
    {
        return 'https://example.com/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('network_admin_url')) {
    function network_admin_url($path = '')
    {
        return 'https://example.com/wp-admin/network/' . ltrim($path, '/');
    }
}


if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value)
    {
        return $value;
    }
}

if (!function_exists('current_user_can')) {
    /**
     * Test stub for current_user_can. Accepts optional cap for compatibility.
     *
     * @param mixed $cap Optional capability name (ignored in tests).
     * @return bool Always true in test context.
     */
    function current_user_can($cap = null)
    {
        return true;
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user()
    {
        return (object) ['user_login' => 'admin', 'user_email' => 'admin@example.com', 'display_name' => 'Admin'];
    }
}

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