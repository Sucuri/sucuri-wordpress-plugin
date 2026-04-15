<?php

/**
 * Code related to the option.lib.php interface.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Plugin options handler.
 *
 * Options are pieces of data that WordPress uses to store various preferences
 * and configuration settings. Listed below are the options, along with some of
 * the default values from the current WordPress install. By using the
 * appropriate function, options can be added, changed, removed, and retrieved,
 * from the wp_options table.
 *
 * The Options API is a simple and standardized way of storing data in the
 * database. The API makes it easy to create, access, update, and delete
 * options. All the data is stored in the wp_options table under a given custom
 * name. This page contains the technical documentation needed to use the
 * Options API. A list of default options can be found in the Option Reference.
 *
 * Note that the _site_ methods are essentially the same as their
 * counterparts. The only differences occur for WP Multisite, when the options
 * apply network-wide and the data is stored in the wp_sitemeta table under the
 * given custom name.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 * @see        https://codex.wordpress.org/Option_Reference
 * @see        https://codex.wordpress.org/Options_API
 */
class SucuriScanOption extends SucuriScanRequest
{
    /**
     * Default values for all the plugin's options.
     *
     * @return array Default values for all the plugin's options.
     */
    private static function getDefaultOptionValues()
    {
        $defaults = array(
            'sucuriscan_account' => '',
            'sucuriscan_addr_header' => 'HTTP_X_SUCURI_CLIENTIP',
            'sucuriscan_api_key' => false,
            'sucuriscan_api_protocol' => 'https',
            'sucuriscan_api_service' => 'disabled',
            'sucuriscan_auto_clear_cache' => 'disabled',
            'sucuriscan_checksum_api' => '',
            'sucuriscan_cloudproxy_apikey' => '',
            'sucuriscan_waf_prompt_dismissed_users' => array(),
            'sucuriscan_diff_utility' => 'disabled',
            'sucuriscan_dns_lookups' => 'enabled',
            'sucuriscan_email_subject' => '',
            'sucuriscan_emails_per_hour' => 5,
            'sucuriscan_emails_sent' => 0,
            'sucuriscan_ignored_events' => '',
            'sucuriscan_last_email_at' => time(),
            'sucuriscan_lastlogin_redirection' => 'enabled',
            'sucuriscan_maximum_failed_logins' => 30,
            'sucuriscan_notify_available_updates' => 'disabled',
            'sucuriscan_notify_bruteforce_attack' => 'disabled',
            'sucuriscan_notify_failed_login' => 'disabled',
            'sucuriscan_notify_plugin_activated' => 'enabled',
            'sucuriscan_notify_plugin_change' => 'enabled',
            'sucuriscan_notify_plugin_deactivated' => 'disabled',
            'sucuriscan_notify_plugin_deleted' => 'disabled',
            'sucuriscan_notify_plugin_installed' => 'disabled',
            'sucuriscan_notify_plugin_updated' => 'disabled',
            'sucuriscan_notify_post_publication' => 'enabled',
            'sucuriscan_notify_scan_checksums' => 'disabled',
            'sucuriscan_notify_settings_updated' => 'enabled',
            'sucuriscan_notify_success_login' => 'disabled',
            'sucuriscan_notify_theme_activated' => 'enabled',
            'sucuriscan_notify_theme_deleted' => 'disabled',
            'sucuriscan_notify_theme_editor' => 'enabled',
            'sucuriscan_notify_theme_installed' => 'disabled',
            'sucuriscan_notify_theme_updated' => 'disabled',
            'sucuriscan_notify_to' => '',
            'sucuriscan_notify_user_registration' => 'disabled',
            'sucuriscan_notify_website_updated' => 'disabled',
            'sucuriscan_notify_widget_added' => 'disabled',
            'sucuriscan_notify_widget_deleted' => 'disabled',
            'sucuriscan_plugin_version' => '0.0',
            'sucuriscan_prettify_mails' => 'disabled',
            'sucuriscan_revproxy' => 'disabled',
            'sucuriscan_runtime' => 0,
            'sucuriscan_selfhosting_fpath' => '',
            'sucuriscan_selfhosting_monitor' => 'disabled',
            'sucuriscan_site_version' => '0.0',
            'sucuriscan_sitecheck_target' => '',
            'sucuriscan_timezone' => 'UTC+00.00',
            'sucuriscan_use_wpmail' => 'enabled',
            'sucuriscan_twofactor_mode' => 'disabled',
            'sucuriscan_twofactor_users' => array(),
            'sucuriscan_preferred_theme' => 'dark',
            'sucuriscan_headers_cache_control' => 'disabled',
            'sucuriscan_headers_cache_control_options' => array(
                'front_page' => array(
                    'id' => 'front_page',
                    'title' => __('Front Page', 'sucuri-scanner'),
                    'max_age' => 21600,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 'unavailable',
                    'old_age_multiplier' => 'unavailable',
                ),

                'posts' => array(
                    'id' => 'posts',
                    'title' => __('Posts', 'sucuri-scanner'),
                    'max_age' => 43200,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 'unavailable',
                    'old_age_multiplier' => true,
                ),

                'pages' => array(
                    'id' => 'pages',
                    'title' => __('Pages', 'sucuri-scanner'),
                    'max_age' => 86400,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 'unavailable',
                    'old_age_multiplier' => 'unavailable',
                ),

                'main_index' => array(
                    'id' => 'main_index',
                    'title' => __('Main Index', 'sucuri-scanner'),
                    'max_age' => 21600,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 5,
                    'old_age_multiplier' => 'unavailable',
                ),

                'categories' => array(
                    'id' => 'categories',
                    'title' => __('Categories', 'sucuri-scanner'),
                    'max_age' => 86400,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 8,
                    'old_age_multiplier' => 'unavailable',
                ),

                'tags' => array(
                    'id' => 'tags',
                    'title' => __('Tags', 'sucuri-scanner'),
                    'max_age' => 86400,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 10,
                    'old_age_multiplier' => 'unavailable',
                ),

                'authors' => array(
                    'id' => 'authors',
                    'title' => __('Authors', 'sucuri-scanner'),
                    'max_age' => 86400,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 10,
                    'old_age_multiplier' => 'unavailable',
                ),

                'archives' => array(
                    'id' => 'archives',
                    'title' => __('Archives', 'sucuri-scanner'),
                    'max_age' => 86400,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 'unavailable',
                    'old_age_multiplier' => 'unavailable',
                ),

                'feeds' => array(
                    'id' => 'feeds',
                    'title' => __('Feeds', 'sucuri-scanner'),
                    'max_age' => 21600,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 'unavailable',
                    'old_age_multiplier' => 'unavailable',
                ),

                'attachment_pages' => array(
                    'id' => 'attachment_pages',
                    'title' => __('Attachment Pages', 'sucuri-scanner'),
                    'max_age' => 86400,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 'unavailable',
                    'old_age_multiplier' => 'unavailable',
                ),

                'search_results' => array(
                    'id' => 'search_results',
                    'title' => __('Search Results', 'sucuri-scanner'),
                    'max_age' => 86400,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 'unavailable',
                    'old_age_multiplier' => 'unavailable',
                ),

                '404_not_found' => array(
                    'id' => '404_not_found',
                    'title' => __('404 Not Found', 'sucuri-scanner'),
                    'max_age' => 86400,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 'unavailable',
                    'old_age_multiplier' => 'unavailable',
                ),

                'redirects' => array(
                    'id' => 'attachment',
                    'title' => __('Redirects', 'sucuri-scanner'),
                    'max_age' => 86400,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 'unavailable',
                    'old_age_multiplier' => 'unavailable',
                ),

                'woocommerce_products' => array(
                    'id' => 'woocommerce_products',
                    'title' => __('Woocommerce Products', 'sucuri-scanner'),
                    'max_age' => 86400,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 'unavailable',
                    'old_age_multiplier' => 'unavailable',
                ),

                'woocommerce_categories' => array(
                    'id' => 'woocommerce_categories',
                    'title' => __('Woocommerce Categories', 'sucuri-scanner'),
                    'max_age' => 86400,
                    's_maxage' => 0,
                    'stale_if_error' => 0,
                    'stale_while_revalidate' => 0,
                    'pagination_factor' => 'unavailable',
                    'old_age_multiplier' => 'unavailable',
                ),
            ),
            'sucuriscan_headers_csp' => 'disabled',
            'sucuriscan_headers_csp_options' => array(
                'base_uri' => array(
                    'id' => 'base_uri',
                    'title' => __('Base URI', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __(
                        'Restricts the URLs that can appear in the document’s <base> element. Commonly \'self\'.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'child_src' => array(
                    'id' => 'child_src',
                    'title' => __('Child Source', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __('Allowed sources for frames and nested browsing contexts.', 'sucuri-scanner'),
                    'enforced' => false
                ),
                'connect_src' => array(
                    'id' => 'connect_src',
                    'title' => __('Connect Source', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __('Allowed endpoints for fetch/XHR/WebSocket connections.', 'sucuri-scanner'),
                    'enforced' => false
                ),
                'default_src' => array(
                    'id' => 'default_src',
                    'title' => __('Default Source', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __(
                        'Fallback policy for resources if no other directive is defined.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'font_src' => array(
                    'id' => 'font_src',
                    'title' => __('Font Source', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __('Allowed font sources (e.g. self or font CDNs).', 'sucuri-scanner'),
                    'enforced' => false
                ),
                'form_action' => array(
                    'id' => 'form_action',
                    'title' => __('Form Action', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __('Restricts the URLs to which forms can be submitted.', 'sucuri-scanner'),
                    'enforced' => false
                ),
                'frame_ancestors' => array(
                    'id' => 'frame_ancestors',
                    'title' => __('Frame Ancestors', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __('Restricts which sites can embed this page in a frame.', 'sucuri-scanner'),
                    'enforced' => false
                ),
                'img_src' => array(
                    'id' => 'img_src',
                    'title' => __('Image Source', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __(
                        'Allowed image sources. Often includes self and possibly data: URIs.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'manifest_src' => array(
                    'id' => 'manifest_src',
                    'title' => __('Manifest Source', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __('Allowed sources for web app manifests.', 'sucuri-scanner'),
                    'enforced' => false
                ),
                'media_src' => array(
                    'id' => 'media_src',
                    'title' => __('Media Source', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __('Allowed sources for audio and video content.', 'sucuri-scanner'),
                    'enforced' => false
                ),
                'navigate_to' => array(
                    'id' => 'navigate_to',
                    'title' => __('Navigate To', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __(
                        'Restricts which URLs the document can navigate to (e.g., links).',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'object_src' => array(
                    'id' => 'object_src',
                    'title' => __('Object Source', 'sucuri-scanner'),
                    'value' => "'none'",
                    'type' => 'text',
                    'description' => __(
                        'Allowed sources for <object>, <embed>, or <applet>. Usually \'none\'.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'script_src' => array(
                    'id' => 'script_src',
                    'title' => __('Script Source', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __(
                        'Allowed script sources. Avoid \'unsafe-inline\'; consider nonces.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'script_src_attr' => array(
                    'id' => 'script_src_attr',
                    'title' => __('Script Source Attribute', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __(
                        'Allowed sources for inline event handlers.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'script_src_elem' => array(
                    'id' => 'script_src_elem',
                    'title' => __('Script Source Element', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __(
                        'Allowed sources for <script> elements. Falls back to script-src if missing.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'style_src' => array(
                    'id' => 'style_src',
                    'title' => __('Style Source', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __(
                        'Allowed style sources. Avoid \'unsafe-inline\'; consider nonces.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'style_src_attr' => array(
                    'id' => 'style_src_attr',
                    'title' => __('Style Source Attribute', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __('Allowed sources for inline style attributes.', 'sucuri-scanner'),
                    'enforced' => false
                ),
                'style_src_elem' => array(
                    'id' => 'style_src_elem',
                    'title' => __('Style Source Element', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __('Allowed sources for <style> and <link> elements.', 'sucuri-scanner'),
                    'enforced' => false
                ),
                'worker_src' => array(
                    'id' => 'worker_src',
                    'title' => __('Worker Source', 'sucuri-scanner'),
                    'value' => "'self'",
                    'type' => 'text',
                    'description' => __('Allowed sources for web and service workers.', 'sucuri-scanner'),
                    'enforced' => false
                ),
                'sandbox' => array(
                    'id' => 'sandbox',
                    'title' => __('Sandbox', 'sucuri-scanner'),
                    'type' => 'multi_checkbox',
                    'options' => array(
                        'allow-downloads' => array(
                            'title' => __('Allow Downloads', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'allow-forms' => array(
                            'title' => __('Allow Forms', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'allow-modals' => array(
                            'title' => __('Allow Modals', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'allow-orientation-lock' => array(
                            'title' => __('Allow Orientation Lock', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'allow-pointer-lock' => array(
                            'title' => __('Allow Pointer Lock', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'allow-popups' => array(
                            'title' => __('Allow Popups', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'allow-popups-to-escape-sandbox' => array(
                            'title' => __('Allow Popups to Escape Sandbox', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'allow-presentation' => array(
                            'title' => __('Allow Presentation', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'allow-same-origin' => array(
                            'title' => __('Allow Same Origin', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'allow-scripts' => array(
                            'title' => __('Allow Scripts', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'allow-top-navigation' => array(
                            'title' => __('Allow Top Navigation', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                    ),
                    'description' => __(
                        'Applies a sandbox to the page. Select tokens to allow exceptions.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'upgrade_insecure_requests' => array(
                    'id' => 'upgrade_insecure_requests',
                    'title' => __('Upgrade Insecure Requests', 'sucuri-scanner'),
                    'type' => 'multi_checkbox',
                    'options' => array(
                        'upgrade-insecure-requests' => array(
                            'title' => __('Upgrade Insecure Requests', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                    ),
                    'description' => __(
                        'Upgrade insecure requests to HTTPS. This is a security feature that prevents mixed content.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
            ),
            'sucuriscan_headers_cors' => 'disabled',
            'sucuriscan_headers_cors_options' => array(
                'Access-Control-Allow-Origin' => array(
                    'id' => 'access_control_allow_origin',
                    'title' => __('Access-Control-Allow-Origin', 'sucuri-scanner'),
                    'value' => '*',
                    'type' => 'text',
                    'description' => __(
                        'Specifies the origin that is allowed to access the resource.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'Access-Control-Expose-Headers' => array(
                    'id' => 'access_control_expose_headers',
                    'title' => __('Access-Control-Expose-Headers', 'sucuri-scanner'),
                    'value' => '',
                    'type' => 'text',
                    'description' => __(
                        'Specifies the headers that can be exposed when accessing the resource.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false,
                ),
                'Access-Control-Allow-Methods' => array(
                    'id' => 'access_control_allow_methods',
                    'title' => __('Access-Control-Allow-Methods', 'sucuri-scanner'),
                    'options' => array(
                        'GET' => array(
                            'title' => __('Allow GET method', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'POST' => array(
                            'title' => __('Allow POST method', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'PUT' => array(
                            'title' => __('Allow PUT method', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'DELETE' => array(
                            'title' => __('Allow DELETE method', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'OPTIONS' => array(
                            'title' => __('Allow OPTIONS method', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'PATCH' => array(
                            'title' => __('Allow PATCH method', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'HEAD' => array(
                            'title' => __('Allow HEAD method', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'TRACE' => array(
                            'title' => __('Allow TRACE method', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                        'CONNECT' => array(
                            'title' => __('Allow CONNECT method', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                    ),
                    'type' => 'multi_checkbox',
                    'description' => __(
                        'Specifies the methods allowed when accessing the resource.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'Access-Control-Allow-Headers' => array(
                    'id' => 'access_control_allow_headers',
                    'title' => __('Access-Control-Allow-Headers', 'sucuri-scanner'),
                    'value' => '',
                    'type' => 'text',
                    'description' => __(
                        'Specifies the headers allowed when accessing the resource.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'Access-Control-Allow-Credentials' => array(
                    'id' => 'access_control_allow_credentials',
                    'title' => __('Access-Control-Allow-Credentials', 'sucuri-scanner'),
                    'type' => 'multi_checkbox',
                    'options' => array(
                        'Access-Control-Allow-Credentials' => array(
                            'title' => __('Allow Credentials', 'sucuri-scanner'),
                            'enforced' => false,
                        ),
                    ),
                    'description' => __(
                        'Specifies whether credentials are allowed when accessing the resource.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
                'Access-Control-Max-Age' => array(
                    'id' => 'access_control_max_age',
                    'title' => __('Access-Control-Max-Age', 'sucuri-scanner'),
                    'value' => '86400',
                    'type' => 'text',
                    'description' => __(
                        'Specifies how long the results of a preflight request can be cached.',
                        'sucuri-scanner'
                    ),
                    'enforced' => false
                ),
            ),
        );

        return (array) apply_filters('sucuriscan_option_defaults', $defaults);
    }

    /**
     * Map of options that must be stored as secrets.
     *
     * @return array
     */
    private static function getSecretOptionMap()
    {
        return array(
            'sucuriscan_cloudproxy_apikey' => 'sucuriscan_secret_cloudproxy_apikey',
        );
    }

    /**
     * Check whether an option is stored as a secret.
     *
     * @param string $option Option name.
     * @return bool
     */
    private static function isSecretOption($option = '')
    {
        $option = self::varPrefix($option);
        $map = self::getSecretOptionMap();

        return array_key_exists($option, $map);
    }

    /**
     * Resolve the storage name for a secret option.
     *
     * @param string $option Option name.
     * @return string
     */
    private static function getSecretStorageName($option = '')
    {
        $option = self::varPrefix($option);
        $map = self::getSecretOptionMap();

        return array_key_exists($option, $map) ? $map[$option] : $option;
    }

    /**
     * Resolve the storage name for encrypted secret payloads.
     *
     * @param string $option Option name.
     * @return string
     */
    private static function getSecretEncryptedStorageName($option = '')
    {
        return self::getSecretStorageName($option) . '_enc';
    }

    /**
     * Check whether secret encryption can be enabled.
     *
     * @return bool
     */
    private static function canEncryptSecrets()
    {
        if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
            return false;
        }

        if (!function_exists('openssl_get_cipher_methods') || !function_exists('wp_salt')) {
            return false;
        }

        $methods = openssl_get_cipher_methods();

        return is_array($methods) && in_array('aes-256-gcm', $methods, true);
    }

    /**
     * Remove any existing SUCURI_PLUG_KEY and SUCURI_PLUG_SALT define() lines
     * from wp-config.php.
     *
     * @return bool True when the file was written (or had nothing to remove).
     */
    private static function removePluginSaltFromConfig()
    {
        $config_path = self::getConfigPath();

        if (!$config_path || !is_writable($config_path)) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
            return false;
        }

        $content = (string) file_get_contents($config_path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

        if (!preg_match('/^\s*define\s*\(\s*[\'"]SUCURI_PLUG_(?:KEY|SALT)[\'"]/m', $content)) {
            return true; // Nothing to remove.
        }

        $new_content = preg_replace(
            '/^[^\n]*define\s*\(\s*[\'"]SUCURI_PLUG_(?:KEY|SALT)[\'"][^\n]*\n?/m',
            '',
            $content
        );

        return (bool) file_put_contents($config_path, $new_content, LOCK_EX); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    }

    /**
     * TODO hook regeneration to an admin action and provide a UI button for manual rotation, 
     * with a warning about breaking changes if the plugin is active on multiple sites without network support.
     */

    /**
     * Regenerate the SUCURI_PLUG_* salt pair.
     *
     * Removes the existing constants from wp-config.php, derives a fresh pair
     * from the current WordPress AUTH salts, and writes them back.
     *
     * Because PHP constants cannot be re-defined within the same request, the
     * newly derived raw string is returned directly so callers can use it
     * without relying on the still-stale in-memory constants.
     *
     * @return string|bool New combined plug-key + plug-salt string, or false on failure.
     */
    private static function regeneratePluginSaltRaw()
    {
        if (!function_exists('wp_salt')) {
            return false;
        }

        self::removePluginSaltFromConfig();

        $auth_raw = wp_salt('auth');
        $plug_key = hash_hmac('sha256', 'sucuri_plug_key_v1', $auth_raw);
        $plug_salt = hash_hmac('sha256', 'sucuri_plug_salt_v1', $auth_raw);

        self::writePluginSaltToConfig($plug_key, $plug_salt);

        return $plug_key . $plug_salt;
    }

    /**
     * Append SUCURI_PLUG_KEY and SUCURI_PLUG_SALT define() lines to wp-config.php.
     *
     * The constants are inserted just before the "That's all" stop-editing comment.
     * If that marker is absent they are inserted before the wp-settings.php include.
     * Returns true when the constants are already present (no write needed) or when
     * the file was updated successfully.
     *
     * @param string $plug_key  64-char hex string.
     * @param string $plug_salt 64-char hex string.
     * @return bool
     */
    private static function writePluginSaltToConfig($plug_key, $plug_salt)
    {
        $config_path = self::getConfigPath();

        if (!$config_path || !is_writable($config_path)) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
            return false;
        }

        $content = (string) file_get_contents($config_path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if ($content === '') {
            return false;
        }

        $has_key  = (bool) preg_match('/^\s*define\s*\(\s*[\'"]SUCURI_PLUG_KEY[\'"]/m', $content);
        $has_salt = (bool) preg_match('/^\s*define\s*\(\s*[\'"]SUCURI_PLUG_SALT[\'"]/m', $content);

        if ($has_key && $has_salt) {
            return true; // Already present — nothing to do.
        }

        if ($has_key || $has_salt) {
            // Partial/broken state — one constant is missing but we cannot
            // safely re-write the block without duplicating the existing one.
            return false;
        }

        $block = sprintf(
            "define('SUCURI_PLUG_KEY',  '%s');\ndefine('SUCURI_PLUG_SALT', '%s');\n",
            $plug_key,
            $plug_salt
        );

        // Insert before the canonical stop-editing marker.
        $stop_marker = "/* That's all, stop editing!";
        $stop_pos = strpos($content, $stop_marker);

        if ($stop_pos !== false) {
            $new_content = substr($content, 0, $stop_pos)
                . $block
                . substr($content, $stop_pos);
        } else {
            // Fallback: insert before the wp-settings.php inclusion line.
            $lines = explode("\n", $content);
            $insert_at = count($lines);

            foreach ($lines as $i => $line) {
                if (preg_match('/require|include/i', $line)
                    && strpos($line, 'wp-settings.php') !== false
                ) {
                    $insert_at = $i;
                    break;
                }
            }

            array_splice($lines, $insert_at, 0, array(rtrim($block)));
            $new_content = implode("\n", $lines);
        }

        return (bool) file_put_contents($config_path, $new_content, LOCK_EX); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    }

    /**
     * Get or initialize the plugin-specific raw salt string.
     *
     * Priority:
     *  1. PHP constants SUCURI_PLUG_KEY and SUCURI_PLUG_SALT are already defined
     *     (written to wp-config.php on a previous run, or user-managed).
     *  2. First run: derive from the current WordPress AUTH salts, write the
     *     constants to wp-config.php, and return the combined string.
     *
     * @return string|bool Combined plug-key and plug-salt string, or false on failure.
     */
    private static function getPluginSaltRaw()
    {
        // Constants already available — either loaded from wp-config.php or
        // defined by the site owner before the plugin loaded.
        if (defined('SUCURI_PLUG_KEY') && defined('SUCURI_PLUG_SALT')) {
            return SUCURI_PLUG_KEY . SUCURI_PLUG_SALT;
        }

        // First run: derive plugin-specific values from WordPress AUTH salts and
        // persist them in wp-config.php so they survive future WP salt rotations.
        if (!function_exists('wp_salt')) {
            return false;
        }

        $auth_raw = wp_salt('auth');
        $plug_key = hash_hmac('sha256', 'sucuri_plug_key_v1', $auth_raw);
        $plug_salt = hash_hmac('sha256', 'sucuri_plug_salt_v1', $auth_raw);

        if (!self::writePluginSaltToConfig($plug_key, $plug_salt)) {
            return false;
        }

        return $plug_key . $plug_salt;
    }

    /**
     * Build encryption key from WordPress AUTH salts (legacy scheme, payload v:1).
     *
     * @return string|bool 32-byte key, or false on failure.
     */
    private static function getAuthEncryptionKey()
    {
        if (!function_exists('wp_salt')) {
            return false;
        }

        $context = 'sucuriscan_waf_key_v1';

        return substr(hash_hmac('sha256', $context, wp_salt('auth'), true), 0, 32);
    }

    /**
     * Build encryption key from plugin-specific SUCURI_PLUG_* salts (payload v:2).
     *
     * @return string|bool 32-byte key, or false on failure.
     */
    private static function getSecretEncryptionKey()
    {
        $plug_raw = self::getPluginSaltRaw();

        if ($plug_raw === false) {
            return false;
        }

        $context = 'sucuriscan_waf_key_v1';

        return substr(hash_hmac('sha256', $context, $plug_raw, true), 0, 32);
    }

    /**
     * Generate random bytes for encryption.
     *
     * @param int $length Number of bytes.
     * @return string|bool
     */
    private static function getSecretRandomBytes($length)
    {
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            return openssl_random_pseudo_bytes($length);
        }

        return false;
    }

    /**
     * Encrypt a secret value with AES-256-GCM.
     *
     * @param string      $plaintext Secret value.
     * @param string|null $raw_salt  Optional raw plug salt to use instead of
     *                               reading the runtime constants.  Pass this
     *                               when the constants have just been regenerated
     *                               and the new values are not yet available as
     *                               PHP constants (constants cannot be redefined
     *                               within the same request).
     * @return array|bool
     */
    private static function encryptSecretValue($plaintext, $raw_salt = null)
    {
        if (!self::canEncryptSecrets()) {
            return false;
        }

        if ($raw_salt !== null) {
            $context = 'sucuriscan_waf_key_v1';
            $key = substr(hash_hmac('sha256', $context, $raw_salt, true), 0, 32);
        } else {
            $key = self::getSecretEncryptionKey();
        }

        if (!$key) {
            return false;
        }

        $iv = self::getSecretRandomBytes(12);
        if (!$iv) {
            return false;
        }

        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false || $tag === '') {
            return false;
        }

        return array(
            'v' => 2,
            'alg' => 'aes-256-gcm',
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct' => base64_encode($ciphertext),
        );
    }

    /**
     * Decrypt a secret payload.
     *
     * @param array $payload Encrypted payload.
     * @return string|bool
     */
    private static function decryptSecretValue($payload, $raw_salt = null)
    {
        if (!self::canEncryptSecrets()) {
            return false;
        }

        if (!is_array($payload)
            || !isset($payload['v'])
            || !isset($payload['alg'])
            || !isset($payload['iv'])
            || !isset($payload['tag'])
            || !isset($payload['ct'])
        ) {
            return false;
        }

        $version = (int) $payload['v'];

        if ($payload['alg'] !== 'aes-256-gcm') {
            return false;
        }

        // Route decryption key by payload version:
        //   v:1 — legacy, encrypted with WordPress AUTH_SALT via wp_salt('auth').
        //   v:2 — current, encrypted with plugin-specific SUCURI_PLUG_* salt.
        // An explicit $raw_salt overrides version-based routing (used for fallback recovery).
        if ($raw_salt !== null) {
            $key = substr(hash_hmac('sha256', 'sucuriscan_waf_key_v1', $raw_salt, true), 0, 32);
        } elseif ($version === 1) {
            $key = self::getAuthEncryptionKey();
        } elseif ($version === 2) {
            $key = self::getSecretEncryptionKey();
        } else {
            return false;
        }

        if (!$key) {
            return false;
        }

        $iv = base64_decode($payload['iv']);
        $tag = base64_decode($payload['tag']);
        $ct = base64_decode($payload['ct']);

        if ($iv === false || $tag === false || $ct === false) {
            return false;
        }

        return openssl_decrypt(
            $ct,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }

    /**
     * Return the option name for decrypt error flags.
     *
     * @return string
     */
    private static function wafKeyDecryptErrorOption()
    {
        return 'sucuriscan_waf_key_decrypt_error';
    }

    /**
     * Set a decryption error flag for the WAF key.
     *
     * @param string $message Error detail for logging.
     * @return void
     */
    private static function setWafKeyDecryptError($message = '')
    {
        if (!function_exists('get_option') || !function_exists('update_option')) {
            return;
        }

        if ($message) {
            $message = sprintf(
                /* translators: %s: error message */
                __('Firewall API key decryption failed: %s', 'sucuri-scanner'),
                $message
            );
        }

        $option = self::wafKeyDecryptErrorOption();
        $current = get_option($option, array());
        $timestamp = isset($current['ts']) ? (int) $current['ts'] : 0;

        if ($timestamp && (time() - $timestamp) < 3600) {
            return;
        }

        update_option($option, array('ts' => time(), 'message' => (string) $message), false);

        if ($message) {
            SucuriScanEvent::reportWarningEvent($message);
        }
    }

    /**
     * Clear the WAF key decryption error flag.
     *
     * @return void
     */
    private static function clearWafKeyDecryptError()
    {
        if (function_exists('delete_option')) {
            delete_option(self::wafKeyDecryptErrorOption());
        }
    }

    /**
     * Render a decryption error notice on selected admin pages.
     *
     * @return void
     */
    public static function renderWafKeyDecryptNotice()
    {
        if (!function_exists('get_option')) {
            return;
        }

        if (!SucuriScanPermissions::canManagePlugin()) {
            return;
        }

        $flag = get_option(self::wafKeyDecryptErrorOption(), array());
        if (empty($flag) || !is_array($flag)) {
            return;
        }

        SucuriScanInterface::error(
            __('The Sucuri WAF API key could not be decrypted. Please re-save the key in the Firewall settings to restore functionality.', 'sucuri-scanner')
        );
    }

    /**
     * Retrieve a secret option from the database.
     *
     * @param string $option Option name.
     * @return mixed|null
     */
    private static function getSecretOption($option = '')
    {
        if (!function_exists('get_option')) {
            return null;
        }

        $option = self::varPrefix($option);
        $storage = self::getSecretStorageName($option);
        $encrypted_storage = self::getSecretEncryptedStorageName($option);

        $encrypted_payload = get_option($encrypted_storage, null);
        if ($encrypted_payload !== null) {
            $payload = is_array($encrypted_payload) ? $encrypted_payload : @json_decode($encrypted_payload, true);
            $decrypted = self::decryptSecretValue($payload);

            if ($decrypted !== false) {
                // Auto-migrate v:1 payloads (AUTH_SALT scheme) to v:2 (SUCURI_PLUG_* scheme).
                if (is_array($payload) && isset($payload['v']) && (int) $payload['v'] === 1
                    && self::canEncryptSecrets()
                ) {
                    $new_payload = self::encryptSecretValue($decrypted);
                    if ($new_payload !== false) {
                        update_option($encrypted_storage, $new_payload, false);
                    }
                }

                self::clearWafKeyDecryptError();
                return $decrypted;
            }

            // Fallback: the payload may have been encrypted with the raw salt derived
            // directly from wp_salt('auth') — this happens when SUCURI_PLUG_* constants
            // in wp-config.php were stale or user-defined (e.g. conditional defines that
            // survived the removal step), so the in-memory constants diverged from what
            // regeneratePluginSaltRaw() used during the save.  Try that key so the
            // system can self-heal without requiring a manual re-save.
            if (function_exists('wp_salt')
                && is_array($payload)
                && isset($payload['v'])
                && (int) $payload['v'] === 2
            ) {
                $auth_raw     = wp_salt('auth');
                $fallback_raw = hash_hmac('sha256', 'sucuri_plug_key_v1', $auth_raw)
                              . hash_hmac('sha256', 'sucuri_plug_salt_v1', $auth_raw);
                $decrypted = self::decryptSecretValue($payload, $fallback_raw);

                if ($decrypted !== false) {
                    // Re-encrypt with the constants-based key so subsequent reads succeed.
                    if (self::canEncryptSecrets()) {
                        $new_payload = self::encryptSecretValue($decrypted);
                        if ($new_payload !== false) {
                            update_option($encrypted_storage, $new_payload, false);
                        }
                    }
                    self::clearWafKeyDecryptError();
                    return $decrypted;
                }
            }

            self::setWafKeyDecryptError('decryption failed; please re-save the key.');
            return false;
        }

        $value = get_option($storage, null);

        if ($value !== null) {
            if (self::canEncryptSecrets()) {
                $payload = self::encryptSecretValue($value);
                if ($payload !== false) {
                    $updated = update_option($encrypted_storage, $payload, false);
                    if ($updated) {
                        delete_option($storage);
                    }
                }
            }

            self::clearWafKeyDecryptError();
            return $value;
        }

        // Backward compatibility: migrate legacy DB value to secret storage.
        $legacy = get_option($option, null);
        if ($legacy !== null) {
            self::updateSecretOption($option, $legacy);
            delete_option($option);
            return $legacy;
        }

        return null;
    }

    /**
     * Update a secret option in the database (non-autoloaded).
     *
     * Encrypts the value with the stable SUCURI_PLUG_* salt (written to
     * wp-config.php once on first run via getPluginSaltRaw()).  The salt is
     * never rotated on save — doing so rewrote wp-config.php on every key
     * insert and caused within-request key mismatches.
     *
     * @param string $option Option name.
     * @param mixed $value Option value.
     * @return bool
     */
    private static function updateSecretOption($option = '', $value = '')
    {
        if (!function_exists('update_option')) {
            return false;
        }

        $option = self::varPrefix($option);
        $storage = self::getSecretStorageName($option);
        $encrypted_storage = self::getSecretEncryptedStorageName($option);

        if (self::canEncryptSecrets()) {
            // Use the stable plugin-specific salt (written to wp-config.php once on
            // first run, never rotated).  Rotating on every save caused wp-config.php
            // to be rewritten on every key insert and created within-request key
            // mismatches because PHP constants cannot be redefined.
            $payload = self::encryptSecretValue($value);

            if ($payload !== false) {
                $encrypted_result = update_option($encrypted_storage, $payload, false);
                if ($encrypted_result) {
                    delete_option($storage);
                    self::clearWafKeyDecryptError();
                    return true;
                }
            }
        }

        delete_option($encrypted_storage);
        $result = update_option($storage, $value, false);
        self::clearWafKeyDecryptError();
        return $result;
    }

    /**
     * Delete a secret option from the database.
     *
     * @param string $option Option name.
     * @return bool
     */
    private static function deleteSecretOption($option = '')
    {
        if (!function_exists('delete_option')) {
            return false;
        }

        $option = self::varPrefix($option);
        $storage = self::getSecretStorageName($option);
        $encrypted_storage = self::getSecretEncryptedStorageName($option);

        delete_option($encrypted_storage);
        $deleted = delete_option($storage);

        // Remove legacy storage if still present.
        delete_option($option);

        self::clearWafKeyDecryptError();

        return $deleted;
    }

    /**
     * Delete an option from the settings file only.
     *
     * @param string $option Option name.
     * @return bool
     */
    private static function deleteOptionFromFile($option = '')
    {
        $options = self::getAllOptions();
        $option = self::varPrefix($option);

        if (array_key_exists($option, $options)) {
            unset($options[$option]);
            return self::writeNewOptions($options);
        }

        return false;
    }

    /**
     * Retrieve a secret option value from the DB or settings file.
     *
     * @param string $option Option name.
     * @param array $options Settings file options.
     * @return mixed
     */
    private static function getSecretOptionValue($option, $options)
    {
        $value = self::getSecretOption($option);

        if ($value !== null) {
            return $value;
        }

        if (array_key_exists($option, $options)) {
            $value = $options[$option];
            if (self::updateSecretOption($option, $value)) {
                self::deleteOptionFromFile($option);
            }
            return $value;
        }

        if (strpos($option, SUCURISCAN . '_') === 0) {
            $value = self::getDefaultOptions($option);
            // Only promote to secret storage when there is a real value.
            // An empty default must not trigger wp-config.php writes.
            if ($value !== '' && $value !== false && $value !== null) {
                self::updateSecretOption($option, $value);
            }
            return $value;
        }

        return false;
    }

    /**
     * Name of all valid plugin's options.
     *
     * @return array Name of all valid plugin's options.
     */
    public static function getDefaultOptionNames()
    {
        $options = self::getDefaultOptionValues();
        $names = array_keys($options);

        return $names;
    }

    /**
     * Retrieve the default values for some specific options.
     *
     * @param string $option List of options, or single option name.
     * @return mixed          The default values for the specified options.
     */
    private static function getDefaultOptions($option = '')
    {
        $default = self::getDefaultOptionValues();

        // Use framework built-in function.
        if (function_exists('get_option')) {
            $admin_email = get_option('admin_email');
            $default['sucuriscan_account'] = $admin_email;
            $default['sucuriscan_notify_to'] = $admin_email;
            $default['sucuriscan_email_subject'] = sprintf(
                /* translators: %1$s: domain, %2$s: event, %3$s: remote address */
                __('Sucuri Alert, %1$s, %2$s, %3$s', 'sucuri-scanner'),
                ':domain',
                ':event',
                ':remoteaddr'
            );
        }

        return @$default[$option];
    }

    /**
     * Returns path of the options storage.
     *
     * Returns the absolute path of the file that will store the options
     * associated to the plugin. This must be a PHP file without public access,
     * for which reason it will contain a header with an exit point to prevent
     * malicious people to read the its content. The rest of the file will
     * content a JSON encoded array.
     *
     * @return string File path of the options storage.
     */
    public static function optionsFilePath()
    {
        return self::dataStorePath('sucuri-settings.php');
    }

    /**
     * Returns an array with all the plugin options.
     *
     * NOTE: There is a maximum number of lines for this file, one is for the
     * exit point and the other one is for a single line JSON encoded string.
     * We will discard any other content that exceeds this limit.
     *
     * @return array Array with all the plugin options.
     */
    public static function getAllOptions()
    {
        $options = wp_cache_get('alloptions', SUCURISCAN);

        if ($options && is_array($options)) {
            return $options;
        }

        $options = array();
        $fpath = self::optionsFilePath();

        /* Use this over SucuriScanCache to prevent nested method calls */
        $content = SucuriScanFileInfo::fileContent($fpath);

        if ($content !== false) {
            // Refer to self::optionsFilePath to know why the number two.
            $lines = explode("\n", $content, 2);

            if (count($lines) >= 2) {
                $jsonData = json_decode($lines[1], true);

                if (is_array($jsonData) && !empty($jsonData)) {
                    $options = $jsonData;
                }
            }
        }

        wp_cache_set('alloptions', $options, SUCURISCAN);

        return $options;
    }

    /**
     * Write new options into the external options file.
     *
     * @param array $options Array with plugins options.
     * @return bool           True if the new options were saved, false otherwise.
     */
    public static function writeNewOptions($options = array())
    {
        wp_cache_delete('alloptions', SUCURISCAN);

        $fpath = self::optionsFilePath();
        $content = "<?php exit(0); ?>\n";
        $content .= @json_encode($options) . "\n";

        return (bool) @file_put_contents($fpath, $content);
    }

    /**
     * Returns data from the settings file or the database.
     *
     * To facilitate the development, you can prefix the name of the key in the
     * request (when accessing it) with a single colon, this method will automa-
     * tically replace that character with the unique identifier of the plugin.
     *
     * NOTE: The SucuriScanCache library is a better interface to read the
     * content of a configuration file following the same standard in the other
     * files associated to the plugin. However, this library makes use of this
     * method to retrieve the directory where the files are stored, if we use
     * this library for this specific task we will end up provoking a maximum
     * nesting method call warning.
     *
     * @see https://developer.wordpress.org/reference/functions/get_option/
     *
     * @param string $option Name of the option.
     * @return mixed          Value associated to the option.
     */
    public static function getOption($option = '')
    {
        $options = self::getAllOptions();
        $option = self::varPrefix($option);

        if (self::isSecretOption($option)) {
            return self::getSecretOptionValue($option, $options);
        }

        if (array_key_exists($option, $options)) {
            return $options[$option];
        }

        /**
         * Fallback to the default values.
         *
         * If the option is not set in the external options file then we must
         * search in the database for older data, this to provide backward
         * compatibility with older installations of the plugin. If the option
         * is found in the database we must insert it in the external file and
         * delete it from the database before the value is returned to the user.
         *
         * If the option is not in the external file nor in the database, and
         * the name starts with the same prefix used by the plugin then we must
         * return the default value defined by the author.
         *
         * Note that if the plain text file is not writable the method should
         * not delete the option from the database to keep backward compatibility
         * with previous installations of the plugin.
         */
        if (function_exists('get_option')) {
            $value = get_option($option);

            if ($value !== false) {
                if (strpos($option, SUCURISCAN . '_') === 0) {
                    $written = self::updateOption($option, $value);

                    if ($written === true) {
                        delete_option($option);
                    }
                }

                return $value;
            }
        }

        /**
         * Cache default value to stop querying the database.
         *
         * The option was not found in the database either, we will return the
         * data from the array of default values hardcoded in the source code,
         * then will attempt to write the default value into the flat settings
         * file to stop querying the database in subsequent requests.
         */
        if (strpos($option, SUCURISCAN . '_') === 0) {
            $value = self::getDefaultOptions($option);
            self::updateOption($option, $value);
            return $value;
        }

        return false;
    }

    /**
     * Update the value of an database' option.
     *
     * Use the method to update a named option/value pair to the options database
     * table. The option name value is escaped with a special database method before
     * the insert SQL statement but not the option value, this value should always
     * be properly sanitized.
     *
     * @see https://developer.wordpress.org/reference/functions/update_option/
     *
     * @param string $option Name of the option.
     * @param mixed $value New value for the option.
     * @return bool           True if option has been updated, false otherwise.
     */
    public static function updateOption($option = '', $value = '')
    {
        if (self::isSecretOption($option)) {
            return self::updateSecretOption($option, $value);
        }

        if (strpos($option, ':') === 0 || strpos($option, SUCURISCAN) === 0) {
            $options = self::getAllOptions();
            $option = self::varPrefix($option);
            $options[$option] = $value;

            return self::writeNewOptions($options);
        }

        return update_option($option, $value);
    }

    /**
     * Remove an option from the database.
     *
     * A safe way of removing a named option/value pair from the options database table.
     *
     * @see https://developer.wordpress.org/reference/functions/delete_option/
     *
     * @param string $option Name of the option to be deleted.
     * @return bool           True if option is successfully deleted, false otherwise.
     */
    public static function deleteOption($option = '')
    {
        if (self::isSecretOption($option)) {
            return self::deleteSecretOption($option);
        }

        if (strpos($option, ':') === 0 || strpos($option, SUCURISCAN) === 0) {
            $options = self::getAllOptions();
            $option = self::varPrefix($option);

            // Create/Modify option's value.
            if (array_key_exists($option, $options)) {
                unset($options[$option]);

                return self::writeNewOptions($options);
            }
        }

        return delete_option($option);
    }

    /**
     * Check whether a setting is enabled or not.
     *
     * @param string $option Name of the option to be deleted.
     * @return bool           True if the option is enabled, false otherwise.
     */
    public static function isEnabled($option = '')
    {
        return (bool) (self::getOption($option) === 'enabled');
    }

    /**
     * Check whether a setting is disabled or not.
     *
     * @param string $option Name of the option to be deleted.
     * @return bool           True if the option is disabled, false otherwise.
     */
    public static function isDisabled($option = '')
    {
        return (bool) (self::getOption($option) === 'disabled');
    }

    /**
     * Retrieve all the options stored by Wordpress in the database. The options
     * containing the word "transient" are excluded from the results, this method
     * compatible with multisite instances.
     *
     * @return array All the options stored by Wordpress in the database.
     */
    private static function getSiteOptions()
    {
        $settings = array();

        if (array_key_exists('wpdb', $GLOBALS)) {
            $results = $GLOBALS['wpdb']->get_results(
                'SELECT * FROM ' . $GLOBALS['wpdb']->options . ' WHERE opti'
                . 'on_name NOT LIKE "%_transient_%" ORDER BY option_id ASC'
            );

            foreach ($results as $row) {
                $settings[$row->option_name] = $row->option_value;
            }
        }

        $external = self::getAllOptions();

        foreach ($external as $option => $value) {
            $settings[$option] = $value;
        }

        return $settings;
    }

    /**
     * Check what Wordpress options were changed comparing the values in the database
     * with the values sent through a simple request using a GET or POST method.
     *
     * @param array $request The content of the global variable GET or POST considering SERVER[REQUEST_METHOD].
     * @return array          A list of all the options that were changes through this request.
     */
    public static function whatOptionsWereChanged($request = array())
    {
        $options_changed = array(
            'original' => array(),
            'changed' => array()
        );

        $site_options = self::getSiteOptions();

        foreach ($request as $req_name => $req_value) {
            if (
                array_key_exists($req_name, $site_options)
                && $site_options[$req_name] != $req_value
            ) {
                $options_changed['original'][$req_name] = $site_options[$req_name];
                $options_changed['changed'][$req_name] = $req_value;
            }
        }

        return $options_changed;
    }

    /**
     * Check the nonce comming from any of the settings pages.
     *
     * @return bool True if the nonce is valid, false otherwise.
     */
    public static function checkOptionsNonce()
    {
        // Create the option_page value if permalink submission.
        if (!isset($_POST['option_page']) && isset($_POST['permalink_structure'])) {
            $_POST['option_page'] = 'permalink';
        }

        /* check if the option_page has an allowed value */
        $option_page = SucuriScanRequest::post('option_page');

        if (!$option_page) {
            return false;
        }

        $action = '';
        $nonce = '_wpnonce';

        switch ($option_page) {
            case 'general':    /* no_break */
            case 'writing':    /* no_break */
            case 'reading':    /* no_break */
            case 'discussion': /* no_break */
            case 'media':      /* no_break */
            case 'options':    /* no_break */
                $action = $option_page . '-options';
                break;
            case 'permalink':
                $action = 'update-permalink';
                break;
        }

        /* check the nonce validity */
        return (bool) (
            !empty($action)
            && isset($_REQUEST[$nonce])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST[$nonce])), $action)
        );
    }

    /**
     * Returns a list of post-types.
     *
     * The list of post-types includes objects such as Post and Page but also
     * the transitions between each post type, for example, if there are posts
     * of type Draft and they change to Trash, this function will include a new
     * post type called "from_draft_to_trash" and so on.
     *
     * @return array List of post-types with transitions.
     */
    public static function getPostTypes()
    {
        $postTypes = get_post_types();
        $transitions = array(
            'new',
            'publish',
            'pending',
            'draft',
            'auto-draft',
            'future',
            'private',
            'inherit',
            'trash',
        );

        /* include post-type transitions */
        foreach ($transitions as $from) {
            foreach ($transitions as $to) {
                if ($from === $to) {
                    continue;
                }

                $event = sprintf('from_%s_to_%s', $from, $to);

                if (!array_key_exists($event, $postTypes)) {
                    $postTypes[$event] = $event;
                }
            }
        }

        /* include custom non-registered post-types */
        $ignoredEvents = SucuriScanOption::getIgnoredEvents();
        foreach ($ignoredEvents as $event => $time) {
            if (!array_key_exists($event, $postTypes)) {
                $postTypes[$event] = $event;
            }
        }

        return $postTypes;
    }

    /**
     * Check whether an event is being ignored to send alerts or not.
     *
     * @param string $event Unique post-type name.
     * @return bool          Whether an event is being ignored or not.
     */
    public static function isIgnoredEvent($event = '')
    {
        $event = strtolower($event);
        $ignored = self::getIgnoredEvents();

        return array_key_exists($event, $ignored);
    }

    /**
     * Get a list of the post types ignored to receive email alerts when the
     * "new site content" hook is triggered.
     *
     * @return array List of ignored posts-types to send alerts.
     */
    public static function getIgnoredEvents()
    {
        $post_types = self::getOption(':ignored_events');

        if (is_string($post_types)) {
            $post_types = @json_decode($post_types, true);
        }

        return (array) $post_types;
    }

    /**
     * Retrieve a list of basic security keys and check whether their values were
     * randomized correctly.
     *
     * @return array Array with three keys: good, missing, bad.
     */
    public static function getSecurityKeys()
    {
        $response = array(
            'good' => array(),
            'missing' => array(),
            'bad' => array(),
        );
        $key_names = array(
            'AUTH_KEY',
            'AUTH_SALT',
            'LOGGED_IN_KEY',
            'LOGGED_IN_SALT',
            'NONCE_KEY',
            'NONCE_SALT',
            'SECURE_AUTH_KEY',
            'SECURE_AUTH_SALT',
        );

        foreach ($key_names as $key_name) {
            if (defined($key_name)) {
                $key_value = constant($key_name);

                if (stripos($key_value, 'unique phrase') !== false) {
                    $response['bad'][$key_name] = $key_value;
                } else {
                    $response['good'][$key_name] = $key_value;
                }
            } else {
                $response['missing'][$key_name] = false;
            }
        }

        return $response;
    }

    /**
     * Change the reverse proxy setting.
     *
     * When enabled this option forces the plugin to override the value of the
     * global IP address variable from the HTTP header selected by the user from
     * the settings. Note that this may also be automatically enabled when the
     * firewall page is activated as it assumes that the proxy is creating a
     * custom HTTP header for the real IP.
     *
     * @param string $action Enable or disable the reverse proxy.
     * @param bool $silent Hide admin notices on success.
     * @return void
     */
    public static function setRevProxy($action = 'disable', $silent = false)
    {
        if ($action !== 'enable' && $action !== 'disable') {
            return self::deleteOption(':revproxy');
        }

        $action_d = $action . 'd';
        $message = 'Reverse proxy support was <code>' . $action_d . '</code>';

        self::updateOption(':revproxy', $action_d);

        SucuriScanEvent::reportInfoEvent($message);
        SucuriScanEvent::notifyEvent('plugin_change', $message);

        if ($silent) {
            return true;
        }

        return SucuriScanInterface::info(
            sprintf(
                'Reverse proxy support was set to <b>%s</b>',
                $action_d /* either enabled or disabled */
            )
        );
    }

    /**
     * Change the HTTP header to retrieve the real IP address.
     *
     * @param string $header Valid HTTP header name.
     * @param bool $silent Hide admin notices on success.
     * @return void
     */
    public static function setAddrHeader($header = 'REMOTE_ADDR', $silent = false)
    {
        $header = strtoupper($header);
        $allowed = SucuriScan::allowedHttpHeaders(true);

        if (!array_key_exists($header, $allowed)) {
            return SucuriScanInterface::error('HTTP header is not allowed');
        }

        $message = sprintf('HTTP header was set to %s', $header);

        self::updateOption(':addr_header', $header);

        SucuriScanEvent::reportInfoEvent($message);
        SucuriScanEvent::notifyEvent('plugin_change', $message);

        if ($silent) {
            return true;
        }

        return SucuriScanInterface::info(
            sprintf(
                'HTTP header was set to <code>%s</code>',
                $header /* one of the allowed HTTP headers */
            )
        );
    }
}
