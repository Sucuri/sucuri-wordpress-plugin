<?php
// WordPress function stubs for PHPUnit tests.
// This file is require'd AFTER vendor/autoload.php so Patchwork's stream
// wrapper can preprocess it — which is required for Brain\Monkey to be able
// to mock any of these functions with Functions\when().

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
    function current_user_can($cap = null)
    {
        return true;
    }
}

if (!function_exists('wp_doing_ajax')) {
    function wp_doing_ajax()
    {
        return false;
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user()
    {
        return (object) ['user_login' => 'admin', 'user_email' => 'admin@example.com', 'display_name' => 'Admin'];
    }
}

if (!function_exists('wp_cache_get')) {
    function wp_cache_get($key, $group = '', $force = false, &$found = null)
    {
        $found = false;
        return false;
    }
}

if (!function_exists('wp_cache_set')) {
    function wp_cache_set($key, $data, $group = '', $expire = 0)
    {
        return true;
    }
}

if (!function_exists('wp_cache_delete')) {
    function wp_cache_delete($key, $group = '')
    {
        return true;
    }
}

if (!function_exists('get_site_url')) {
    function get_site_url($blog_id = null, $path = '', $scheme = null)
    {
        return 'https://example.com' . (empty($path) ? '' : '/' . ltrim($path, '/'));
    }
}

if (!function_exists('is_multisite')) {
    function is_multisite()
    {
        return false;
    }
}

if (!function_exists('validate_file')) {
    function validate_file($file, $allowed_files = array())
    {
        if (!is_scalar($file) || '' === $file) {
            return 0;
        }
        if ('../' === $file || preg_match('|\.\./|', $file)) {
            return 1;
        }
        if (strpos($file, './') === 0) {
            return 2;
        }
        if (':' === substr($file, 1, 1)) {
            return 3;
        }
        if (!empty($allowed_files) && !in_array($file, $allowed_files, true)) {
            return 4;
        }
        return 0;
    }
}
