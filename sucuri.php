<?php
/*
Plugin Name: Sucuri Security - Auditing, Malware Scanner and Hardening
Plugin URI: https://wordpress.sucuri.net/
Description: The <a href="https://sucuri.net/" target="_blank">Sucuri</a> plugin provides the website owner the best Activity Auditing, SiteCheck Remote Malware Scanning, Effective Security Hardening and Post-Hack features. SiteCheck will check for malware, spam, blacklisting and other security issues like .htaccess redirects, hidden eval code, etc. The best thing about it is it's completely free.
Author: Sucuri, INC
Version: 1.7.17
Author URI: https://sucuri.net
*/


/**
 * Main file to control the plugin.
 *
 * @package   Sucuri Security
 * @author    Yorman Arias <yorman.arias@sucuri.net>
 * @author    Daniel Cid   <dcid@sucuri.net>
 * @copyright Since 2010-2015 Sucuri Inc.
 * @license   Released under the GPL - see LICENSE file for details.
 * @link      https://wordpress.sucuri.net/
 * @since     File available since Release 0.1
 */


/**
 * Plugin dependencies.
 *
 * List of required functions for the execution of this plugin, we are assuming
 * that this site was built on top of the WordPress project, and that it is
 * being loaded through a pluggable system, these functions most be defined
 * before to continue.
 *
 * @var array
 */
$sucuriscan_dependencies = array(
    'wp',
    'wp_die',
    'add_action',
    'remove_action',
    'wp_remote_get',
    'wp_remote_post',
);

// Terminate execution if any of the functions mentioned above is not defined.
foreach ($sucuriscan_dependencies as $dependency) {
    if (!function_exists($dependency)) {
        exit(0);
    }
}

/**
 * Plugin's constants.
 *
 * These constants will hold the basic information of the plugin, file/folder
 * paths, version numbers, read-only variables that will affect the functioning
 * of the rest of the code. The conditional will act as a container helping in
 * the readability of the code considering the total number of lines that this
 * file will have.
 */

/**
 * Unique name of the plugin through out all the code.
 */
define('SUCURISCAN', 'sucuriscan');

/**
 * Current version of the plugin's code.
 */
define('SUCURISCAN_VERSION', '1.7.17');

/**
 * The name of the Sucuri plugin main file.
 */
define('SUCURISCAN_PLUGIN_FILE', 'sucuri.php');

/**
 * The name of the folder where the plugin's files will be located.
 */
define('SUCURISCAN_PLUGIN_FOLDER', 'sucuri-scanner');

/**
 * The fullpath where the plugin's files will be located.
 */
define('SUCURISCAN_PLUGIN_PATH', WP_PLUGIN_DIR.'/'.SUCURISCAN_PLUGIN_FOLDER);

/**
 * The fullpath of the main plugin file.
 */
define('SUCURISCAN_PLUGIN_FILEPATH', SUCURISCAN_PLUGIN_PATH.'/'.SUCURISCAN_PLUGIN_FILE);

/**
 * The local URL where the plugin's files and assets are served.
 */
define('SUCURISCAN_URL', rtrim(plugin_dir_url(SUCURISCAN_PLUGIN_FILEPATH), '/'));

/**
 * Checksum of this file to check the integrity of the plugin.
 */
define('SUCURISCAN_PLUGIN_CHECKSUM', @md5_file(SUCURISCAN_PLUGIN_FILEPATH));

/**
 * Remote URL where the public Sucuri API service is running.
 */
define('SUCURISCAN_API', 'https://wordpress.sucuri.net/api/');

/**
 * Latest version of the public Sucuri API.
 */
define('SUCURISCAN_API_VERSION', 'v1');

/**
 * Remote URL where the CloudProxy API service is running.
 */
define('SUCURISCAN_CLOUDPROXY_API', 'https://waf.sucuri.net/api');

/**
 * Latest version of the CloudProxy API.
 */
define('SUCURISCAN_CLOUDPROXY_API_VERSION', 'v2');

/**
 * The maximum quantity of entries that will be displayed in the last login page.
 */
define('SUCURISCAN_LASTLOGINS_USERSLIMIT', 25);

/**
 * The maximum quantity of entries that will be displayed in the audit logs page.
 */
define('SUCURISCAN_AUDITLOGS_PER_PAGE', 50);

/**
 * The maximum quantity of buttons in the paginations.
 */
define('SUCURISCAN_MAX_PAGINATION_BUTTONS', 20);

/**
 * The minimum quantity of seconds to wait before each filesystem scan.
 */
define('SUCURISCAN_MINIMUM_RUNTIME', 10800);

/**
 * The life time of the cache for the results of the SiteCheck scans.
 */
define('SUCURISCAN_SITECHECK_LIFETIME', 1200);

/**
 * The life time of the cache for the results of the get_plugins function.
 */
define('SUCURISCAN_GET_PLUGINS_LIFETIME', 1800);

/**
 * The maximum execution time of a HTTP request before timeout.
 */
define('SUCURISCAN_MAX_REQUEST_TIMEOUT', 15);

/**
 * Plugin's global variables.
 *
 * These variables will be defined globally to allow the inclusion in multiple
 * functions and classes defined in the libraries loaded by this plugin. The
 * conditional will act as a container helping in the readability of the code
 * considering the total number of lines that this file will have.
 */
if (defined('SUCURISCAN')) {
    /**
     * Define the prefix for some actions and filters that rely in the
     * differentiation of the type of site where the extension is being used. There
     * are a few differences between a single site installation that must be
     * correctly defined when the extension is in a different environment, for
     * example, in a multisite installation.
     *
     * @var string
     */
    $sucuriscan_action_prefix = SucuriScan::is_multisite() ? 'network_' : '';

    /**
     * List an associative array with the sub-pages of this plugin.
     *
     * @return array
     */
    $sucuriscan_pages = array(
        'sucuriscan' => 'Dashboard',
        'sucuriscan_scanner' => 'Malware Scan',
        'sucuriscan_firewall' => 'Firewall (WAF)',
        'sucuriscan_hardening' => 'Hardening',
        'sucuriscan_posthack' => 'Post-Hack',
        'sucuriscan_lastlogins' => 'Last Logins',
        'sucuriscan_settings' => 'Settings',
        'sucuriscan_infosys' => 'Site Info',
    );

    /**
     * Settings options.
     *
     * The following global variables are mostly associative arrays where the key is
     * linked to an option that will be stored in the database, and their
     * correspondent values are the description of the option. These variables will
     * be used in the settings page to offer the user a way to configure the
     * behaviour of the plugin.
     *
     * @var array
     */

    $sucuriscan_notify_options = array(
        'sucuriscan_notify_plugin_change' => 'Receive email alerts for <b>Sucuri</b> plugin changes',
        'sucuriscan_prettify_mails' => 'Receive email alerts in HTML <em>(there may be issues with some mail services)</em>',
        'sucuriscan_use_wpmail' => 'Use WordPress functions to send mails <em>(uncheck to use native PHP functions)</em>',
        'sucuriscan_lastlogin_redirection' => 'Allow redirection after login to report the last-login information',
        'sucuriscan_notify_scan_checksums' => 'Receive email alerts for core integrity checks',
        'sucuriscan_notify_user_registration' => 'user:Receive email alerts for new user registration',
        'sucuriscan_notify_success_login' => 'user:Receive email alerts for successful login attempts',
        'sucuriscan_notify_failed_login' => 'user:Receive email alerts for failed login attempts <em>(you may receive tons of emails)</em>',
        'sucuriscan_notify_bruteforce_attack' => 'user:Receive email alerts for password guessing attacks <em>(summary of failed logins per hour)</em>',
        'sucuriscan_notify_post_publication' => 'Receive email alerts for Post-Type changes <em>(configure from Ignore Alerts)</em>',
        'sucuriscan_notify_website_updated' => 'Receive email alerts when the WordPress version is updated',
        'sucuriscan_notify_settings_updated' => 'Receive email alerts when your website settings are updated',
        'sucuriscan_notify_theme_editor' => 'Receive email alerts when a file is modified with theme/plugin editor',
        'sucuriscan_notify_plugin_installed' => 'plugin:Receive email alerts when a <b>plugin is installed</b>',
        'sucuriscan_notify_plugin_activated' => 'plugin:Receive email alerts when a <b>plugin is activated</b>',
        'sucuriscan_notify_plugin_deactivated' => 'plugin:Receive email alerts when a <b>plugin is deactivated</b>',
        'sucuriscan_notify_plugin_updated' => 'plugin:Receive email alerts when a <b>plugin is updated</b>',
        'sucuriscan_notify_plugin_deleted' => 'plugin:Receive email alerts when a <b>plugin is deleted</b>',
        'sucuriscan_notify_widget_added' => 'widget:Receive email alerts when a <b>widget is added</b> to a sidebar',
        'sucuriscan_notify_widget_deleted' => 'widget:Receive email alerts when a <b>widget is deleted</b> from a sidebar',
        'sucuriscan_notify_theme_installed' => 'theme:Receive email alerts when a <b>theme is installed</b>',
        'sucuriscan_notify_theme_activated' => 'theme:Receive email alerts when a <b>theme is activated</b>',
        'sucuriscan_notify_theme_updated' => 'theme:Receive email alerts when a <b>theme is updated</b>',
        'sucuriscan_notify_theme_deleted' => 'theme:Receive email alerts when a <b>theme is deleted</b>',
    );

    $sucuriscan_schedule_allowed = array(
        'hourly' => 'Every three hours (3 hours)',
        'twicedaily' => 'Twice daily (12 hours)',
        'daily' => 'Once daily (24 hours)',
        '_oneoff' => 'Never',
    );

    $sucuriscan_interface_allowed = array(
        'spl' => 'SPL (high performance)',
        'opendir' => 'OpenDir (medium)',
        'glob' => 'Glob (low)',
    );

    $sucuriscan_emails_per_hour = array(
        '5' => 'Maximum 5 per hour',
        '10' => 'Maximum 10 per hour',
        '20' => 'Maximum 20 per hour',
        '40' => 'Maximum 40 per hour',
        '80' => 'Maximum 80 per hour',
        '160' => 'Maximum 160 per hour',
        'unlimited' => 'Unlimited',
    );

    $sucuriscan_maximum_failed_logins = array(
        '30' => '30 failed logins per hour',
        '60' => '60 failed logins per hour',
        '120' => '120 failed logins per hour',
        '240' => '240 failed logins per hour',
        '480' => '480 failed logins per hour',
    );

    $sucuriscan_verify_ssl_cert = array(
        'true' => 'Verify peer\'s cert',
        'false' => 'Stop peer\'s cert verification',
    );

    $sucuriscan_no_notices_in = array(
        /* Value of the page parameter to ignore. */
    );

    $sucuriscan_email_subjects = array(
        'Sucuri Alert, :domain, :event',
        'Sucuri Alert, :domain, :event, :remoteaddr',
        'Sucuri Alert, :domain, :event, :username',
        'Sucuri Alert, :domain, :event, :email',
        'Sucuri Alert, :event, :remoteaddr',
        'Sucuri Alert, :event',
    );

    /**
     * Remove the WordPress generator meta-tag from the source code.
     */
    remove_action('wp_head', 'wp_generator');

    /**
     * Run a specific function defined in the plugin's code to locate every
     * directory and file, collect their checksum and file size, and send this
     * information to the Sucuri API service where a security and integrity scan
     * will be performed against the hashes provided and the official versions.
     */
    add_action('sucuriscan_scheduled_scan', 'SucuriScan::run_scheduled_task');

    /**
     * Initialize the execute of the main plugin's functions.
     *
     * This will load the menu options in the WordPress administrator panel, and
     * execute the bootstrap function of the plugin.
     */
    add_action('init', 'SucuriScanInterface::initialize', 1);
    add_action('admin_init', 'SucuriScanInterface::create_datastore_folder');
    add_action('admin_init', 'SucuriScanInterface::handle_old_plugins');
    add_action('admin_enqueue_scripts', 'SucuriScanInterface::enqueue_scripts', 1);

    /**
     * Display extension menu and submenu items in the correct interface. For single
     * site installations the menu items can be displayed normally as always but for
     * multisite installations the menu items must be available only in the network
     * panel and hidden in the administration panel of the subsites.
     */
    add_action($sucuriscan_action_prefix . 'admin_menu', 'SucuriScanInterface::add_interface_menu');

    /**
     * Attach Ajax requests to a custom page handler.
     */
    foreach ($sucuriscan_pages as $page_func => $page_title) {
        $ajax_func = $page_func . '_ajax';

        if (function_exists($ajax_func)) {
            add_action('wp_ajax_' . $ajax_func, $ajax_func);
        }
    }

    /**
     * Function call interceptors.
     *
     * Define the names for the hooks that will intercept specific function calls in
     * the admin interface and parts of the external site, an event report will be
     * sent to the API service and an email notification to the administrator of the
     * site.
     *
     * @see Class SucuriScanHook
     */
    if (class_exists('SucuriScanHook')) {
        $sucuriscan_hooks = array(
            'add_attachment',
            'add_link',
            'create_category',
            'delete_post',
            'delete_user',
            'login_form_resetpass',
            'private_to_published',
            'publish_page',
            'publish_phone',
            'publish_post',
            'retrieve_password',
            'switch_theme',
            'user_register',
            'wp_insert_comment',
            'wp_login',
            'wp_login_failed',
            'wp_trash_post',
            'xmlrpc_publish_post',
        );

        if (SucuriScanOption::is_enabled(':xhr_monitor')) {
            $sucuriscan_hooks[] = 'all';
        }

        foreach ($sucuriscan_hooks as $hook_name) {
            $hook_func = 'SucuriScanHook::hook_' . $hook_name;
            add_action($hook_name, $hook_func, 50, 5);
        }

        add_action('admin_init', 'SucuriScanHook::hook_undefined_actions');
        add_action('login_form', 'SucuriScanHook::hook_undefined_actions');
    } else {
        SucuriScanInterface::error('Function call interceptors are not working properly.');
    }

    /**
     * Display a message if the plugin is not activated.
     *
     * Display a message at the top of the administration panel with a button that
     * once clicked will send the site's email and domain name to the Sucuri API
     * service where an API key will be generated for the site, this key will allow
     * the plugin to execute the filesystem scans, the project integrity, and the
     * email notifications.
     */
    add_action($sucuriscan_action_prefix . 'admin_notices', 'SucuriScanInterface::setup_notice');

    /**
     * Heartbeat API
     *
     * Update the settings of the Heartbeat API according to the values set by an
     * administrator. This tool may cause an increase in the CPU usage, a bad
     * configuration may cause low account to run out of resources, but in better
     * cases it may improve the performance of the site by reducing the quantity of
     * requests sent to the server per session.
     */
    add_filter('init', 'SucuriScanHeartbeat::register_script', 1);
    add_filter('heartbeat_settings', 'SucuriScanHeartbeat::update_settings');
    add_filter('heartbeat_send', 'SucuriScanHeartbeat::respond_to_send', 10, 3);
    add_filter('heartbeat_received', 'SucuriScanHeartbeat::respond_to_received', 10, 3);
    add_filter('heartbeat_nopriv_send', 'SucuriScanHeartbeat::respond_to_send', 10, 3);
    add_filter('heartbeat_nopriv_received', 'SucuriScanHeartbeat::respond_to_received', 10, 3);
}

/**
 * Miscellaneous library.
 *
 * Multiple and generic functions that will be used through out the code of
 * other libraries extending from this and functions defined in other files, be
 * aware of the hierarchy and check the other libraries for duplicated methods.
 */
class SucuriScan
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
    }

    /**
     * Return name of a variable with the plugin's prefix (if needed).
     *
     * To facilitate the development, you can prefix the name of the key in the
     * request (when accessing it) with a single colon, this function will
     * automatically replace that character with the unique identifier of the
     * plugin.
     *
     * @param  string $var_name Name of a variable with an optional colon at the beginning.
     * @return string           Full name of the variable with the extra characters (if needed).
     */
    public static function variable_prefix($var_name = '')
    {
        if (!empty($var_name) && $var_name[0] === ':') {
            $var_name = sprintf(
                '%s_%s',
                SUCURISCAN,
                substr($var_name, 1)
            );
        }

        return $var_name;
    }

    /**
     * Gets the value of a configuration option.
     *
     * @param  string $property The configuration option name.
     * @return string           Value of the configuration option as a string on success.
     */
    public static function ini_get($property = '')
    {
        $ini_value = ini_get($property);
        $default = array(
            'error_log' => 'error_log',
            'safe_mode' => 'Off',
            'memory_limit' => '128M',
            'upload_max_filesize' => '2M',
            'post_max_size' => '8M',
            'max_execution_time' => '30',
            'max_input_time' => '-1',
        );

        if ($ini_value === false) {
            $ini_value = 'Undefined';
        } elseif (empty($ini_value) || $ini_value === null) {
            if (array_key_exists($property, $default)) {
                $ini_value = $default[$property];
            } else {
                $ini_value = 'Off';
            }
        }

        if ($property == 'error_log') {
            $ini_value = basename($ini_value);
        }

        return $ini_value;
    }

    /**
     * Encodes the less-than, greater-than, ampersand, double quote and single quote
     * characters, will never double encode entities.
     *
     * @param  string $text The text which is to be encoded.
     * @return string       The encoded text with HTML entities.
     */
    public static function escape($text = '')
    {
        // Escape the value of the variable using a built-in function if possible.
        if (function_exists('esc_attr')) {
            $text = esc_attr($text);
        } else {
            $text = htmlspecialchars($text);
        }

        return $text;
    }

    /**
     * Generates a lowercase random string with an specific length.
     *
     * @param  integer $length Length of the string that will be generated.
     * @return string          The random string generated.
     */
    public static function random_char($length = 4)
    {
        $string = '';
        $offset = 25;
        $chars = array(
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
            'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        );

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, $offset);
            $string .= $chars[$index];
        }

        return $string;
    }

    /**
     * Translate a given number in bytes to a human readable file size using the
     * a approximate value in Kylo, Mega, Giga, etc.
     *
     * @link   https://www.php.net/manual/en/function.filesize.php#106569
     * @param  integer $bytes    An integer representing a file size in bytes.
     * @param  integer $decimals How many decimals should be returned after the translation.
     * @return string            Human readable representation of the given number in Kylo, Mega, Giga, etc.
     */
    public static function human_filesize($bytes = 0, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[ $factor ];
    }

    /**
     * Returns the system filepath to the relevant user uploads directory for this
     * site. This is a multisite capable function.
     *
     * @param  string $path The relative path that needs to be completed to get the absolute path.
     * @return string       The full filesystem path including the directory specified.
     */
    public static function datastore_folder_path($path = '')
    {
        $default_dir = 'sucuri';
        $datastore = SucuriScanOption::get_option(':datastore_path');

        // Use the uploads folder by default.
        if (empty($datastore)) {
            $uploads_path = false;

            // Multisite installations may have different paths.
            if (function_exists('wp_upload_dir')) {
                $upload_dir = wp_upload_dir();

                if (isset($upload_dir['basedir'])) {
                    $uploads_path = rtrim($upload_dir['basedir'], '/');
                }
            }

            if ($uploads_path === false) {
                if (defined('WP_CONTENT_DIR')) {
                    $uploads_path = WP_CONTENT_DIR . '/uploads';
                } else {
                    $uploads_path = ABSPATH . '/wp-content/uploads';
                }
            }

            $datastore = $uploads_path . '/' . $default_dir;
            SucuriScanOption::update_option(':datastore_path', $datastore);
        }

        return $datastore . '/' . $path;
    }

    /**
     * Check whether the current site is working as a multi-site instance.
     *
     * @return boolean Either TRUE or FALSE in case WordPress is being used as a multi-site instance.
     */
    public static function is_multisite()
    {
        return (bool) (function_exists('is_multisite') && is_multisite());
    }

    public static function admin_url($url = '')
    {
        if (self::is_multisite()) {
            return network_admin_url($url);
        } else {
            return admin_url($url);
        }
    }

    /**
     * Find and retrieve the current version of Wordpress installed.
     *
     * @return string The version number of Wordpress installed.
     */
    public static function site_version()
    {
        global $wp_version;

        if ($wp_version === null) {
            $wp_version_path = ABSPATH . WPINC . '/version.php';

            if (file_exists($wp_version_path)) {
                include($wp_version_path);
                $wp_version = isset($wp_version) ? $wp_version : '0.0';
            } else {
                $option_version = get_option('version');
                $wp_version = $option_version ? $option_version : '0.0';
            }
        }

        $wp_version = self::escape($wp_version);

        return $wp_version;
    }

    /**
     * Find and retrieve the absolute path of the WordPress configuration file.
     *
     * @return string Absolute path of the WordPress configuration file.
     */
    public static function get_wpconfig_path()
    {
        if (defined('ABSPATH')) {
            $file_path = ABSPATH . '/wp-config.php';

            // if wp-config.php doesn't exist, or is not readable check one directory up.
            if (!file_exists($file_path)) {
                $file_path = ABSPATH . '/../wp-config.php';
            }

            // Remove duplicated double slashes.
            $file_path = @realpath($file_path);

            if ($file_path) {
                return $file_path;
            }
        }

        return false;
    }

    /**
     * Find and retrieve the absolute path of the main WordPress htaccess file.
     *
     * @return string Absolute path of the main WordPress htaccess file.
     */
    public static function get_htaccess_path()
    {
        if (defined('ABSPATH')) {
            $base_dirs = array(
                rtrim(ABSPATH, '/'),
                dirname(ABSPATH),
                dirname(dirname(ABSPATH)),
            );

            foreach ($base_dirs as $base_dir) {
                $htaccess_path = sprintf('%s/.htaccess', $base_dir);

                if (file_exists($htaccess_path)) {
                    return $htaccess_path;
                }
            }
        }

        return false;
    }

    /**
     * Get the pattern of the definition related with a WordPress secret key.
     *
     * @return string Secret key definition pattern.
     */
    public static function secret_key_pattern()
    {
        return '/define\(\s*\'([A-Z_]+)\',(\s*)\'(.+)\'\s*\);/';
    }

    /**
     * Execute the plugin' scheduled tasks.
     *
     * @return void
     */
    public static function run_scheduled_task()
    {
        SucuriScanEvent::filesystem_scan();
        sucuriscan_core_files_data(true);
    }

    /**
     * List of allowed HTTP headers to retrieve the real IP.
     *
     * Once the DNS lookups are enabled to discover the real IP address of the
     * visitors the user may choose the HTTP header that will be used by default to
     * retrieve the real IP address of each HTTP request, generally they do not need
     * to set this but in rare cases the hosting provider may have a load balancer
     * that can interfere in the process, in which case they will have to explicitly
     * specify the main HTTP header. This is a list of the allowed headers that the
     * user can choose.
     *
     * @param  boolean $with_keys Return the array with its values are keys.
     * @return array              Allowed HTTP headers to retrieve real IP.
     */
    public static function allowedHttpHeaders($with_keys = false)
    {
        $allowed = array(
            'HTTP_X_SUCURI_CLIENTIP',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'SUCURI_RIP',
            'REMOTE_ADDR',
        );

        if ($with_keys === true) {
            $verbose = array();

            foreach ($allowed as $header) {
                $verbose[$header] = $header;
            }

            return $verbose;
        }

        return $allowed;
    }

    /**
     * List HTTP headers ordered.
     *
     * The list of HTTP headers is ordered per relevancy, and having the main HTTP
     * header as the first entry, this guarantees that the IP address of the
     * visitors will be retrieved from the HTTP header chosen by the user first and
     * fallback to the other alternatives if available.
     *
     * @return array Ordered allowed HTTP headers.
     */
    private static function orderedHttpHeaders()
    {
        $ordered = array();
        $allowed = self::allowedHttpHeaders();
        $addr_header = SucuriScanOption::get_option(':addr_header');
        $ordered[] = $addr_header;

        foreach ($allowed as $header) {
            if (!in_array($header, $ordered)) {
                $ordered[] = $header;
            }
        }

        return $ordered;
    }

    /**
     * Retrieve the real ip address of the user in the current request.
     *
     * @param  boolean $with_header Return HTTP header where the IP address was found.
     * @return string               Real IP address of the user in the current request.
     */
    public static function get_remote_addr($with_header = false)
    {
        $remote_addr = false;
        $header_used = 'unknown';
        $headers = self::orderedHttpHeaders();

        foreach ($headers as $header) {
            if (array_key_exists($header, $_SERVER)
                && self::is_valid_ip($_SERVER[$header])
            ) {
                $remote_addr = $_SERVER[$header];
                $header_used = $header;
                break;
            }
        }

        if (!$remote_addr || $remote_addr === '::1') {
            $remote_addr = '127.0.0.1';
        }

        if ($with_header) {
            return $header_used;
        }

        return $remote_addr;
    }

    /**
     * Return the HTTP header used to retrieve the remote address.
     *
     * @return string The HTTP header used to retrieve the remote address.
     */
    public static function get_remote_addr_header()
    {
        return self::get_remote_addr(true);
    }

    /**
     * Retrieve the user-agent from the current request.
     *
     * @return string The user-agent from the current request.
     */
    public static function get_user_agent()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            return self::escape($_SERVER['HTTP_USER_AGENT']);
        }

        return false;
    }

    /**
     * Get the clean version of the current domain.
     *
     * @return string The domain of the current site.
     */
    public static function get_domain($return_tld = false)
    {
        if (function_exists('get_site_url')) {
            $site_url = get_site_url();
            $pattern = '/([fhtps]+:\/\/)?([^:\/]+)(:[0-9:]+)?(\/.*)?/';
            $replacement = ( $return_tld === true ) ? '$2' : '$2$3$4';
            $domain_name = @preg_replace($pattern, $replacement, $site_url);

            return $domain_name;
        }

        return false;
    }

    /**
     * Get top-level domain (TLD) of the website.
     *
     * @return string Top-level domain (TLD) of the website.
     */
    public static function get_top_level_domain()
    {
        return self::get_domain(true);
    }

    /**
     * Check whether reverse proxy servers must be supported.
     *
     * @return boolean TRUE if reverse proxies must be supported, FALSE otherwise.
     */
    public static function support_reverse_proxy()
    {
        return SucuriScanOption::is_enabled(':revproxy');
    }

    /**
     * Check whether the DNS lookups should be execute or not.
     *
     * DNS lookups are only necessary if you are planning to use a reverse proxy or
     * firewall (like CloudProxy), this is used to set the correct IP address when
     * the firewall/proxy filters the requests. If you are not planning to use any
     * of these is better to disable this option, otherwise the load time of your
     * site may be affected.
     *
     * @return boolean True if the DNS lookups should be executed, false otherwise.
     */
    public static function execute_dns_lookups()
    {
        if (( defined('NOT_USING_CLOUDPROXY') && NOT_USING_CLOUDPROXY === true )
            || SucuriScanOption::is_disabled(':dns_lookups')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Check whether the site is behind the Sucuri CloudProxy network.
     *
     * @param  boolean $verbose Return an array with the hostname, address, and status, or not.
     * @return boolean          Either TRUE or FALSE if the site is behind CloudProxy.
     */
    public static function is_behind_cloudproxy($verbose = false)
    {
        $http_host = self::get_top_level_domain();

        if (self::execute_dns_lookups()) {
            $host_by_addr = @gethostbyname($http_host);
            $host_by_name = @gethostbyaddr($host_by_addr);
            $status = (bool) preg_match('/^cloudproxy[0-9]+\.sucuri\.net$/', $host_by_name);
        } else {
            $status = false;
            $host_by_addr = '::1';
            $host_by_name = 'localhost';
        }

        /*
         * If the DNS reversion failed but the CloudProxy API key is set, then consider
         * the site as protected by a firewall. A fake key can be used to bypass the DNS
         * checking, but that is not something that will affect us, only the client.
         */
        if ($status === false
            && SucuriScanAPI::getCloudproxyKey()
        ) {
            $status = true;
        }

        if ($verbose) {
            return array(
                'http_host' => $http_host,
                'host_name' => $host_by_name,
                'host_addr' => $host_by_addr,
                'status' => $status,
            );
        }

        return $status;
    }

    /**
     * Get the email address set by the administrator to receive the notifications
     * sent by the plugin, if the email is missing the WordPress email address is
     * chosen by default.
     *
     * @return string The administrator email address.
     */
    public static function get_site_email()
    {
        $email = get_option('admin_email');

        if (self::is_valid_email($email)) {
            return $email;
        }

        return false;
    }

    /**
     * Get user data by field and data.
     *
     * @param  integer $identifier User account identifier.
     * @return object              WordPress user object with data.
     */
    public static function get_user_by_id($identifier = 0)
    {
        if (function_exists('get_user_by')) {
            $user = get_user_by('id', $identifier);

            if ($user instanceof WP_User) {
                return $user;
            }
        }

        return false;
    }

    /**
     * Retrieve a list of all admin user accounts.
     *
     * @return array List of admin users, false otherwise.
     */
    public static function get_admin_users()
    {
        if (function_exists('get_users')) {
            $args = array( 'role' => 'administrator' );

            return get_users($args);
        }

        return false;
    }

    /**
     * Get a list of user emails that can be used to generate an API key for this
     * website. Only accounts with the status in zero will be returned, the status
     * field in the users table is officially deprecated but some 3rd-party plugins
     * still use it to check if the account was activated by the owner of the email,
     * a value different than zero generally means that the email was not verified
     * successfully.
     *
     * @return array List of user identifiers and email addresses.
     */
    public static function get_users_for_api_key()
    {
        $valid_users = array();
        $users = self::get_admin_users();

        if ($users !== false) {
            foreach ($users as $user) {
                if ($user->user_status === '0') {
                    $valid_users[ $user->ID ] = sprintf(
                        '%s - %s',
                        $user->user_login,
                        $user->user_email
                    );
                }
            }
        }

        return $valid_users;
    }

    /**
     * Returns the current time measured in the number of seconds since the Unix Epoch.
     *
     * @return integer Return current Unix timestamp.
     */
    public static function local_time()
    {
        if (function_exists('current_time')) {
            return current_time('timestamp');
        } else {
            return time();
        }
    }

    /**
     * Retrieve the date in localized format, based on timestamp.
     *
     * If the locale specifies the locale month and weekday, then the locale will
     * take over the format for the date. If it isn't, then the date format string
     * will be used instead.
     *
     * @param  integer $timestamp Unix timestamp.
     * @return string             The date, translated if locale specifies it.
     */
    public static function datetime($timestamp = null)
    {
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        $tz_format = sprintf('%s %s', $date_format, $time_format);

        if (is_numeric($timestamp) && $timestamp > 0) {
            return date_i18n($tz_format, $timestamp);
        }

        return date_i18n($tz_format);
    }

    /**
     * Retrieve the date in localized format based on the current time.
     *
     * @return string The date, translated if locale specifies it.
     */
    public static function current_datetime()
    {
        return self::datetime();
    }

    /**
     * Return the time passed since the specified timestamp until now.
     *
     * @param  integer $timestamp The Unix time number of the date/time before now.
     * @return string             The time passed since the timestamp specified.
     */
    public static function time_ago($timestamp = 0)
    {
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        $local_time = self::local_time();
        $diff = abs($local_time - intval($timestamp));

        if ($diff == 0) {
            return 'just now';
        }

        $intervals = array(
            1                => array( 'year', 31556926, ),
            $diff < 31556926 => array( 'month', 2592000, ),
            $diff < 2592000  => array( 'week', 604800, ),
            $diff < 604800   => array( 'day', 86400, ),
            $diff < 86400    => array( 'hour', 3600, ),
            $diff < 3600     => array( 'minute', 60, ),
            $diff < 60       => array( 'second', 1, ),
        );

        $value = floor($diff / $intervals[1][1]);
        $time_ago = sprintf(
            '%s %s%s ago',
            $value,
            $intervals[1][0],
            ( $value > 1 ? 's' : '' )
        );

        return $time_ago;
    }

    /**
     * Convert an string of characters into a valid variable name.
     *
     * @see https://www.php.net/manual/en/language.variables.basics.php
     *
     * @param  string $text A text containing alpha-numeric and special characters.
     * @return string       A valid variable name.
     */
    public static function human2var($text = '')
    {
        $text = strtolower($text);
        $pattern = '/[^a-z0-9_]/';
        $var_name = preg_replace($pattern, '_', $text);

        return $var_name;
    }

    /**
     * Check whether a variable contains a serialized data or not.
     *
     * @param  string  $data The data that will be checked.
     * @return boolean       TRUE if the data was serialized, FALSE otherwise.
     */
    public static function is_serialized($data = '')
    {
        return ( is_string($data) && preg_match('/^(a|O):[0-9]+:.+/', $data) );
    }

    /**
     * Check whether an IP address has a valid format or not.
     *
     * @param  string  $remote_addr The host IP address.
     * @return boolean              Whether the IP address specified is valid or not.
     */
    public static function is_valid_ip($remote_addr = '')
    {
        if (function_exists('filter_var')) {
            return (bool) filter_var($remote_addr, FILTER_VALIDATE_IP);
        } elseif (strlen($remote_addr) >= 7) {
            $pattern = '/^([0-9]{1,3}\.) {3}[0-9]{1,3}$/';

            if (preg_match($pattern, $remote_addr, $match)) {
                for ($i = 0; $i < 4; $i++) {
                    if ($match[ $i ] > 255) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }


    /**
     * Check whether an IP address is formatted as CIDR or not.
     *
     * @param  string $remote_addr The supposed ip address that will be checked.
     * @return boolean             Either TRUE or FALSE if the ip address specified is valid or not.
     */
    public static function is_valid_cidr($remote_addr = '')
    {
        if (preg_match('/^([0-9\.]{7,15})\/(8|16|24)$/', $remote_addr, $match)) {
            if (self::is_valid_ip($match[1])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Separate the parts of an IP address.
     *
     * @param  string $remote_addr The supposed ip address that will be formatted.
     * @return array               Clean address, CIDR range, and CIDR format; FALSE otherwise.
     */
    public static function get_ip_info($remote_addr = '')
    {
        if ($remote_addr) {
            $ip_parts = explode('/', $remote_addr);

            if (array_key_exists(0, $ip_parts)
                && self::is_valid_ip($ip_parts[0])
            ) {
                $addr_info = array();
                $addr_info['remote_addr'] = $ip_parts[0];
                $addr_info['cidr_range'] = isset($ip_parts[1]) ? $ip_parts[1] : '32';
                $addr_info['cidr_format'] = $addr_info['remote_addr'] . '/' . $addr_info['cidr_range'];

                return $addr_info;
            }
        }

        return false;
    }

    /**
     * Validate email address.
     *
     * This use the native PHP function filter_var which is available in PHP >=
     * 5.2.0 if it is not found in the interpreter this function will sue regular
     * expressions to check whether the email address passed is valid or not.
     *
     * @see https://www.php.net/manual/en/function.filter-var.php
     *
     * @param  string $email The string that will be validated as an email address.
     * @return boolean       TRUE if the email address passed to the function is valid, FALSE if not.
     */
    public static function is_valid_email($email = '')
    {
        if (function_exists('filter_var')) {
            return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
        } else {
            $pattern = '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix';
            return (bool) preg_match($pattern, $email);
        }
    }

    /**
     * Check whether a regular expression is valid or not.
     *
     * @param  string  $pattern The regular expression to check.
     * @return boolean          True if the regular expression is valid, false otherwise.
     */
    public static function is_valid_pattern($pattern = '')
    {
        return (bool) (
            is_string($pattern)
            && !empty($pattern)
            && @preg_match($pattern, null) !== false
        );
    }

    /**
     * Cut a long text to the length specified, and append suspensive points at the end.
     *
     * @param  string  $text   String of characters that will be cut.
     * @param  integer $length Maximum length of the returned string, default is 10.
     * @return string          Short version of the text specified.
     */
    public static function excerpt($text = '', $length = 10)
    {
        $text_length = strlen($text);

        if ($text_length > $length) {
            return substr($text, 0, $length) . '...';
        }

        return $text;
    }

    /**
     * Same as the excerpt method but with the string reversed.
     *
     * @param  string  $text   String of characters that will be cut.
     * @param  integer $length Maximum length of the returned string, default is 10.
     * @return string          Short version of the text specified.
     */
    public static function excerpt_rev($text = '', $length = 10)
    {
        $str_reversed = strrev($text);
        $str_excerpt = self::excerpt($str_reversed, $length);
        $text_transformed = strrev($str_excerpt);

        return $text_transformed;
    }

    /**
     * Check whether an list is a multidimensional array or not.
     *
     * @param  array   $list An array or multidimensional array of different values.
     * @return boolean       TRUE if the list is multidimensional, FALSE otherwise.
     */
    public static function is_multi_list($list = array())
    {
        if (!empty($list)) {
            foreach ((array) $list as $item) {
                if (is_array($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Join array elements with a string no matter if it is multidimensional.
     *
     * @param  string $separator Character that will act as a separator, default to an empty string.
     * @param  array  $list      The array of strings to implode.
     * @return string            String of all the items in the list, with the separator between them.
     */
    public static function implode($separator = '', $list = array())
    {
        if (self::is_multi_list($list)) {
            $pieces = array();

            foreach ($list as $items) {
                $pieces[] = @implode($separator, $items);
            }

            $joined_pieces = '(' . implode('), (', $pieces) . ')';

            return $joined_pieces;
        } else {
            return implode($separator, $list);
        }
    }

    /**
     * Determine if the plugin notices can be displayed in the current page.
     *
     * @param  string  $current_page Identifier of the current page.
     * @return boolean               TRUE if the current page must not have noticies.
     */
    public static function no_notices_here($current_page = false)
    {
        global $sucuriscan_no_notices_in;

        if ($current_page === false) {
            $current_page = SucuriScanRequest::get('page');
        }

        if (isset($sucuriscan_no_notices_in)
            && is_array($sucuriscan_no_notices_in)
            && !empty($sucuriscan_no_notices_in)
        ) {
            return (bool) in_array($current_page, $sucuriscan_no_notices_in);
        }

        return false;
    }

    /**
     * Check whether the site is running over the Nginx web server.
     *
     * @return boolean TRUE if the site is running over Nginx, FALSE otherwise.
     */
    public static function is_nginx_server()
    {
        return (bool) preg_match('/^nginx(\/[0-9\.]+)?$/', @$_SERVER['SERVER_SOFTWARE']);
    }

    /**
     * Check whether the site is running over the Nginx web server.
     *
     * @return boolean TRUE if the site is running over Nginx, FALSE otherwise.
     */
    public static function is_iis_server()
    {
        return (bool) preg_match('/Microsoft-IIS/i', @$_SERVER['SERVER_SOFTWARE']);
    }
}

/**
 * HTTP request handler.
 *
 * Function definitions to retrieve, validate, and clean the parameters during a
 * HTTP request, generally after a form submission or while loading a URL. Use
 * these methods at most instead of accessing an index in the global PHP
 * variables _POST, _GET, _REQUEST since they may come with insecure data.
 */
class SucuriScanRequest extends SucuriScan
{

    /**
     * Returns the value stored in a specific index in the global _GET, _POST or
     * _REQUEST variables, you can specify a pattern as the second argument to
     * match allowed values.
     *
     * @param  array  $list    The array where the specified key will be searched.
     * @param  string $key     Name of the index where the requested variable is supposed to be.
     * @param  string $pattern Optional pattern to match allowed values in the requested key.
     * @return string          The value stored in the specified key inside the global _GET variable.
     */
    public static function request($list = array(), $key = '', $pattern = '')
    {
        $key = self::variable_prefix($key);

        if (is_array($list)
            && is_string($key)
            && isset($list[ $key ])
        ) {
            // Select the key from the list and escape its content.
            $key_value = $list[ $key ];

            // Define regular expressions for specific value types.
            if ($pattern === '') {
                $pattern = '/.*/';
            } else {
                switch ($pattern) {
                    case '_nonce':
                        $pattern = '/^[a-z0-9]{10}$/';
                        break;
                    case '_page':
                        $pattern = '/^[a-z_]+$/';
                        break;
                    case '_array':
                        $pattern = '_array';
                        break;
                    case '_yyyymmdd':
                        $pattern = '/^[0-9]{4}(\-[0-9]{2}) {2}$/';
                        break;
                    default:
                        $pattern = '/^'.$pattern.'$/';
                        break;
                }
            }

            // If the request data is an array, then only cast the value.
            if ($pattern == '_array' && is_array($key_value)) {
                return (array) $key_value;
            }

            // Check the format of the request data with a regex defined above.
            if (@preg_match($pattern, $key_value)) {
                return self::escape($key_value);
            }
        }

        return false;
    }

    /**
     * Returns the value stored in a specific index in the global _GET variable,
     * you can specify a pattern as the second argument to match allowed values.
     *
     * @param  string $key     Name of the index where the requested variable is supposed to be.
     * @param  string $pattern Optional pattern to match allowed values in the requested key.
     * @return string          The value stored in the specified key inside the global _GET variable.
     */
    public static function get($key = '', $pattern = '')
    {
        return self::request($_GET, $key, $pattern);
    }

    /**
     * Returns the value stored in a specific index in the global _POST variable,
     * you can specify a pattern as the second argument to match allowed values.
     *
     * @param  string $key     Name of the index where the requested variable is supposed to be.
     * @param  string $pattern Optional pattern to match allowed values in the requested key.
     * @return string          The value stored in the specified key inside the global _POST variable.
     */
    public static function post($key = '', $pattern = '')
    {
        return self::request($_POST, $key, $pattern);
    }

    /**
     * Returns the value stored in a specific index in the global _REQUEST variable,
     * you can specify a pattern as the second argument to match allowed values.
     *
     * @param  string $key     Name of the index where the requested variable is supposed to be.
     * @param  string $pattern Optional pattern to match allowed values in the requested key.
     * @return string          The value stored in the specified key inside the global _POST variable.
     */
    public static function get_or_post($key = '', $pattern = '')
    {
        return self::request($_REQUEST, $key, $pattern);
    }
}

/**
 * Class to process files and folders.
 *
 * Here are implemented the functions needed to open, scan, read, create files
 * and folders using the built-in PHP class SplFileInfo. The SplFileInfo class
 * offers a high-level object oriented interface to information for an individual
 * file.
 */
class SucuriScanFileInfo extends SucuriScan
{

    /**
     * Define the interface that will be used to execute the file system scans, the
     * available options are SPL, OpenDir, and Glob (all in lowercase). This can be
     * configured from the settings page.
     *
     * @var string
     */
    public $scan_interface = 'spl';

    /**
     * Whether the list of files that can be ignored from the filesystem scan will
     * be used to return the directory tree, this should be disabled when scanning a
     * directory without the need to filter the items in the list.
     *
     * @var boolean
     */
    public $ignore_files = true;

    /**
     * Whether the list of folders that can be ignored from the filesystem scan will
     * be used to return the directory tree, this should be disabled when scanning a
     * path without the need to filter the items in the list.
     *
     * @var boolean
     */
    public $ignore_directories = true;

    /**
     * A list of ignored directory paths, these folders will be skipped during the
     * execution of the file system scans, and any sub-directory or files inside
     * these paths will be ignored too.
     *
     * @see SucuriScanFSScanner.get_ignored_directories()
     * @var array
     */
    private $ignored_directories = array();

    /**
     * Whether the filesystem scanner should run recursively or not.
     *
     * @var boolean
     */
    public $run_recursively = true;

    /**
     * Whether the directory paths must be skipped or not.
     *
     * This is useful to retrieve the full list of resources inside a parent
     * directory, one case where this option can be set as True is when a folder is
     * required to be deleted recursively, considering that by default the folders
     * are ignored and that a folder may be empty some times there could be issues
     * because the deletion will not reach these resources.
     *
     * @var boolean
     */
    public $skip_directories = true;

    /**
     * Class constructor.
     */
    public function __construct()
    {
    }

    /**
     * Retrieve a long text string with signatures of all the files contained
     * in the main and subdirectories of the folder specified, also the filesize
     * and md5sum of that file. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @param  string  $directory Parent directory where the filesystem scan will start.
     * @param  boolean $as_array  Whether the result of the operation will be returned as an array or string.
     * @return array              List of files in the main and subdirectories of the folder specified.
     */
    public function get_directory_tree_md5($directory = '', $as_array = false)
    {
        $project_signatures = '';
        $abs_path = rtrim(ABSPATH, DIRECTORY_SEPARATOR);
        $files = $this->get_directory_tree($directory);

        if ($as_array) {
            $project_signatures = array();
        }

        if ($files) {
            sort($files);

            foreach ($files as $filepath) {
                $file_checksum = @md5_file($filepath);
                $filesize = @filesize($filepath);

                if ($as_array) {
                    $basename = str_replace($abs_path . DIRECTORY_SEPARATOR, '', $filepath);
                    $project_signatures[ $basename ] = array(
                        'filepath' => $filepath,
                        'checksum' => $file_checksum,
                        'filesize' => $filesize,
                        'created_at' => @filectime($filepath),
                        'modified_at' => @filemtime($filepath),
                    );
                } else {
                    $filepath = str_replace($abs_path, $abs_path . DIRECTORY_SEPARATOR, $filepath);
                    $project_signatures .= sprintf(
                        "%s%s%s%s\n",
                        $file_checksum,
                        $filesize,
                        chr(32),
                        $filepath
                    );
                }
            }
        }

        return $project_signatures;
    }

    /**
     * Retrieve a list with all the files contained in the main and subdirectories
     * of the folder specified. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @param  string $directory Parent directory where the filesystem scan will start.
     * @return array             List of files in the main and subdirectories of the folder specified.
     */
    public function get_directory_tree($directory = '')
    {
        if (file_exists($directory) && is_dir($directory)) {
            $tree = array();

            // Check whether the ignore scanning feature is enabled or not.
            if (SucuriScanFSScanner::will_ignore_scanning()) {
                $this->ignored_directories = SucuriScanFSScanner::get_ignored_directories();
            }

            switch ($this->scan_interface) {
                case 'spl':
                    if ($this->is_spl_available()) {
                        $tree = $this->get_directory_tree_with_spl($directory);
                    } else {
                        $this->scan_interface = 'opendir';
                        SucuriScanOption::update_option(':scan_interface', $this->scan_interface);
                        $tree = $this->get_directory_tree($directory);
                    }
                    break;

                case 'glob':
                    $tree = $this->get_directory_tree_with_glob($directory);
                    break;

                case 'opendir':
                    $tree = $this->get_directory_tree_with_opendir($directory);
                    break;

                default:
                    $this->scan_interface = 'spl';
                    $tree = $this->get_directory_tree($directory);
                    break;
            }

            return $tree;
        }

        return false;
    }

    /**
     * Find a file under the directory tree specified.
     *
     * @param  string $filename  Name of the folder or file being scanned at the moment.
     * @param  string $directory Directory where the scanner is located at the moment.
     * @return array             List of file paths where the file was found.
     */
    public function find_file($filename = '', $directory = null)
    {
        $file_paths = array();

        if (is_null($directory)
            && defined('ABSPATH')
        ) {
            $directory = ABSPATH;
        }

        if (is_dir($directory)) {
            $dir_tree = $this->get_directory_tree($directory);

            foreach ($dir_tree as $filepath) {
                if (stripos($filepath, $filename) !== false) {
                    $file_paths[] = $filepath;
                }
            }
        }

        return $file_paths;
    }

    /**
     * Check whether the built-in class SplFileObject is available in the system
     * or not, it is required to have PHP >= 5.1.0. The SplFileObject class offers
     * an object oriented interface for a file.
     *
     * @link https://www.php.net/manual/en/class.splfileobject.php
     *
     * @return boolean Whether the PHP class "SplFileObject" is available or not.
     */
    public static function is_spl_available()
    {
        return (bool) class_exists('SplFileObject');
    }

    /**
     * Retrieve a list with all the files contained in the main and subdirectories
     * of the folder specified. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @link https://www.php.net/manual/en/class.recursivedirectoryiterator.php
     * @see  RecursiveDirectoryIterator extends FilesystemIterator
     * @see  FilesystemIterator         extends DirectoryIterator
     * @see  DirectoryIterator          extends SplFileInfo
     * @see  SplFileInfo
     *
     * @param  string $directory Parent directory where the filesystem scan will start.
     * @return array             List of files in the main and subdirectories of the folder specified.
     */
    private function get_directory_tree_with_spl($directory = '')
    {
        $files = array();
        $filepath = @realpath($directory);
        $objects = array();

        // Exception for directory name must not be empty.
        if ($filepath === false) {
            return $files;
        }

        if (!class_exists('FilesystemIterator')) {
            $this->scan_interface = 'opendir';
            SucuriScanOption::update_option(':scan_interface', $this->scan_interface);
            $alternative_tree = $this->get_directory_tree($directory);

            return $alternative_tree;
        }

        try {
            if ($this->run_recursively) {
                $flags = FilesystemIterator::KEY_AS_PATHNAME
                    | FilesystemIterator::CURRENT_AS_FILEINFO
                    | FilesystemIterator::SKIP_DOTS
                    | FilesystemIterator::UNIX_PATHS;
                $objects = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($filepath, $flags),
                    RecursiveIteratorIterator::SELF_FIRST,
                    RecursiveIteratorIterator::CATCH_GET_CHILD
                );
            } else {
                $objects = new DirectoryIterator($filepath);
            }
        } catch (RuntimeException $exception) {
            SucuriScanEvent::report_exception($exception);
        }

        foreach ($objects as $filepath => $fileinfo) {
            $filename = $fileinfo->getFilename();

            if ($this->ignore_folderpath(null, $filename)
                || (
                    $this->skip_directories === true
                    && $fileinfo->isDir()
                )
            ) {
                continue;
            }

            if ($this->run_recursively) {
                $directory = dirname($filepath);
            } else {
                $directory = $fileinfo->getPath();
                $filepath = $directory . '/' . $filename;
            }

            if ($this->ignore_folderpath($directory, $filename)
                || $this->ignore_filepath($filename)
            ) {
                continue;
            }

            $files[] = $filepath;
        }

        return $files;
    }

    /**
     * Retrieve a list with all the files contained in the main and subdirectories
     * of the folder specified. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @param  string $directory Parent directory where the filesystem scan will start.
     * @return array             List of files in the main and subdirectories of the folder specified.
     */
    private function get_directory_tree_with_glob($directory = '')
    {
        $files = array();
        $directory_pattern = sprintf('%s/*', rtrim($directory, '/'));
        $files_found = @glob($directory_pattern);

        if (is_array($files_found)) {
            foreach ($files_found as $filepath) {
                $filepath = @realpath($filepath);
                $directory = dirname($filepath);
                $filepath_parts = explode('/', $filepath);
                $filename = array_pop($filepath_parts);

                if (is_dir($filepath)) {
                    if ($this->ignore_folderpath($directory, $filename)) {
                        continue;
                    }

                    if ($this->run_recursively) {
                        $sub_files = $this->get_directory_tree_with_glob($filepath);

                        if ($sub_files) {
                            $files = array_merge($files, $sub_files);
                        }
                    }
                } elseif ($this->ignore_filepath($filename)) {
                    continue;
                } else {
                    $files[] = $filepath;
                }
            }
        }

        return $files;
    }

    /**
     * Retrieve a list with all the files contained in the main and subdirectories
     * of the folder specified. Some folders and files will be ignored depending
     * on some rules defined by the developer.
     *
     * @param  string $directory Parent directory where the filesystem scan will start.
     * @return array             List of files in the main and subdirectories of the folder specified.
     */
    private function get_directory_tree_with_opendir($directory = '')
    {
        $files = array();
        $dh = @opendir($directory);

        if (!$dh) {
            return false;
        }

        while (($filename = readdir($dh)) !== false) {
            $filepath = @realpath($directory . '/' . $filename);

            if ($filepath === false) {
                continue;
            } elseif (is_dir($filepath)) {
                if ($this->ignore_folderpath($directory, $filename)) {
                    continue;
                }

                if ($this->run_recursively) {
                    $sub_files = $this->get_directory_tree_with_opendir($filepath);

                    if ($sub_files) {
                        $files = array_merge($files, $sub_files);
                    }
                }
            } else {
                if ($this->ignore_filepath($filename)) {
                    continue;
                }
                $files[] = $filepath;
            }
        }

        closedir($dh);
        return $files;
    }

    /**
     * Skip some specific directories and file paths from the filesystem scan.
     *
     * @param  string  $directory Directory where the scanner is located at the moment.
     * @param  string  $filename  Name of the folder or file being scanned at the moment.
     * @return boolean            Either TRUE or FALSE representing that the scan should ignore this folder or not.
     */
    private function ignore_folderpath($directory = '', $filename = '')
    {
        // Ignoring current and parent folders.
        if ($filename == '.' || $filename == '..') {
            return true;
        }

        if ($this->ignore_directories) {
            // Ignore directories based on a common regular expression.
            $filepath = @realpath($directory . '/' . $filename);
            $pattern = '/\/wp-content\/(uploads|cache|backup|w3tc)/';

            if (preg_match($pattern, $filepath)) {
                return true;
            }

            // Ignore directories specified by the administrator.
            if (!empty($this->ignored_directories)) {
                foreach ($this->ignored_directories['directories'] as $ignored_dir) {
                    if (strpos($directory, $ignored_dir) !== false
                        || strpos($filepath, $ignored_dir) !== false
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Skip some specific files from the filesystem scan.
     *
     * @param  string  $filename Name of the folder or file being scanned at the moment.
     * @return boolean           Either TRUE or FALSE representing that the scan should ignore this filename or not.
     */
    private function ignore_filepath($filename = '')
    {
        if (!$this->ignore_files) {
            return false;
        }

        // Ignoring backup files from our clean ups.
        if (strpos($filename, '_sucuribackup.') !== false) {
            return true;
        }

        // Ignore files specified by the administrator.
        if (!empty($this->ignored_directories)) {
            foreach ($this->ignored_directories['directories'] as $ignored_dir) {
                if (strpos($ignored_dir, $filename) !== false) {
                    return true;
                }
            }
        }

        // Any file maching one of these rules WILL NOT be ignored.
        if (( strpos($filename, '.php') !== false) ||
            ( strpos($filename, '.htm') !== false) ||
            ( strpos($filename, '.js') !== false) ||
            ( strcmp($filename, '.htaccess') == 0     ) ||
            ( strcmp($filename, 'php.ini') == 0     )
        ) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve a list of unique directory paths.
     *
     * @param  array $dir_tree A list of files under a directory.
     * @return array           A list of unique directory paths.
     */
    public function get_diretories_only($dir_tree = array())
    {
        $dirs = array();

        if (is_string($dir_tree)) {
            $dir_tree = $this->get_directory_tree($dir_tree);
        }

        if (is_array($dir_tree) && !empty($dir_tree)) {
            foreach ($dir_tree as $filepath) {
                $dir_path = dirname($filepath);

                if (is_array($dirs)
                    && !in_array($dir_path, $dirs)
                    && array_key_exists('directories', $this->ignored_directories)
                    && is_array($this->ignored_directories['directories'])
                    && !in_array($dir_path, $this->ignored_directories['directories'])
                ) {
                    $dirs[] = $dir_path;
                }
            }
        }

        return $dirs;
    }

    /**
     * Returns a list of lines matching the specified pattern in all the files found
     * in the specified directory, each entry in the list contains the relative path
     * of the file and the number of the line where the pattern was found, as well
     * as the string around the pattern in that line.
     *
     * @param  string $directory Directory where the scanner is located at the moment.
     * @param  string $pattern   Text that will be searched inside each file.
     * @return array             Associative list with the file path and line number of the match.
     */
    public function grep_pattern($directory = '', $pattern = '')
    {
        $dir_tree = $this->get_directory_tree($directory);
        $pattern = '/.*' . str_replace('/', '\/', $pattern) . '.*/';
        $results = array();

        if (class_exists('SplFileObject')
            && class_exists('RegexIterator')
            && SucuriScan::is_valid_pattern($pattern)
        ) {
            foreach ($dir_tree as $file_path) {
                try {
                    $fobject = new SplFileObject($file_path);
                    $fstream = new RegexIterator($fobject, $pattern, RegexIterator::MATCH);

                    foreach ($fstream as $key => $ltext) {
                        $lnumber = ( $key + 1 );
                        $ltext = str_replace("\n", '', $ltext);
                        $fpath = str_replace($directory, '', $file_path);
                        $loutput = sprintf('%s:%d:%s', $fpath, $lnumber, $ltext);
                        $results[] = array(
                            'file_path' => $file_path,
                            'relative_path' => $fpath,
                            'line_number' => $lnumber,
                            'line_text' => $ltext,
                            'output' => $loutput,
                        );
                    }
                } catch (RuntimeException $exception) {
                    SucuriScanEvent::report_exception($exception);
                }
            }
        }

        return $results;
    }

    /**
     * Remove a directory recursively.
     *
     * @param  string  $directory Path of the existing directory that will be removed.
     * @return boolean            TRUE if all the files and folder inside the directory were removed.
     */
    public function remove_directory_tree($directory = '')
    {
        $dir_tree = $this->get_directory_tree($directory);

        if ($dir_tree) {
            $dirs_only = array();

            // Include the parent directory as the first entry.
            $dirs_only[] = $directory;

            /**
             * Delete all the files and symbolic links recursively and append the
             * directories in a list to delete them later when we are sure that all files
             * were successfully deleted, this is because PHP does not allows to delete non-
             * empty folders.
             */
            foreach ($dir_tree as $filepath) {
                if (is_dir($filepath)) {
                    $dirs_only[] = $filepath;
                } else {
                    @unlink($filepath);
                }
            }

            if (!function_exists('sucuriscan_strlen_diff')) {
                /**
                 * Evaluates the difference between the length of two strings.
                 *
                 * @param  string  $a First string of characters that will be measured.
                 * @param  string  $b Second string of characters that will be measured.
                 * @return integer    The difference in length between the two strings.
                 */
                function sucuriscan_strlen_diff($a = '', $b = '')
                {
                    return strlen($b) - strlen($a);
                }
            }

            // Sort the directories by deep level in ascendant order.
            $dirs_only = array_unique($dirs_only);
            usort($dirs_only, 'sucuriscan_strlen_diff');

            // Delete all the directories starting from the deepest level.
            foreach ($dirs_only as $dir_path) {
                @rmdir($dir_path);
            }

            return true;
        }

        return false;
    }

    /**
     * Return the lines of a file as an array, it will automatically remove the new
     * line characters from the end of each line, and skip empty lines from the
     * list.
     *
     * @param  string $filepath Path to the file.
     * @return array            An array where each element is a line in the file.
     */
    public static function file_lines($filepath = '')
    {
        return @file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * Function to emulate the UNIX tail function by displaying the last X number of
     * lines in a file. Useful for large files, such as logs, when you want to
     * process lines in PHP or write lines to a database.
     *
     * @param  string  $file_path Path to the file.
     * @param  integer $lines     Number of lines to retrieve from the end of the file.
     * @param  boolean $adaptive  Whether the buffer will adapt to a specific number of bytes or not.
     * @return string             Text contained at the end of the file.
     */
    public static function tail_file($file_path = '', $lines = 1, $adaptive = true)
    {
        $file = @fopen($file_path, 'rb');
        $limit = $lines;

        if ($file) {
            fseek($file, -1, SEEK_END);

            if ($adaptive && $lines < 2) {
                $buffer = 64;
            } elseif ($adaptive && $lines < 10) {
                $buffer = 512;
            } else {
                $buffer = 4096;
            }

            if (fread($file, 1) != "\n") {
                $lines -= 1;
            }

            $output = '';
            $chunk = '';

            while (ftell($file) > 0 && $lines >= 0) {
                $seek = min(ftell($file), $buffer);
                fseek($file, -$seek, SEEK_CUR);
                $chunk = fread($file, $seek);
                $output = $chunk . $output;
                fseek($file, -mb_strlen($chunk, '8bit'), SEEK_CUR);
                $lines -= substr_count($chunk, "\n");
            }

            fclose($file);

            $lines_arr = explode("\n", $output);
            $lines_count = count($lines_arr);
            $result = array_slice($lines_arr, ($lines_count - $limit));

            return $result;
        }

        return false;
    }

    /**
     * Gets inode change time of file.
     *
     * @param  string  $file_path Path to the file.
     * @return integer            Time the file was last changed.
     */
    public static function creation_time($file_path = '')
    {
        if (file_exists($file_path)) {
            clearstatcache($file_path);
            return filectime($file_path);
        }

        return 0;
    }

    /**
     * Gets file modification time.
     *
     * @param  string  $file_path Path to the file.
     * @return integer            Time the file was last modified.
     */
    public static function modification_time($file_path = '')
    {
        if (file_exists($file_path)) {
            clearstatcache($file_path);
            return filemtime($file_path);
        }

        return 0;
    }

    /**
     * Tells whether the filename is a directory, symbolic link, or file.
     *
     * @param  string $path Path to the file.
     * @return string       Type of resource: dir, link, file.
     */
    public static function get_resource_type($path = '')
    {
        if (is_dir($path)) {
            return 'dir';
        } elseif (is_link($path)) {
            return 'link';
        } elseif (is_file($path)) {
            return 'file';
        } else {
            return 'unknown';
        }
    }
}

/**
 * File-based cache library.
 *
 * WP_Object_Cache [1] is WordPress' class for caching data which may be
 * computationally expensive to regenerate, such as the result of complex
 * database queries. However the object cache is non-persistent. This means that
 * data stored in the cache resides in memory only and only for the duration of
 * the request. Cached data will not be stored persistently across page loads
 * unless of the installation of a 3party persistent caching plugin [2].
 *
 * [1] https://codex.wordpress.org/Class_Reference/WP_Object_Cache
 * [2] https://codex.wordpress.org/Class_Reference/WP_Object_Cache#Persistent_Caching
 */
class SucuriScanCache extends SucuriScan
{
    /**
     * The unique name (or identifier) of the file with the data.
     *
     * The file should be located in the same folder where the dynamic data
     * generated by the plugin is stored, and using the following format [1], it
     * most be a PHP file because it is expected to have an exit point in the first
     * line of the file causing it to stop the execution if a unauthorized user
     * tries to access it directly.
     *
     * [1] /public/data/sucuri-DATASTORE.php
     *
     * @var null|string
     */
    private $datastore = null;

    /**
     * The full path of the datastore file.
     *
     * @var string
     */
    private $datastore_path = '';

    /**
     * Whether the datastore file is usable or not.
     *
     * This variable will only be TRUE if the datastore file specified exists, is
     * writable and readable, in any other case it will always be FALSE.
     *
     * @var boolean
     */
    private $usable_datastore = false;

    /**
     * Class constructor.
     *
     * @param  string $datastore Unique name (or identifier) of the file with the data.
     * @return void
     */
    public function __construct($datastore = '', $auto_create = true)
    {
        $this->datastore = $datastore;
        $this->datastore_path = $this->datastoreFilePath($auto_create);
        $this->usable_datastore = (bool) $this->datastore_path;
    }

    /**
     * Default attributes for every datastore file.
     *
     * @return string Default attributes for every datastore file.
     */
    private function datastoreDefaultInfo()
    {
        $attrs = array(
            'datastore' => $this->datastore,
            'created_on' => time(),
            'updated_on' => time(),
        );

        return $attrs;
    }

    /**
     * Default content of every datastore file.
     *
     * @param  array  $finfo Rainbow table with the key names and decoded values.
     * @return string        Default content of every datastore file.
     */
    private function datastoreInfo($finfo = array())
    {
        $attrs = $this->datastoreDefaultInfo();
        $info_is_available = (bool) isset($finfo['info']);
        $info  = "<?php\n";

        foreach ($attrs as $attr_name => $attr_value) {
            if ($info_is_available
                && $attr_name != 'updated_on'
                && isset($finfo['info'][$attr_name])
            ) {
                $attr_value = $finfo['info'][$attr_name];
            }

            $info .= sprintf("// %s=%s;\n", $attr_name, $attr_value);
        }

        $info .= "exit(0);\n";
        $info .= "?>\n";

        return $info;
    }

    /**
     * Check if the datastore file exists, if it's writable and readable by the same
     * user running the server, in case that it does not exists the function will
     * tries to create it by itself with the right permissions to use it.
     *
     * @param  boolean $auto_create Automatically create the file if not exists or not.
     * @return string               The full path where the datastore file is located, FALSE otherwise.
     */
    private function datastoreFilePath($auto_create = false)
    {
        if (!is_null($this->datastore)) {
            $folder_path = $this->datastore_folder_path();
            $file_path = $folder_path . 'sucuri-' . $this->datastore . '.php';

            // Create the datastore parent directory.
            if (!file_exists($folder_path)) {
                @mkdir($folder_path, 0755, true);
            }

            // Create the datastore file is it does not exists and the folder is writable.
            if (!file_exists($file_path)
                && is_writable($folder_path)
                && $auto_create === true
            ) {
                @file_put_contents($file_path, $this->datastoreInfo());
            }

            // Continue the operation after an attemp to create the datastore file.
            if (file_exists($file_path)
                && is_writable($file_path)
                && is_readable($file_path)
            ) {
                return $file_path;
            }
        }

        return false;
    }

    /**
     * Define the pattern for the regular expression that will check if a cache key
     * is valid or not, and also will help the function that parses the file to see
     * which characters of each line are the keys are which are the values.
     *
     * @param  string $action Either "valid", "content", or "header".
     * @return string Cache key pattern.
     */
    private function keyPattern($action = 'valid')
    {
        if ($action == 'valid') {
            return '/^([0-9a-zA-Z_]+)$/';
        } elseif ($action == 'content') {
            return '/^([0-9a-zA-Z_]+):(.+)/';
        } elseif ($action == 'header') {
            return '/^\/\/ ([a-z_]+)=(.*);$/';
        }

        return false;
    }

    /**
     * Check whether a key has a valid name or not.
     *
     * @param  string  $key Unique name to identify the data in the datastore file.
     * @return boolean      TRUE if the format of the key name is valid, FALSE otherwise.
     */
    private function validKeyName($key = '')
    {
        return (bool) @preg_match($this->keyPattern('valid'), $key);
    }

    /**
     * Update the content of the datastore file with the new entries.
     *
     * @param  array   $finfo Rainbow table with the key names and decoded values.
     * @return boolean        TRUE if the operation finished successfully, FALSE otherwise.
     */
    private function saveNewEntries($finfo = array())
    {
        $data_string = $this->datastoreInfo($finfo);

        if (!empty($finfo)) {
            foreach ($finfo['entries'] as $key => $data) {
                if ($this->validKeyName($key)) {
                    $data = json_encode($data);
                    $data_string .= sprintf("%s:%s\n", $key, $data);
                }
            }
        }

        return (bool) @file_put_contents($this->datastore_path, $data_string);
    }

    /**
     * Retrieve and parse the datastore file, and generate a rainbow table with the
     * key names and decoded data as the values of each entry. Duplicated key names
     * will be removed automatically while adding the keys to the array and their
     * values will correspond to the first occurrence found in the file.
     *
     * @param  boolean $assoc When TRUE returned objects will be converted into associative arrays.
     * @return array          Rainbow table with the key names and decoded values.
     */
    private function getDatastoreContent($assoc = false)
    {
        $data_object = array(
            'info' => array(),
            'entries' => array(),
        );

        if ($this->usable_datastore) {
            $data_lines = SucuriScanFileInfo::file_lines($this->datastore_path);

            if (!empty($data_lines)) {
                foreach ($data_lines as $line) {
                    if (preg_match($this->keyPattern('header'), $line, $match)) {
                        $data_object['info'][ $match[1] ] = $match[2];
                    } elseif (preg_match($this->keyPattern('content'), $line, $match)) {
                        if ($this->validKeyName($match[1])
                            && !array_key_exists($match[1], $data_object)
                        ) {
                            $data_object['entries'][$match[1]] = @json_decode($match[2], $assoc);
                        }
                    }
                }
            }
        }

        return $data_object;
    }

    /**
     * Retrieve the headers of the datastore file.
     *
     * Each datastore file has a list of attributes at the beginning of the it with
     * information like the creation and last update time. If you are extending the
     * functionality of these headers please refer to the function that contains the
     * default attributes and their values [1].
     *
     * [1] SucuriScanCache::datastoreDefaultInfo()
     *
     * @return array Default content of every datastore file.
     */
    public function getDatastoreInfo()
    {
        $finfo = $this->getDatastoreContent();

        if (!empty($finfo['info'])) {
            return $finfo['info'];
        }

        return false;
    }

    /**
     * Get the total number of unique entries in the datastore file.
     *
     * @param  array   $finfo Rainbow table with the key names and decoded values.
     * @return integer        Total number of unique entries found in the datastore file.
     */
    public function getCount($finfo = null)
    {
        if (!is_array($finfo)) {
            $finfo = $this->getDatastoreContent();
        }

        return count($finfo['entries']);
    }

    /**
     * Check whether the last update time of the datastore file has surpassed the
     * lifetime specified for a key name. This function is the only one related with
     * the caching process, any others besides this are just methods used to handle
     * the data inside those files.
     *
     * @param  integer $lifetime Life time of the key in the datastore file.
     * @param  array   $finfo    Rainbow table with the key names and decoded values.
     * @return boolean           TRUE if the life time of the data has expired, FALSE otherwise.
     */
    public function dataHasExpired($lifetime = 0, $finfo = null)
    {
        if (is_null($finfo)) {
            $finfo = $this->getDatastoreContent();
        }

        if ($lifetime > 0 && !empty($finfo['info'])) {
            $diff_time = time() - intval($finfo['info']['updated_on']);

            if ($diff_time >= $lifetime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Execute the action using the key name and data specified.
     *
     * @param  string  $key      Unique name to identify the data in the datastore file.
     * @param  string  $data     Mixed data stored in the datastore file following the unique key name.
     * @param  string  $action   Either add, set, get, or delete.
     * @param  integer $lifetime Life time of the key in the datastore file.
     * @param  boolean $assoc    When TRUE returned objects will be converted into associative arrays.
     * @return boolean           TRUE if the operation finished successfully, FALSE otherwise.
     */
    private function handleKeyData($key = '', $data = null, $action = '', $lifetime = 0, $assoc = false)
    {
        if ($this->validKeyName($key)
            && $this->usable_datastore
        ) {
            $finfo = $this->getDatastoreContent($assoc);

            if ($action == 'set' || $action == 'add') {
                $finfo['entries'][$key] = $data;

                return $this->saveNewEntries($finfo);
            } elseif ($action == 'get') {
                if (!$this->dataHasExpired($lifetime, $finfo)
                    && array_key_exists($key, $finfo['entries'])
                ) {
                    return $finfo['entries'][$key];
                }
            } elseif ($action == 'get_all') {
                if (!$this->dataHasExpired($lifetime, $finfo)) {
                    return $finfo['entries'];
                }
            } elseif ($action == 'exists') {
                if (!$this->dataHasExpired($lifetime, $finfo)
                    && array_key_exists($key, $finfo['entries'])
                ) {
                    return true;
                }
            } elseif ($action == 'delete') {
                unset($finfo['entries'][$key]);

                return $this->saveNewEntries($finfo);
            }
        }

        return false;
    }

    /**
     * JSON-encode the data and store it in the datastore file identifying it with
     * the key name, the data will be added to the file even if the key is
     * duplicated, but when getting the value of the same key later again it will
     * return only the value of the first occurrence found in the file.
     *
     * @param  string  $key  Unique name to identify the data in the datastore file.
     * @param  string  $data Mixed data stored in the datastore file following the unique key name.
     * @return boolean       TRUE if the data was stored successfully, FALSE otherwise.
     */
    public function add($key = '', $data = '')
    {
        return $this->handleKeyData($key, $data, 'add');
    }

    /**
     * Update the data of all the key names matching the one specified.
     *
     * @param  string  $key  Unique name to identify the data in the datastore file.
     * @param  string  $data Mixed data stored in the datastore file following the unique key name.
     * @return boolean       TRUE if the data was stored successfully, FALSE otherwise.
     */
    public function set($key = '', $data = '')
    {
        return $this->handleKeyData($key, $data, 'set');
    }

    /**
     * Retrieve the first occurrence of the key found in the datastore file.
     *
     * @param  string  $key      Unique name to identify the data in the datastore file.
     * @param  integer $lifetime Life time of the key in the datastore file.
     * @param  boolean $assoc    When TRUE returned objects will be converted into associative arrays.
     * @return string            Mixed data stored in the datastore file following the unique key name.
     */
    public function get($key = '', $lifetime = 0, $assoc = false)
    {
        $assoc = ($assoc == 'array' ? true : $assoc);

        return $this->handleKeyData($key, null, 'get', $lifetime, $assoc);
    }

    /**
     * Retrieve all the entries found in the datastore file.
     *
     * @param  integer $lifetime Life time of the key in the datastore file.
     * @param  boolean $assoc    When TRUE returned objects will be converted into associative arrays.
     * @return string            Mixed data stored in the datastore file following the unique key name.
     */
    public function getAll($lifetime = 0, $assoc = false)
    {
        $assoc = ($assoc == 'array' ? true : $assoc);

        return $this->handleKeyData('temp', null, 'get_all', $lifetime, $assoc);
    }

    /**
     * Check whether a specific key exists in the datastore file.
     *
     * @param  string  $key Unique name to identify the data in the datastore file.
     * @return boolean      TRUE if the key exists in the datastore file, FALSE otherwise.
     */
    public function exists($key = '')
    {
        return $this->handleKeyData($key, null, 'exists');
    }

    /**
     * Delete any entry from the datastore file matching the key name specified.
     *
     * @param  string  $key Unique name to identify the data in the datastore file.
     * @return boolean      TRUE if the entries were removed, FALSE otherwise.
     */
    public function delete($key = '')
    {
        return $this->handleKeyData($key, null, 'delete');
    }

    /**
     * Remove all the entries from the datastore file.
     *
     * @return boolean Always TRUE unless the datastore file is not writable.
     */
    public function flush()
    {
        $finfo = $this->getDatastoreContent();

        return $this->saveNewEntries($finfo);
    }
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
 * Note that the _site_ functions are essentially the same as their
 * counterparts. The only differences occur for WP Multisite, when the options
 * apply network-wide and the data is stored in the wp_sitemeta table under the
 * given custom name.
 *
 * @see https://codex.wordpress.org/Option_Reference
 * @see https://codex.wordpress.org/Options_API
 */
class SucuriScanOption extends SucuriScanRequest
{

    /**
     * Default values for all the plugin's options.
     *
     * @return array Default values for all the plugin's options.
     */
    public static function get_default_option_values()
    {
        $defaults = array(
            'sucuriscan_account' => '',
            'sucuriscan_addr_header' => 'HTTP_X_SUCURI_CLIENTIP',
            'sucuriscan_ads_visibility' => 'enabled',
            'sucuriscan_api_key' => false,
            'sucuriscan_api_service' => 'enabled',
            'sucuriscan_audit_report' => 'disabled',
            'sucuriscan_cloudproxy_apikey' => '',
            'sucuriscan_collect_wrong_passwords' => 'disabled',
            'sucuriscan_comment_monitor' => 'disabled',
            'sucuriscan_datastore_path' => '',
            'sucuriscan_dismiss_setup' => 'disabled',
            'sucuriscan_dns_lookups' => 'enabled',
            'sucuriscan_email_subject' => 'Sucuri Alert, :domain, :event',
            'sucuriscan_emails_per_hour' => 5,
            'sucuriscan_emails_sent' => 0,
            'sucuriscan_errorlogs_limit' => 30,
            'sucuriscan_fs_scanner' => 'enabled',
            'sucuriscan_heartbeat' => 'enabled',
            'sucuriscan_heartbeat_autostart' => 'enabled',
            'sucuriscan_heartbeat_interval' => 'standard',
            'sucuriscan_heartbeat_pulse' => 15,
            'sucuriscan_ignore_scanning' => 'disabled',
            'sucuriscan_ignored_events' => '',
            'sucuriscan_last_email_at' => time(),
            'sucuriscan_lastlogin_redirection' => 'enabled',
            'sucuriscan_logs4report' => 500,
            'sucuriscan_maximum_failed_logins' => 30,
            'sucuriscan_notify_bruteforce_attack' => 'disabled',
            'sucuriscan_notify_failed_login' => 'enabled',
            'sucuriscan_notify_plugin_activated' => 'disabled',
            'sucuriscan_notify_plugin_change' => 'disabled',
            'sucuriscan_notify_plugin_deactivated' => 'disabled',
            'sucuriscan_notify_plugin_deleted' => 'disabled',
            'sucuriscan_notify_plugin_installed' => 'disabled',
            'sucuriscan_notify_plugin_updated' => 'disabled',
            'sucuriscan_notify_post_publication' => 'enabled',
            'sucuriscan_notify_scan_checksums' => 'disabled',
            'sucuriscan_notify_settings_updated' => 'disabled',
            'sucuriscan_notify_success_login' => 'enabled',
            'sucuriscan_notify_theme_activated' => 'disabled',
            'sucuriscan_notify_theme_deleted' => 'disabled',
            'sucuriscan_notify_theme_editor' => 'enabled',
            'sucuriscan_notify_theme_installed' => 'disabled',
            'sucuriscan_notify_theme_updated' => 'disabled',
            'sucuriscan_notify_to' => '',
            'sucuriscan_notify_user_registration' => 'disabled',
            'sucuriscan_notify_website_updated' => 'disabled',
            'sucuriscan_notify_widget_added' => 'disabled',
            'sucuriscan_notify_widget_deleted' => 'disabled',
            'sucuriscan_parse_errorlogs' => 'enabled',
            'sucuriscan_prettify_mails' => 'disabled',
            'sucuriscan_request_timeout' => 5,
            'sucuriscan_revproxy' => 'disabled',
            'sucuriscan_runtime' => 0,
            'sucuriscan_scan_checksums' => 'enabled',
            'sucuriscan_scan_errorlogs' => 'disabled',
            'sucuriscan_scan_frequency' => 'twicedaily',
            'sucuriscan_scan_interface' => 'spl',
            'sucuriscan_selfhosting_fpath' => '',
            'sucuriscan_selfhosting_monitor' => 'disabled',
            'sucuriscan_site_version' => '0.0',
            'sucuriscan_sitecheck_counter' => 0,
            'sucuriscan_sitecheck_scanner' => 'enabled',
            'sucuriscan_use_wpmail' => 'enabled',
            'sucuriscan_verify_ssl_cert' => 'false',
            'sucuriscan_xhr_monitor' => 'disabled',
        );

        return $defaults;
    }

    /**
     * Name of all valid plugin's options.
     *
     * @return array Name of all valid plugin's options.
     */
    public static function get_default_option_names()
    {
        $options = self::get_default_option_values();
        $names = array_keys($options);

        return $names;
    }

    /**
     * Check whether an option is used in the plugin or not.
     *
     * @param  string  $option_name Name of the option that will be checked.
     * @return boolean              True if the option is part of the plugin, False otherwise.
     */
    public static function is_valid_plugin_option($option_name = '')
    {
        $valid_options = self::get_default_option_names();
        $is_valid_option = (bool) array_key_exists($option_name, $valid_options);

        return $is_valid_option;
    }

    /**
     * Retrieve the default values for some specific options.
     *
     * @param  string|array $settings Either an array that will be complemented or a string with the name of the option.
     * @return string|array           The default values for the specified options.
     */
    public static function get_default_options($settings = '')
    {
        $default_options = self::get_default_option_values();

        // Use framework built-in function.
        if (function_exists('get_option')) {
            $admin_email = get_option('admin_email');
            $default_options['sucuriscan_account'] = $admin_email;
            $default_options['sucuriscan_notify_to'] = $admin_email;
        }

        if (is_array($settings)) {
            foreach ($default_options as $option_name => $option_value) {
                if (!isset($settings[ $option_name ])) {
                    $settings[ $option_name ] = $option_value;
                }
            }

            return $settings;
        }

        if (is_string($settings)
            && !empty($settings)
            && array_key_exists($settings, $default_options)
        ) {
            return $default_options[ $settings ];
        }

        return false;
    }

    /**
     * Alias function for the method Common::SucuriScan_Get_Options()
     *
     * This function search the specified option in the database, not only the options
     * set by the plugin but all the options set for the site. If the value retrieved
     * is FALSE the method tries to search for a default value.
     *
     * To facilitate the development, you can prefix the name of the key in the
     * request (when accessing it) with a single colon, this function will
     * automatically replace that character with the unique identifier of the
     * plugin.
     *
     * @see https://codex.wordpress.org/Function_Reference/get_option
     *
     * @param  string $option_name Optional parameter that you can use to filter the results to one option.
     * @return string              The value (or default value) of the option specified.
     */
    public static function get_option($option_name = '')
    {
        if (function_exists('update_option')) {
            $option_name = self::variable_prefix($option_name);
            $option_value = get_option($option_name);

            if ($option_value === false && preg_match('/^sucuriscan_/', $option_name)) {
                $option_value = self::get_default_options($option_name);
            }

            return $option_value;
        }

        return false;
    }

    /**
     * Update the value of an database' option.
     *
     * Use the function to update a named option/value pair to the options database
     * table. The option name value is escaped with a special database method before
     * the insert SQL statement but not the option value, this value should always
     * be properly sanitized.
     *
     * @see https://codex.wordpress.org/Function_Reference/update_option
     *
     * @param  string  $option_name  Name of the option to update which must not exceed 64 characters.
     * @param  string  $option_value The new value for the option, can be an integer, string, array, or object.
     * @return boolean               True if option value has changed, false if not or if update failed.
     */
    public static function update_option($option_name = '', $option_value = '')
    {
        if (function_exists('update_option')) {
            $option_name = self::variable_prefix($option_name);

            return update_option($option_name, $option_value);
        }

        return false;
    }

    /**
     * Remove an option from the database.
     *
     * A safe way of removing a named option/value pair from the options database table.
     *
     * @see https://codex.wordpress.org/Function_Reference/delete_option
     *
     * @param  string  $option_name Name of the option to be deleted.
     * @return boolean              True, if option is successfully deleted. False on failure, or option does not exist.
     */
    public static function delete_option($option_name = '')
    {
        if (function_exists('delete_option')) {
            $option_name = self::variable_prefix($option_name);

            return delete_option($option_name);
        }

        return false;
    }

    /**
     * Check whether a setting is enabled or not.
     *
     * @param  string  $option Name of the option to be deleted.
     * @return boolean         True if the option is enabled, false otherwise.
     */
    public static function is_enabled($option = '')
    {
        return (bool) ( self::get_option($option) === 'enabled' );
    }

    /**
     * Check whether a setting is disabled or not.
     *
     * @param  string  $option Name of the option to be deleted.
     * @return boolean         True if the option is disabled, false otherwise.
     */
    public static function is_disabled($option = '')
    {
        return (bool) ( self::get_option($option) === 'disabled' );
    }

    /**
     * Delete all the plugin options from the database.
     *
     * @return void
     */
    public static function delete_plugin_options()
    {
        global $wpdb;

        $options = $wpdb->get_results(
            "SELECT * FROM {$wpdb->options}
            WHERE option_name LIKE 'sucuriscan%'
            ORDER BY option_id ASC"
        );

        foreach ($options as $option) {
            self::delete_option($option->option_name);
        }
    }

    /**
     * Retrieve all the options stored by Wordpress in the database. The options
     * containing the word "transient" are excluded from the results, this function
     * is compatible with multisite instances.
     *
     * @return array All the options stored by Wordpress in the database, except the transient options.
     */
    public static function get_site_options()
    {
        global $wpdb;

        $settings = array();
        $results = $wpdb->get_results(
            "SELECT * FROM {$wpdb->options}
            WHERE option_name NOT LIKE '%_transient_%'
            ORDER BY option_id ASC"
        );

        foreach ($results as $row) {
            $settings[ $row->option_name ] = $row->option_value;
        }

        return $settings;
    }

    /**
     * Check what Wordpress options were changed comparing the values in the database
     * with the values sent through a simple request using a GET or POST method.
     *
     * @param  array $request The content of the global variable GET or POST considering SERVER[REQUEST_METHOD].
     * @return array          A list of all the options that were changes through this request.
     */
    public static function what_options_were_changed($request = array())
    {
        $options_changed = array(
            'original' => array(),
            'changed' => array()
        );

        $site_options = self::get_site_options();

        foreach ($request as $req_name => $req_value) {
            if (array_key_exists($req_name, $site_options)
                && $site_options[ $req_name ] != $req_value
            ) {
                $options_changed['original'][ $req_name ] = $site_options[ $req_name ];
                $options_changed['changed'][ $req_name ] = $req_value;
            }
        }

        return $options_changed;
    }

    /**
     * Check the nonce comming from any of the settings pages.
     *
     * @return boolean TRUE if the nonce is valid, FALSE otherwise.
     */
    public static function check_options_nonce()
    {
        // Create the option_page value if permalink submission.
        if (!isset($_POST['option_page'])
            && isset($_POST['permalink_structure'])
        ) {
            $_POST['option_page'] = 'permalink';
        }

        // Check if the option_page has an allowed value.
        if ($option_page = SucuriScanRequest::post('option_page')) {
            $nonce = '_wpnonce';
            $action = '';

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

            // Check the nonce validity.
            if (!empty($action)
                && isset($_REQUEST[ $nonce ])
                && wp_verify_nonce($_REQUEST[ $nonce ], $action)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a list of the post types ignored to receive email notifications when the
     * "new site content" hook is triggered.
     *
     * @return array List of ignored posts-types to send notifications.
     */
    public static function get_ignored_events()
    {
        $post_types = self::get_option(':ignored_events');
        $post_types_arr = false;

        // Encode (old) serialized data into JSON.
        if (self::is_serialized($post_types)) {
            $post_types_arr = @unserialize($post_types);
            $post_types_fix = json_encode($post_types_arr);
            self::update_option(':ignored_events', $post_types_fix);

            return $post_types_arr;
        } // Decode JSON-encoded data as an array.
        elseif (preg_match('/^\{.+\}$/', $post_types)) {
            $post_types_arr = @json_decode($post_types, true);
        }

        if (!is_array($post_types_arr)) {
            $post_types_arr = array();
        }

        return $post_types_arr;
    }

    /**
     * Add a new post type to the list of ignored events to send notifications.
     *
     * @param  string  $event_name Unique post-type name.
     * @return boolean             Whether the event was ignored or not.
     */
    public static function add_ignored_event($event_name = '')
    {
        if (function_exists('get_post_types')) {
            $post_types = get_post_types();

            // Check if the event is a registered post-type.
            if (array_key_exists($event_name, $post_types)) {
                $ignored_events = self::get_ignored_events();

                // Check if the event is not ignored already.
                if (!array_key_exists($event_name, $ignored_events)) {
                    $ignored_events[ $event_name ] = time();
                    $saved = self::update_option(':ignored_events', json_encode($ignored_events));

                    return $saved;
                }
            }
        }

        return false;
    }

    /**
     * Remove a post type from the list of ignored events to send notifications.
     *
     * @param  string  $event_name Unique post-type name.
     * @return boolean             Whether the event was removed from the list or not.
     */
    public static function remove_ignored_event($event_name = '')
    {
        $ignored_events = self::get_ignored_events();

        if (array_key_exists($event_name, $ignored_events)) {
            unset($ignored_events[ $event_name ]);
            $saved = self::update_option(':ignored_events', json_encode($ignored_events));

            return $saved;
        }

        return false;
    }

    /**
     * Check whether an event is being ignored to send notifications or not.
     *
     * @param  string  $event_name Unique post-type name.
     * @return boolean             Whether an event is being ignored or not.
     */
    public static function is_ignored_event($event_name = '')
    {
        $event_name = strtolower($event_name);
        $ignored_events = self::get_ignored_events();

        if (array_key_exists($event_name, $ignored_events)) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve a list of basic security keys and check whether their values were
     * randomized correctly.
     *
     * @return array Array with three keys: good, missing, bad.
     */
    public static function get_security_keys()
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
                    $response['bad'][ $key_name ] = $key_value;
                } else {
                    $response['good'][ $key_name ] = $key_value;
                }
            } else {
                $response['missing'][ $key_name ] = false;
            }
        }

        return $response;
    }

    /**
     * Change the reverse proxy setting.
     *
     * When enabled this option forces the plugin to override the value of the
     * global IP address variable from the HTTP header selected by the user from the
     * settings. Note that this may also be automatically enabled when the firewall
     * page is activated as it assumes that the proxy is creating a custom HTTP
     * header for the real IP.
     *
     * @param  string $header Valid HTTP header name.
     * @return void
     */
    public static function setRevProxy($action = 'disable')
    {
        $action_d = $action . 'd';
        $message = 'Reverse proxy support was <code>' . $action_d . '</code>';

        self::update_option(':revproxy', $action_d);
        SucuriScanEvent::report_info_event($message);
        SucuriScanEvent::notify_event('plugin_change', $message);
        SucuriScanInterface::info($message);
    }

    /**
     * Change the HTTP header to retrieve the real IP address.
     *
     * @param  string $header Valid HTTP header name.
     * @return void
     */
    public static function setAddrHeader($header = 'REMOTE_ADDR')
    {
        $header = strtoupper($header);
        $allowed = SucuriScan::allowedHttpHeaders(true);

        if (array_key_exists($header, $allowed)) {
            $message = 'HTTP header was set to <code>' . $header . '</code>';

            self::update_option(':addr_header', $header);
            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        } else {
            SucuriScanInterface::error('HTTP header is not in the allowed list');
        }
    }
}

/**
 * System events, reports and actions.
 *
 * An event is an action or occurrence detected by the program that may be
 * handled by the program. Typically events are handled synchronously with the
 * program flow, that is, the program has one or more dedicated places where
 * events are handled, frequently an event loop. Typical sources of events
 * include the user; another source is a hardware device such as a timer. Any
 * program can trigger its own custom set of events as well, e.g. to communicate
 * the completion of a task. A computer program that changes its behavior in
 * response to events is said to be event-driven, often with the goal of being
 * interactive.
 *
 * @see https://en.wikipedia.org/wiki/Event_(computing)
 */
class SucuriScanEvent extends SucuriScan
{

    /**
     * Schedule the task to run the first filesystem scan.
     *
     * @return void
     */
    public static function schedule_task()
    {
        $task_name = 'sucuriscan_scheduled_scan';

        if (!wp_next_scheduled($task_name)) {
            wp_schedule_event(time() + 10, 'twicedaily', $task_name);
        }

        wp_schedule_single_event(time() + 300, $task_name);
    }

    /**
     * Checks last time we ran to avoid running twice (or too often).
     *
     * @param  integer $runtime    When the filesystem scan must be scheduled to run.
     * @param  boolean $force_scan Whether the filesystem scan was forced by an administrator user or not.
     * @return boolean             Either TRUE or FALSE representing the success or fail of the operation respectively.
     */
    private static function verify_run($runtime = 0, $force_scan = false)
    {
        $option_name = ':runtime';
        $last_run = SucuriScanOption::get_option($option_name);
        $current_time = time();

        // The filesystem scanner can be disabled from the settings page.
        if (SucuriScanOption::is_disabled(':fs_scanner')
            && $force_scan === false
        ) {
            return false;
        }

        // Check if the last runtime is too near the current time.
        if ($last_run && !$force_scan) {
            $runtime_diff = $current_time - $runtime;

            if ($last_run >= $runtime_diff) {
                return false;
            }
        }

        SucuriScanOption::update_option($option_name, $current_time);

        return true;
    }

    /**
     * Check whether the current WordPress version must be reported to the API
     * service or not, this is to avoid duplicated information in the audit logs.
     *
     * @return boolean TRUE if the current WordPress version must be reported, FALSE otherwise.
     */
    private static function report_site_version()
    {
        $option_name = ':site_version';
        $reported_version = SucuriScanOption::get_option($option_name);
        $wp_version = self::site_version();

        if ($reported_version != $wp_version) {
            SucuriScanEvent::report_info_event('WordPress version detected ' . $wp_version);
            SucuriScanOption::update_option($option_name, $wp_version);

            return true;
        }

        return false;
    }

    /**
     * Gather all the checksums (aka. file hashes) of this site, send them, and
     * analyze them using the Sucuri Monitoring service, this will generate the
     * audit logs for this site and be part of the integrity checks.
     *
     * @param  boolean $force_scan Whether the filesystem scan was forced by an administrator user or not.
     * @return boolean             TRUE if the filesystem scan was successful, FALSE otherwise.
     */
    public static function filesystem_scan($force_scan = false)
    {
        $minimum_runtime = SUCURISCAN_MINIMUM_RUNTIME;

        if (self::verify_run($minimum_runtime, $force_scan)
            && class_exists('SucuriScanFileInfo')
            && SucuriScanAPI::getPluginKey()
        ) {
            self::report_site_version();

            $file_info = new SucuriScanFileInfo();
            $file_info->scan_interface = SucuriScanOption::get_option(':scan_interface');
            $signatures = $file_info->get_directory_tree_md5(ABSPATH);

            if ($signatures) {
                $hashes_sent = SucuriScanAPI::sendHashes($signatures);

                if ($hashes_sent) {
                    SucuriScanOption::update_option(':runtime', time());
                    return true;
                } else {
                    SucuriScanInterface::error('The file hashes could not be stored.');
                }
            } else {
                SucuriScanInterface::error('The file hashes could not be retrieved, the filesystem scan failed.');
            }
        }

        return false;
    }

    /**
     * Generates an audit event log (to be sent later).
     *
     * @param  integer $severity Importance of the event that will be reported, values from one to five.
     * @param  string  $location In which part of the system was the event triggered.
     * @param  string  $message  The explanation of the event.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           TRUE if the event was logged in the monitoring service, FALSE otherwise.
     */
    private static function report_event($severity = 0, $location = '', $message = '', $internal = false)
    {
        $user = wp_get_current_user();
        $username = false;
        $current_time = date('Y-m-d H:i:s');
        $remote_ip = self::get_remote_addr();

        // Identify current user in session.
        if ($user instanceof WP_User
            && isset($user->user_login)
            && !empty($user->user_login)
        ) {
            if ($user->user_login != $user->display_name) {
                $username = sprintf("\x20%s (%s),", $user->display_name, $user->user_login);
            } else {
                $username = sprintf("\x20%s,", $user->user_login);
            }
        }

        // Fixing severity value.
        $severity = (int) $severity;

        // Convert the severity number into a readable string.
        switch ($severity) {
            case 0:
                $severity_name = 'Debug';
                break;
            case 1:
                $severity_name = 'Notice';
                break;
            case 2:
                $severity_name = 'Info';
                break;
            case 3:
                $severity_name = 'Warning';
                break;
            case 4:
                $severity_name = 'Error';
                break;
            case 5:
                $severity_name = 'Critical';
                break;
            default:
                $severity_name = 'Info';
                break;
        }

        // Mark the event as internal if necessary.
        if ($internal === true) {
            $severity_name = '@' . $severity_name;
        }

        // Clear event message.
        $message = strip_tags($message);
        $message = str_replace("\r", '', $message);
        $message = str_replace("\n", '', $message);
        $message = str_replace("\t", '', $message);

        $event_message = sprintf(
            '%s:%s %s; %s',
            $severity_name,
            $username,
            $remote_ip,
            $message
        );

        return self::sendEventLog($event_message);
    }

    public static function sendEventLog($event_message = '')
    {
        /**
         * Self-hosted Monitor.
         *
         * Send a copy of the event log to a local file, this will allow the
         * administrator of the server to integrate the events monitored by the plugin
         * with a 3rd-party service like OSSEC or similar. More information in the Self-
         * Hosting panel located in the plugin' settings page.
         */
        if (function_exists('sucuriscan_selfhosting_fpath')) {
            $monitor_fpath = sucuriscan_selfhosting_fpath();

            if ($monitor_fpath !== false) {
                $local_event = sprintf(
                    "%s WordPressAudit %s %s : %s\n",
                    date('Y-m-d H:i:s'),
                    SucuriScan::get_top_level_domain(),
                    SucuriScanOption::get_option(':account'),
                    $event_message
                );
                @file_put_contents(
                    $monitor_fpath,
                    $local_event,
                    FILE_APPEND
                );
            }
        }

        if (SucuriScanOption::is_enabled(':api_service')) {
            SucuriScanAPI::sendLogsFromQueue();

            return SucuriScanAPI::sendLog($event_message);
        }

        return true;
    }

    /**
     * Reports a debug event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_debug_event($message = '', $internal = false)
    {
        return self::report_event(0, 'core', $message, $internal);
    }

    /**
     * Reports a notice event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_notice_event($message = '', $internal = false)
    {
        return self::report_event(1, 'core', $message, $internal);
    }

    /**
     * Reports a info event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_info_event($message = '', $internal = false)
    {
        return self::report_event(2, 'core', $message, $internal);
    }

    /**
     * Reports a warning event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_warning_event($message = '', $internal = false)
    {
        return self::report_event(3, 'core', $message, $internal);
    }

    /**
     * Reports a error event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_error_event($message = '', $internal = false)
    {
        return self::report_event(4, 'core', $message, $internal);
    }

    /**
     * Reports a critical event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_critical_event($message = '', $internal = false)
    {
        return self::report_event(5, 'core', $message, $internal);
    }

    /**
     * Reports a notice or error event for enable and disable actions.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  string  $action   An optional text, hopefully either enabled or disabled.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_auto_event($message = '', $action = '', $internal = false)
    {
        $message = strip_tags($message);

        // Auto-detect the action performed, either enabled or disabled.
        if (preg_match('/( was )?(enabled|disabled)$/', $message, $match)) {
            $action = $match[2];
        }

        // Report the correct event for the action performed.
        if ($action == 'enabled') {
            return self::report_notice_event($message, $internal);
        } elseif ($action == 'disabled') {
            return self::report_error_event($message, $internal);
        } else {
            return self::report_info_event($message, $internal);
        }
    }

    /**
     * Reports an esception on the code.
     *
     * @param  Exception $exception A valid exception object of any type.
     * @return boolean              Whether the report was filled correctly or not.
     */
    public static function report_exception($exception = false)
    {
        if ($exception) {
            $e_trace = $exception->getTrace();
            $multiple_entries = array();

            foreach ($e_trace as $e_child) {
                $e_file = array_key_exists('file', $e_child)
                    ? basename($e_child['file'])
                    : '[internal function]';
                $e_line = array_key_exists('line', $e_child)
                    ? basename($e_child['line'])
                    : '0';
                $e_function = array_key_exists('class', $e_child)
                    ? $e_child['class'] . $e_child['type'] . $e_child['function']
                    : $e_child['function'];
                $multiple_entries[] = sprintf(
                    '%s(%s): %s',
                    $e_file,
                    $e_line,
                    $e_function
                );
            }

            $report_message = sprintf(
                '%s: (multiple entries): %s',
                $exception->getMessage(),
                @implode(',', $multiple_entries)
            );

            return self::report_debug_event($report_message);
        }

        return false;
    }

    /**
     * Send a notification to the administrator of the specified events, only if
     * the administrator accepted to receive alerts for this type of events.
     *
     * @param  string $event   The name of the event that was triggered.
     * @param  string $content Body of the email that will be sent to the administrator.
     * @return void
     */
    public static function notify_event($event = '', $content = '')
    {
        $notify = SucuriScanOption::get_option(':notify_' . $event);
        $email = SucuriScanOption::get_option(':notify_to');
        $email_params = array();

        if (self::is_trusted_ip()) {
            $notify = 'disabled';
        }

        if ($notify == 'enabled') {
            if ($event == 'post_publication') {
                $event = 'post_update';
            } elseif ($event == 'failed_login') {
                $settings_url = SucuriScanTemplate::getUrl('settings');
                $content .= "<br>\n<br>\n<em>Explanation: Someone failed to login to your "
                    . "site. If you are getting too many of these messages, it is likely your "
                    . "site is under a password guessing brute-force attack [1]. You can disable "
                    . "the failed login alerts from here [2]. Alternatively, you can consider "
                    . "to install a firewall between your website and your visitors to filter "
                    . "out these and other attacks, take a look at Sucuri CloudProxy [3].</em>"
                    . "<br>\n<br>\n"
                    . "[1] <a href='https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing'>"
                    . "https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing</a><br>\n"
                    . "[2] <a href='" . $settings_url . "'>" . $settings_url . "</a> <br>\n"
                    . "[3] <a href='https://sucuri.net/website-firewall/?wpalert'>"
                    . "https://sucuri.net/website-firewall/</a> <br>\n";
            } elseif ($event == 'bruteforce_attack') {
                // Send a notification even if the limit of emails per hour was reached.
                $email_params['Force'] = true;
            } elseif ($event == 'scan_checksums') {
                $event = 'core_integrity_checks';
                $email_params['Force'] = true;
            }

            $title = str_replace('_', "\x20", $event);
            $mail_sent = SucuriScanMail::send_mail(
                $email,
                $title,
                $content,
                $email_params
            );

            return $mail_sent;
        }

        return false;
    }

    /**
     * Check whether an IP address is being trusted or not.
     *
     * @param  string  $remote_addr The supposed ip address that will be checked.
     * @return boolean              TRUE if the IP address of the user is trusted, FALSE otherwise.
     */
    private static function is_trusted_ip($remote_addr = '')
    {
        $cache = new SucuriScanCache('trustip', false);
        $trusted_ips = $cache->getAll();

        if (!$remote_addr) {
            $remote_addr = SucuriScan::get_remote_addr();
        }

        $addr_md5 = md5($remote_addr);

        // Check if the CIDR in range 32 of this IP is trusted.
        if (is_array($trusted_ips)
            && !empty($trusted_ips)
            && array_key_exists($addr_md5, $trusted_ips)
        ) {
            return true;
        }

        if ($trusted_ips) {
            foreach ($trusted_ips as $cache_key => $ip_info) {
                $ip_parts = explode('.', $ip_info->remote_addr);
                $ip_pattern = false;

                // Generate the regular expression for a specific CIDR range.
                switch ($ip_info->cidr_range) {
                    case 24:
                        $ip_pattern = sprintf('/^%d\.%d\.%d\.[0-9]{1,3}$/', $ip_parts[0], $ip_parts[1], $ip_parts[2]);
                        break;
                    case 16:
                        $ip_pattern = sprintf('/^%d\.%d(\.[0-9]{1,3}) {2}$/', $ip_parts[0], $ip_parts[1]);
                        break;
                    case 8:
                        $ip_pattern = sprintf('/^%d(\.[0-9]{1,3}) {3}$/', $ip_parts[0]);
                        break;
                }

                if ($ip_pattern && preg_match($ip_pattern, $remote_addr)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generate and set a new password for a specific user not in session.
     *
     * @param  integer $user_id The user identifier that will be changed, this must be different than the user in session.
     * @return boolean          Either TRUE or FALSE in case of success or error respectively.
     */
    public static function set_new_password($user_id = 0)
    {
        $user_id = intval($user_id);

        if ($user_id > 0 && function_exists('wp_generate_password')) {
            $user = get_userdata($user_id);

            if ($user instanceof WP_User) {
                $website = SucuriScan::get_domain();
                $user_login = $user->user_login;
                $display_name = $user->display_name;
                $new_password = wp_generate_password(15, true, false);

                $message = SucuriScanTemplate::getSection('notification-resetpwd', array(
                    'ResetPassword.UserName' => $user_login,
                    'ResetPassword.DisplayName' => $display_name,
                    'ResetPassword.Password' => $new_password,
                    'ResetPassword.Website' => $website,
                ));

                $data_set = array( 'Force' => true ); // Skip limit for emails per hour.
                SucuriScanMail::send_mail($user->user_email, 'Password changed', $message, $data_set);

                wp_set_password($new_password, $user_id);

                return true;
            }
        }

        return false;
    }

    /**
     * Modify the WordPress configuration file and change the keys that were defined
     * by a new random-generated list of keys retrieved from the official WordPress
     * API. The result of the operation will be either FALSE in case of error, or an
     * array containing multiple indexes explaining the modification, among them you
     * will find the old and new keys.
     *
     * @return false|array Either FALSE in case of error, or an array with the old and new keys.
     */
    public static function set_new_config_keys()
    {
        $new_wpconfig = '';
        $config_path = self::get_wpconfig_path();

        if ($config_path) {
            $pattern = self::secret_key_pattern();
            $define_tpl = "define('%s',%s'%s');";
            $config_lines = SucuriScanFileInfo::file_lines($config_path);
            $new_keys = SucuriScanAPI::getNewSecretKeys();
            $old_keys = array();
            $old_keys_string = '';
            $new_keys_string = '';

            foreach ((array) $config_lines as $config_line) {
                if (preg_match($pattern, $config_line, $match)) {
                    $key_name = $match[1];

                    if (array_key_exists($key_name, $new_keys)) {
                        $white_spaces = $match[2];
                        $old_keys[ $key_name ] = $match[3];
                        $config_line = sprintf($define_tpl, $key_name, $white_spaces, $new_keys[ $key_name ]);
                        $old_keys_string .= sprintf($define_tpl, $key_name, $white_spaces, $old_keys[ $key_name ]) . "\n";
                        $new_keys_string .= $config_line . "\n";
                    }
                }

                $new_wpconfig .= $config_line . "\n";
            }

            $response = array(
                'updated' => is_writable($config_path),
                'old_keys' => $old_keys,
                'old_keys_string' => $old_keys_string,
                'new_keys' => $new_keys,
                'new_keys_string' => $new_keys_string,
                'new_wpconfig' => $new_wpconfig,
            );

            if ($response['updated']) {
                file_put_contents($config_path, $new_wpconfig, LOCK_EX);
            }

            return $response;
        }

        return false;
    }
}

/**
 * Function call interceptors.
 *
 * The term hooking covers a range of techniques used to alter or augment the
 * behavior of an operating system, of applications, or of other software
 * components by intercepting function calls or messages or events passed
 * between software components. Code that handles such intercepted function
 * calls, events or messages is called a "hook".
 *
 * Hooking is used for many purposes, including debugging and extending
 * functionality. Examples might include intercepting keyboard or mouse event
 * messages before they reach an application, or intercepting operating system
 * calls in order to monitor behavior or modify the function of an application
 * or other component; it is also widely used in benchmarking programs.
 */
class SucuriScanHook extends SucuriScanEvent
{

    /**
     * Send to Sucuri servers an alert notifying that an attachment was added to a post.
     *
     * @param  integer $id The post identifier.
     * @return void
     */
    public static function hook_add_attachment($id = 0)
    {
        if ($data = get_post($id)) {
            $id = $data->ID;
            $title = $data->post_title;
            $mime_type = $data->post_mime_type;
        } else {
            $title = 'unknown';
            $mime_type = 'unknown';
        }

        $message = sprintf('Media file added; identifier: %s; name: %s; type: %s', $id, $title, $mime_type);
        self::report_notice_event($message);
        self::notify_event('post_publication', $message);
    }

    /**
     * Send an alert notifying that a new link was added to the bookmarks.
     *
     * @param  integer $id Identifier of the new link created;
     * @return void
     */
    public static function hook_add_link($id = 0)
    {
        if ($data = get_bookmark($id)) {
            $id = $data->link_id;
            $title = $data->link_name;
            $url = $data->link_url;
            $target = $data->link_target;
        } else {
            $title = 'unknown';
            $url = 'undefined/url';
            $target = '_none';
        }

        $message = sprintf(
            'Bookmark link added; identifier: %s; name: %s; url: %s; target: %s',
            $id,
            $title,
            $url,
            $target
        );
        self::report_warning_event($message);
        self::notify_event('post_publication', $message);
    }

    /**
     * Send an alert notifying that a category was created.
     *
     * @param  integer $id The identifier of the category created.
     * @return void
     */
    public static function hook_create_category($id = 0)
    {
        $title = ( is_int($id) ? get_cat_name($id) : 'Unknown' );

        $message = sprintf('Category created; identifier: %s; name: %s', $id, $title);
        self::report_notice_event($message);
        self::notify_event('post_publication', $message);
    }

    /**
     * Send an alert notifying that a post was deleted.
     *
     * @param  integer $id The identifier of the post deleted.
     * @return void
     */
    public static function hook_delete_post($id = 0)
    {
        self::report_warning_event('Post deleted; identifier: ' . $id);
    }

    /**
     * Send an alert notifying that a post was moved to the trash.
     *
     * @param  integer $id The identifier of the trashed post.
     * @return void
     */
    public static function hook_wp_trash_post($id = 0)
    {
        if ($data = get_post($id)) {
            $title = $data->post_title;
            $status = $data->post_status;
        } else {
            $title = 'Unknown';
            $status = 'none';
        }

        $message = sprintf(
            'Post moved to trash; identifier: %s; name: %s; status: %s',
            $id,
            $title,
            $status
        );
        self::report_warning_event($message);
    }

    /**
     * Send an alert notifying that a user account was deleted.
     *
     * @param  integer $id The identifier of the user account deleted.
     * @return void
     */
    public static function hook_delete_user($id = 0)
    {
        self::report_warning_event('User account deleted; identifier: ' . $id);
    }

    /**
     * Send an alert notifying that an attempt to reset the password
     * of an user account was executed.
     *
     * @return void
     */
    public static function hook_login_form_resetpass()
    {
        // Detecting WordPress 2.8.3 vulnerability - $key is array.
        if (isset($_GET['key']) && is_array($_GET['key'])) {
            self::report_critical_event('Attempt to reset password by attacking WP/2.8.3 bug');
        }
    }

    /**
     * Send an alert notifying that the state of a post was changed
     * from private to published. This will only applies for posts not pages.
     *
     * @param  integer $id The identifier of the post changed.
     * @return void
     */
    public static function hook_private_to_published($id = 0)
    {
        if ($data = get_post($id)) {
            $title = $data->post_title;
            $p_type = ucwords($data->post_type);
        } else {
            $title = 'Unknown';
            $p_type = 'Publication';
        }

        // Check whether the post-type is being ignored to send notifications.
        if (!SucuriScanOption::is_ignored_event($p_type)) {
            $message = sprintf(
                '%s (private to published); identifier: %s; name: %s',
                $p_type,
                $id,
                $title
            );
            self::report_notice_event($message);
            self::notify_event('post_publication', $message);
        }
    }

    /**
     * Send an alert notifying that a post was published.
     *
     * @param  integer $id The identifier of the post or page published.
     * @return void
     */
    public static function hook_publish($id = 0)
    {
        if ($data = get_post($id)) {
            $title = $data->post_title;
            $p_type = ucwords($data->post_type);
            $action = ( $data->post_date == $data->post_modified ? 'created' : 'updated' );
        } else {
            $title = 'Unknown';
            $p_type = 'Publication';
            $action = 'published';
        }

        $message = sprintf(
            '%s was %s; identifier: %s; name: %s',
            $p_type,
            $action,
            $id,
            $title
        );
        self::report_notice_event($message);
        self::notify_event('post_publication', $message);
    }

    /**
     * Alias function for hook_publish()
     *
     * @param  integer $id The identifier of the post or page published.
     * @return void
     */
    public static function hook_publish_page($id = 0)
    {
        self::hook_publish($id);
    }

    /**
     * Alias function for hook_publish()
     *
     * @param  integer $id The identifier of the post or page published.
     * @return void
     */
    public static function hook_publish_post($id = 0)
    {
        self::hook_publish($id);
    }

    /**
     * Alias function for hook_publish()
     *
     * @param  integer $id The identifier of the post or page published.
     * @return void
     */
    public static function hook_publish_phone($id = 0)
    {
        self::hook_publish($id);
    }

    /**
     * Alias function for hook_publish()
     *
     * @param  integer $id The identifier of the post or page published.
     * @return void
     */
    public static function hook_xmlrpc_publish_post($id = 0)
    {
        self::hook_publish($id);
    }

    /**
     * Send an alert notifying that an attempt to retrieve the password
     * of an user account was tried.
     *
     * @param  string $title The name of the user account involved in the trasaction.
     * @return void
     */
    public static function hook_retrieve_password($title = '')
    {
        if (empty($title)) {
            $title = 'unknown';
        }

        self::report_error_event('Password retrieval attempt: ' . $title);
    }

    /**
     * Send an alert notifying that the theme of the site was changed.
     *
     * @param  string $title The name of the new theme selected to used through out the site.
     * @return void
     */
    public static function hook_switch_theme($title = '')
    {
        if (empty($title)) {
            $title = 'unknown';
        }

        $message = 'Theme activated: ' . $title;
        self::report_warning_event($message);
        self::notify_event('theme_activated', $message);
    }

    /**
     * Send an alert notifying that a new user account was created.
     *
     * @param  integer $id The identifier of the new user account created.
     * @return void
     */
    public static function hook_user_register($id = 0)
    {
        if ($data = get_userdata($id)) {
            $title = $data->user_login;
            $email = $data->user_email;
            $roles = @implode(', ', $data->roles);
        } else {
            $title = 'unknown';
            $email = 'user@domain.com';
            $roles = 'none';
        }

        $message = sprintf(
            'User account created; identifier: %s; name: %s; email: %s; roles: %s',
            $id,
            $title,
            $email,
            $roles
        );
        self::report_warning_event($message);
        self::notify_event('user_registration', $message);
    }

    /**
     * Send an alert notifying that an attempt to login into the
     * administration panel was successful.
     *
     * @param  string $title The name of the user account involved in the transaction.
     * @return void
     */
    public static function hook_wp_login($title = '')
    {
        if (empty($title)) {
            $title = 'Unknown';
        }

        $message = 'User authentication succeeded: ' . $title;
        self::report_notice_event($message);
        self::notify_event('success_login', $message);
    }

    /**
     * Send an alert notifying that an attempt to login into the
     * administration panel failed.
     *
     * @param  string $title The name of the user account involved in the transaction.
     * @return void
     */
    public static function hook_wp_login_failed($title = '')
    {
        if (empty($title)) {
            $title = 'Unknown';
        }

        $title = sanitize_user($title, true);
        $password = SucuriScanRequest::post('pwd');
        $message = 'User authentication failed: ' . $title;

        self::report_error_event($message);

        if (sucuriscan_collect_wrong_passwords() === true) {
            $message .= "<br>\nUser wrong password: " . $password;
        }

        self::notify_event('failed_login', $message);

        // Log the failed login in the internal datastore for future reports.
        $logged = sucuriscan_log_failed_login($title, $password);

        // Check if the quantity of failed logins will be considered as a brute-force attack.
        if ($logged) {
            $failed_logins = sucuriscan_get_failed_logins();

            if ($failed_logins) {
                $max_time = 3600;
                $maximum_failed_logins = SucuriScanOption::get_option('sucuriscan_maximum_failed_logins');

                /**
                 * If the time passed is within the hour, and the quantity of failed logins
                 * registered in the datastore file is bigger than the maximum quantity of
                 * failed logins allowed per hour (value configured by the administrator in the
                 * settings page), then send an email notification reporting the event and
                 * specifying that it may be a brute-force attack against the login page.
                 */
                if ($failed_logins['diff_time'] <= $max_time
                    && $failed_logins['count'] >= $maximum_failed_logins
                ) {
                    sucuriscan_report_failed_logins($failed_logins);
                } /**
                 * If there time passed is superior to the hour, then reset the content of the
                 * datastore file containing the failed logins so far, any entry in that file
                 * will not be considered as part of a brute-force attack (if it exists) because
                 * the time passed between the first and last login attempt is big enough to
                 * mitigate the attack. We will consider the current failed login event as the
                 * first entry of that file in case of future attempts during the next sixty
                 * minutes.
                 */
                elseif ($failed_logins['diff_time'] > $max_time) {
                    sucuriscan_reset_failed_logins();
                    sucuriscan_log_failed_login($title);
                }
            }
        }
    }

    /**
     * Fires immediately after a comment is inserted into the database.
     *
     * The action comment-post can also be used to track the insertion of data in
     * the comments table, but this only returns the identifier of the new entry in
     * the database and the status (approved, not approved, spam). The WP-Insert-
     * Comment action returns the same identifier and additionally the full data set
     * with the comment information.
     *
     * @see https://codex.wordpress.org/Plugin_API/Action_Reference/wp_insert_comment
     * @see https://codex.wordpress.org/Plugin_API/Action_Reference/comment_post
     *
     * @param  integer $id      The comment identifier.
     * @param  object  $comment The comment object.
     * @return void
     */
    public static function hook_wp_insert_comment($id = 0, $comment = false)
    {
        if ($comment instanceof stdClass
            && property_exists($comment, 'comment_ID')
            && property_exists($comment, 'comment_agent')
            && property_exists($comment, 'comment_author_IP')
            && SucuriScanOption::is_enabled(':comment_monitor')
        ) {
            $data_set = array(
                'id' => $comment->comment_ID,
                'post_id' => $comment->comment_post_ID,
                'user_id' => $comment->user_id,
                'parent' => $comment->comment_parent,
                'approved' => $comment->comment_approved,
                'remote_addr' => $comment->comment_author_IP,
                'author_email' => $comment->comment_author_email,
                'date' => $comment->comment_date,
                'content' => $comment->comment_content,
                'user_agent' => $comment->comment_agent,
            );
            $message = base64_encode(json_encode($data_set));
            self::report_notice_event('Base64:' . $message, true);
        }
    }

    // TODO: Log when the comment status is modified: wp_set_comment_status
    // TODO: Log when the comment data is modified: edit_comment
    // TODO: Log when the comment is going to be deleted: delete_comment, trash_comment
    // TODO: Log when the comment is finally deleted: deleted_comment, trashed_comment
    // TODO: Log when the comment is closed: comment_closed
    // TODO: Detect auto updates in core, themes, and plugin files.

    /**
     * Placeholder for arbitrary actions.
     *
     * @return void
     */
    public static function hook_all($action = null, $data = false)
    {
        global $wp_filter, $wp_actions;

        if (is_array($wp_filter)
            && is_array($wp_actions)
            && array_key_exists($action, $wp_actions)
            && !array_key_exists($action, $wp_filter)
            && (
                substr($action, 0, 11) === 'admin_post_'
                || substr($action, 0, 8) === 'wp_ajax_'
            )
        ) {
            $message = sprintf('Undefined XHR action %s', $action);
            self::report_error_event($message);
            header('HTTP/1.1 400 Bad Request');
            exit(1);
        }
    }

    /**
     * Send a notifications to the administrator of some specific events that are
     * not triggered through an hooked action, but through a simple request in the
     * admin interface.
     *
     * @return integer Either one or zero representing the success or fail of the operation.
     */
    public static function hook_undefined_actions()
    {

        $plugin_activate_actions = '(activate|deactivate)(\-selected)?';
        $plugin_update_actions = '(upgrade-plugin|do-plugin-upgrade|update-selected)';

        // Plugin activation and/or deactivation.
        if (current_user_can('activate_plugins')
            && (
                SucuriScanRequest::get_or_post('action', $plugin_activate_actions)
                || SucuriScanRequest::get_or_post('action2', $plugin_activate_actions)
            )
        ) {
            $plugin_list = array();
            $items_affected = array();

            // Get the action performed through action or action2 params.
            $action_d = SucuriScanRequest::get_or_post('action');
            if ($action_d == '-1') {
                $action_d = SucuriScanRequest::get_or_post('action2');
            }
            $action_d .= 'd';

            if (SucuriScanRequest::get('plugin', '.+')
                && strpos($_SERVER['REQUEST_URI'], 'plugins.php') !== false
            ) {
                $plugin_list[] = SucuriScanRequest::get('plugin');
            } elseif (isset($_POST['checked'])
                && is_array($_POST['checked'])
                && !empty($_POST['checked'])
            ) {
                $plugin_list = SucuriScanRequest::post('checked', '_array');
                $action_d = str_replace('-selected', '', $action_d);
            }

            foreach ($plugin_list as $plugin) {
                $plugin_info = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

                if (!empty($plugin_info['Name'])
                    && !empty($plugin_info['Version'])
                ) {
                    $items_affected[] = sprintf(
                        '%s (v%s; %s)',
                        self::escape($plugin_info['Name']),
                        self::escape($plugin_info['Version']),
                        self::escape($plugin)
                    );
                }
            }

            // Report activated/deactivated plugins at once.
            if (!empty($items_affected)) {
                $message_tpl = ( count($items_affected) > 1 )
                    ? 'Plugins %s: (multiple entries): %s'
                    : 'Plugin %s: %s';
                $message = sprintf(
                    $message_tpl,
                    $action_d,
                    @implode(',', $items_affected)
                );
                self::report_warning_event($message);
                self::notify_event('plugin_' . $action_d, $message);
            }
        } // Plugin update request.
        elseif (current_user_can('update_plugins')
            && (
                SucuriScanRequest::get_or_post('action', $plugin_update_actions)
                || SucuriScanRequest::get_or_post('action2', $plugin_update_actions)
            )
        ) {
            $plugin_list = array();
            $items_affected = array();

            if (SucuriScanRequest::get('plugin', '.+')
                && strpos($_SERVER['REQUEST_URI'], 'wp-admin/update.php') !== false
            ) {
                $plugin_list[] = SucuriScanRequest::get('plugin', '.+');
            } elseif (isset($_POST['checked'])
                && is_array($_POST['checked'])
                && !empty($_POST['checked'])
            ) {
                $plugin_list = SucuriScanRequest::post('checked', '_array');
            }

            foreach ($plugin_list as $plugin) {
                $plugin_info = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

                if (!empty($plugin_info['Name'])
                    && !empty($plugin_info['Version'])
                ) {
                    $items_affected[] = sprintf(
                        '%s (v%s; %s)',
                        self::escape($plugin_info['Name']),
                        self::escape($plugin_info['Version']),
                        self::escape($plugin)
                    );
                }
            }

            // Report updated plugins at once.
            if (!empty($items_affected)) {
                $message_tpl = ( count($items_affected) > 1 )
                    ? 'Plugins updated: (multiple entries): %s'
                    : 'Plugin updated: %s';
                $message = sprintf(
                    $message_tpl,
                    @implode(',', $items_affected)
                );
                self::report_warning_event($message);
                self::notify_event('plugin_updated', $message);
            }
        } // Plugin installation request.
        elseif (current_user_can('install_plugins')
            && SucuriScanRequest::get('action', '(install|upload)-plugin')
        ) {
            if (isset($_FILES['pluginzip'])) {
                $plugin = self::escape($_FILES['pluginzip']['name']);
            } else {
                $plugin = SucuriScanRequest::get('plugin', '.+');

                if (!$plugin) {
                    $plugin = 'Unknown';
                }
            }

            $message = 'Plugin installed: ' . self::escape($plugin);
            SucuriScanEvent::report_warning_event($message);
            self::notify_event('plugin_installed', $message);
        } // Plugin deletion request.
        elseif (current_user_can('delete_plugins')
            && SucuriScanRequest::post('action', 'delete-selected')
            && SucuriScanRequest::post('verify-delete', '1')
        ) {
            $plugin_list = SucuriScanRequest::post('checked', '_array');
            $items_affected = array();

            foreach ((array) $plugin_list as $plugin) {
                $plugin_info = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

                if (!empty($plugin_info['Name'])
                    && !empty($plugin_info['Version'])
                ) {
                    $items_affected[] = sprintf(
                        '%s (v%s; %s)',
                        self::escape($plugin_info['Name']),
                        self::escape($plugin_info['Version']),
                        self::escape($plugin)
                    );
                }
            }

            // Report deleted plugins at once.
            if (!empty($items_affected)) {
                $message_tpl = ( count($items_affected) > 1 )
                    ? 'Plugins deleted: (multiple entries): %s'
                    : 'Plugin deleted: %s';
                $message = sprintf(
                    $message_tpl,
                    @implode(',', $items_affected)
                );
                self::report_warning_event($message);
                self::notify_event('plugin_deleted', $message);
            }
        } // Plugin editor request.
        elseif (current_user_can('edit_plugins')
            && SucuriScanRequest::post('action', 'update')
            && SucuriScanRequest::post('plugin', '.+')
            && SucuriScanRequest::post('file', '.+')
            && strpos($_SERVER['REQUEST_URI'], 'plugin-editor.php') !== false
        ) {
            $filename = SucuriScanRequest::post('file');
            $message = 'Plugin editor used in: ' . SucuriScan::escape($filename);
            self::report_error_event($message);
            self::notify_event('theme_editor', $message);
        } // Theme editor request.
        elseif (current_user_can('edit_themes')
            && SucuriScanRequest::post('action', 'update')
            && SucuriScanRequest::post('theme', '.+')
            && SucuriScanRequest::post('file', '.+')
            && strpos($_SERVER['REQUEST_URI'], 'theme-editor.php') !== false
        ) {
            $theme_name = SucuriScanRequest::post('theme');
            $filename = SucuriScanRequest::post('file');
            $message = 'Theme editor used in: ' . SucuriScan::escape($theme_name) . '/' . SucuriScan::escape($filename);
            self::report_error_event($message);
            self::notify_event('theme_editor', $message);
        } // Theme installation request.
        elseif (current_user_can('install_themes')
            && SucuriScanRequest::get('action', 'install-theme')
        ) {
            $theme = SucuriScanRequest::get('theme', '.+');

            if (!$theme) {
                $theme = 'Unknown';
            }

            $message = 'Theme installed: ' . self::escape($theme);
            SucuriScanEvent::report_warning_event($message);
            self::notify_event('theme_installed', $message);
        } // Theme deletion request.
        elseif (current_user_can('delete_themes')
            && SucuriScanRequest::get_or_post('action', 'delete')
            && SucuriScanRequest::get_or_post('stylesheet', '.+')
        ) {
            $theme = SucuriScanRequest::get('stylesheet', '.+');

            if (!$theme) {
                $theme = 'Unknown';
            }

            $message = 'Theme deleted: ' . self::escape($theme);
            SucuriScanEvent::report_warning_event($message);
            self::notify_event('theme_deleted', $message);
        } // Theme update request.
        elseif (current_user_can('update_themes')
            && SucuriScanRequest::get('action', '(upgrade-theme|do-theme-upgrade)')
            && SucuriScanRequest::post('checked', '_array')
        ) {
            $themes = SucuriScanRequest::post('checked', '_array');
            $items_affected = array();

            foreach ((array) $themes as $theme) {
                $theme_info = wp_get_theme($theme);
                $theme_name = ucwords($theme);
                $theme_version = '0.0';

                if ($theme_info->exists()) {
                    $theme_name = $theme_info->get('Name');
                    $theme_version = $theme_info->get('Version');
                }

                $items_affected[] = sprintf(
                    '%s (v%s; %s)',
                    self::escape($theme_name),
                    self::escape($theme_version),
                    self::escape($theme)
                );
            }

            // Report updated themes at once.
            if (!empty($items_affected)) {
                $message_tpl = ( count($items_affected) > 1 )
                    ? 'Themes updated: (multiple entries): %s'
                    : 'Theme updated: %s';
                $message = sprintf(
                    $message_tpl,
                    @implode(',', $items_affected)
                );
                self::report_warning_event($message);
                self::notify_event('theme_updated', $message);
            }
        } // WordPress update request.
        elseif (current_user_can('update_core')
            && SucuriScanRequest::get('action', '(do-core-upgrade|do-core-reinstall)')
            && SucuriScanRequest::post('upgrade')
        ) {
            $message = 'WordPress updated to version: ' . SucuriScanRequest::post('version');
            self::report_critical_event($message);
            self::notify_event('website_updated', $message);
        } // Widget addition or deletion.
        elseif (current_user_can('edit_theme_options')
            && SucuriScanRequest::post('action', 'save-widget')
            && SucuriScanRequest::post('id_base') !== false
            && SucuriScanRequest::post('sidebar') !== false
        ) {
            if (SucuriScanRequest::post('delete_widget', '1')) {
                $action_d = 'deleted';
                $action_text = 'deleted from';
            } else {
                $action_d = 'added';
                $action_text = 'added to';
            }

            $message = sprintf(
                'Widget %s (%s) %s %s (#%d; size %dx%d)',
                SucuriScanRequest::post('id_base'),
                SucuriScanRequest::post('widget-id'),
                $action_text,
                SucuriScanRequest::post('sidebar'),
                SucuriScanRequest::post('widget_number'),
                SucuriScanRequest::post('widget-width'),
                SucuriScanRequest::post('widget-height')
            );

            self::report_warning_event($message);
            self::notify_event('widget_' . $action_d, $message);
        } // Detect any Wordpress settings modification.
        elseif (current_user_can('manage_options')
            && SucuriScanOption::check_options_nonce()
        ) {
            // Get the settings available in the database and compare them with the submission.
            $options_changed = SucuriScanOption::what_options_were_changed($_POST);
            $options_changed_str = '';
            $options_changed_simple = '';
            $options_changed_count = 0;

            // Generate the list of options changed.
            foreach ($options_changed['original'] as $option_name => $option_value) {
                $options_changed_count += 1;
                $options_changed_str .= sprintf(
                    "The value of the option <b>%s</b> was changed from <b>'%s'</b> to <b>'%s'</b>.<br>\n",
                    self::escape($option_name),
                    self::escape($option_value),
                    self::escape($options_changed['changed'][ $option_name ])
                );
                $options_changed_simple .= sprintf(
                    "%s: from '%s' to '%s',",
                    self::escape($option_name),
                    self::escape($option_value),
                    self::escape($options_changed['changed'][ $option_name ])
                );
            }

            // Get the option group (name of the page where the request was originated).
            $option_page = isset($_POST['option_page']) ? $_POST['option_page'] : 'options';
            $page_referer = false;

            // Check which of these option groups where modified.
            switch ($option_page) {
                case 'options':
                    $page_referer = 'Global';
                    break;
                case 'general':    /* no_break */
                case 'writing':    /* no_break */
                case 'reading':    /* no_break */
                case 'discussion': /* no_break */
                case 'media':      /* no_break */
                case 'permalink':
                    $page_referer = ucwords($option_page);
                    break;
                default:
                    $page_referer = 'Common';
                    break;
            }

            if ($page_referer && $options_changed_count > 0) {
                $message = $page_referer . ' settings changed';
                SucuriScanEvent::report_error_event(sprintf(
                    '%s: (multiple entries): %s',
                    $message,
                    rtrim($options_changed_simple, ',')
                ));
                self::notify_event('settings_updated', $message . "<br>\n" . $options_changed_str);
            }
        }

    }
}

/**
 * Plugin API library.
 *
 * When used in the context of web development, an API is typically defined as a
 * set of Hypertext Transfer Protocol (HTTP) request messages, along with a
 * definition of the structure of response messages, which is usually in an
 * Extensible Markup Language (XML) or JavaScript Object Notation (JSON) format.
 * While "web API" historically has been virtually synonymous for web service,
 * the recent trend (so-called Web 2.0) has been moving away from Simple Object
 * Access Protocol (SOAP) based web services and service-oriented architecture
 * (SOA) towards more direct representational state transfer (REST) style web
 * resources and resource-oriented architecture (ROA). Part of this trend is
 * related to the Semantic Web movement toward Resource Description Framework
 * (RDF), a concept to promote web-based ontology engineering technologies. Web
 * APIs allow the combination of multiple APIs into new applications known as
 * mashups.
 *
 * @see https://en.wikipedia.org/wiki/Application_programming_interface#Web_APIs
 */
class SucuriScanAPI extends SucuriScanOption
{
    /**
     * Check whether the SSL certificates will be verified while executing a HTTP
     * request or not. This is only for customization of the administrator, in fact
     * not verifying the SSL certificates can lead to a "Man in the Middle" attack.
     *
     * @return boolean Whether the SSL certs will be verified while sending a request.
     */
    public static function verifySslCert()
    {
        return (self::get_option(':verify_ssl_cert') === 'true');
    }

    /**
     * Seconds before consider a HTTP request as timeout.
     *
     * As for the 01/Jan/2016 if the number of seconds before a timeout is greater
     * than sixty (which is one minute) the function will reset the option to its
     * default value to keep the latency of the HTTP requests in a minimum to
     * minimize the interruptions in the admins workflow. The normal connection
     * timeout should be in the range of ten seconds, or fifteen if the DNS lookups
     * are slow.
     *
     * @return integer Seconds to consider a HTTP request timeout.
     */
    public static function requestTimeout()
    {
        $timeout = (int) self::get_option(':request_timeout');

        if ($timeout > SUCURISCAN_MAX_REQUEST_TIMEOUT) {
            self::delete_option(':request_timeout');

            return self::requestTimeout();
        }

        return $timeout;
    }

    /**
     * Generate an user-agent for the HTTP requests.
     *
     * @return string An user-agent for the HTTP requests.
     */
    private static function userAgent()
    {
        return sprintf(
            'WordPress/%s; %s',
            self::site_version(),
            self::get_domain()
        );
    }

    /**
     * Retrieves a URL using a changeable HTTP method, returning results in an
     * array. Results include HTTP headers and content.
     *
     * @see https://codex.wordpress.org/Function_Reference/wp_remote_post
     * @see https://codex.wordpress.org/Function_Reference/wp_remote_get
     *
     * @param  string $url    The target URL where the request will be sent.
     * @param  string $method HTTP method that will be used to send the request.
     * @param  array  $params Parameters for the request defined in an associative array of key-value.
     * @param  array  $args   Request arguments like the timeout, redirections, headers, cookies, etc.
     * @return array          Response object after the HTTP request is executed.
     */
    private static function apiCall($url = '', $method = 'GET', $params = array(), $args = array())
    {
        if (!$url) {
            return false;
        }

        $req_args = array(
            'method' => $method,
            'timeout' => self::requestTimeout(),
            'redirection' => 2,
            'httpversion' => '1.0',
            'user-agent' => self::userAgent(),
            'blocking' => true,
            'headers' => array(),
            'cookies' => array(),
            'compress' => false,
            'decompress' => false,
            'sslverify' => self::verifySslCert(),
        );

        // Update the request arguments with the values passed tot he function.
        foreach ($args as $arg_name => $arg_value) {
            if (array_key_exists($arg_name, $req_args)) {
                $req_args[$arg_name] = $arg_value;
            }
        }

        // Add random request parameter to avoid request reset.
        if (!empty($params) && !array_key_exists('time', $params)) {
            $params['time'] = time();
        }

        if ($method == 'GET') {
            if (!empty($params)) {
                $url = sprintf('%s?%s', $url, http_build_query($params));
            }

            $response = wp_remote_get($url, $req_args);
        } elseif ($method == 'POST') {
            $req_args['body'] = $params;
            $response = wp_remote_post($url, $req_args);
        } else {
            $response = false;
            SucuriScanInterface::error('HTTP method not allowed: ' . $method);
        }

        return self::processResponse($response, $params, $args);
    }

    /**
     * Check whether the plugin API key is valid or not.
     *
     * @param  string  $api_key An unique string to identify this installation.
     * @return boolean          True if the API key is valid, false otherwise.
     */
    private static function isValidKey($api_key = '')
    {
        return (bool) @preg_match('/^[a-z0-9]{32}$/', $api_key);
    }

    /**
     * Store the API key locally.
     *
     * @param  string  $api_key  An unique string of characters to identify this installation.
     * @param  boolean $validate Whether the format of the key should be validated before store it.
     * @return boolean           Either true or false if the key was saved successfully or not respectively.
     */
    public static function setPluginKey($api_key = '', $validate = false)
    {
        if ($validate) {
            if (!self::isValidKey($api_key)) {
                SucuriScanInterface::error('Invalid API key format');
                return false;
            }
        }

        if (!empty($api_key)) {
            SucuriScanEvent::notify_event('plugin_change', 'API key updated successfully: ' . $api_key);
        }

        return self::update_option(':api_key', $api_key);
    }

    /**
     * Retrieve the API key from the local storage.
     *
     * @return string|boolean The API key or false if it does not exists.
     */
    public static function getPluginKey()
    {
        $api_key = self::get_option(':api_key');

        if (is_string($api_key)
            && self::isValidKey($api_key)
        ) {
            return $api_key;
        }

        return false;
    }

    /**
     * Check and return the API key for the plugin.
     *
     * In this plugin the key is a pair of two strings concatenated by a single
     * slash, the first part of it is in fact the key and the second part is the
     * unique identifier of the site in the remote server.
     *
     * @return array|boolean false if the key is invalid or not present, an array otherwise.
     */
    public static function getCloudproxyKey()
    {
        $option_name = ':cloudproxy_apikey';
        $api_key = self::get_option($option_name);

        // Check if the cloudproxy-waf plugin was previously installed.
        if (!$api_key) {
            $api_key = self::get_option('sucuriwaf_apikey');

            if ($api_key) {
                self::update_option($option_name, $api_key);
                self::delete_option('sucuriwaf_apikey');
            }
        }

        // Check the validity of the API key.
        $match = self::isValidCloudproxyKey($api_key, true);

        if ($match) {
            return array(
                'string' => $match[1].'/'.$match[2],
                'k' => $match[1],
                's' => $match[2],
            );
        }

        return false;
    }

    /**
     * Check whether the CloudProxy API key is valid or not.
     *
     * @param  string  $api_key      The CloudProxy API key.
     * @param  boolean $return_match Whether the parts of the API key must be returned or not.
     * @return boolean               true if the API key specified is valid, false otherwise.
     */
    public static function isValidCloudproxyKey($api_key = '', $return_match = false)
    {
        $pattern = '/^([a-z0-9]{32})\/([a-z0-9]{32})$/';

        if ($api_key && preg_match($pattern, $api_key, $match)) {
            if ($return_match) {
                return $match;
            }

            return true;
        }

        return false;
    }

    /**
     * Call an action from the remote API interface of our WordPress service.
     *
     * @param  string  $method       HTTP method that will be used to send the request.
     * @param  array   $params       Parameters for the request defined in an associative array of key-value.
     * @param  boolean $send_api_key Whether the API key should be added to the request parameters or not.
     * @param  array   $args         Request arguments like the timeout, redirections, headers, cookies, etc.
     * @return array                 Response object after the HTTP request is executed.
     */
    public static function apiCallWordpress($method = 'GET', $params = array(), $send_api_key = true, $args = array())
    {
        $url = SUCURISCAN_API;
        $params[ SUCURISCAN_API_VERSION ] = 1;
        $params['p'] = 'wordpress';

        if ($send_api_key) {
            $api_key = self::getPluginKey();

            if (!$api_key) {
                return false;
            }

            $params['k'] = $api_key;
        }

        return self::apiCall($url, $method, $params, $args);
    }

    /**
     * Call an action from the remote API interface of our CloudProxy service.
     *
     * @param  string $method HTTP method that will be used to send the request.
     * @param  array  $params Parameters for the request defined in an associative array of key-value.
     * @return array          Response object after the HTTP request is executed.
     */
    public static function apiCallCloudproxy($method = 'GET', $params = array())
    {
        $send_request = false;

        if (isset($params['k']) && isset($params['s'])) {
            $send_request = true;
        } else {
            $api_key = self::getCloudproxyKey();

            if ($api_key) {
                $send_request = true;
                $params['k'] = $api_key['k'];
                $params['s'] = $api_key['s'];
            }
        }

        if ($send_request) {
            $url = SUCURISCAN_CLOUDPROXY_API;
            $params[ SUCURISCAN_CLOUDPROXY_API_VERSION ] = 1;
            unset($params['string']);

            return self::apiCall($url, $method, $params);
        }

        return false;
    }

    /**
     * Execute some actions according to the response message.
     *
     * @param  array $response Response object after the HTTP request is executed.
     * @param  array $params   Parameters for the request defined in an associative array of key-value.
     * @param  array $args     Request arguments like the timeout, redirections, headers, cookies, etc.
     * @return array           Response object with some modifications.
     */
    private static function processResponse($response = array(), $params = array(), $args = array())
    {
        /**
         * Convert the error message generated by the code base functions after the HTTP
         * request is executed to a valid response object that will allow this code
         * process the data according to the specified standards.
         */
        if (is_wp_error($response)) {
            // Extract information from the error object.
            $error_message = $response->get_error_message();
            $request_action = isset($params['a']) ? $params['a'] : 'unknown';

            // Build a fake request response with custom data.
            $data_set = array(
                'status' => 0,
                'action' => $request_action,
                'messages' => array( $error_message ),
                'request_time' => SucuriScan::local_time(),
                'output' => new stdClass(),
                'verbose' => 0,
            );

            // Build the response object and encode data.
            $response = array();
            $response['body'] = json_encode($data_set);
            $response['headers']['date'] = date('r');
            $response['headers']['connection'] = 'close';
            $response['headers']['content-type'] = 'application/json';
            $response['headers']['content-length'] = strlen($response['body']);
            $response['response']['code'] = 500;
            $response['response']['message'] = 'ERROR';
        }

        /**
         * Process the response object.
         *
         * Some response messages and even errors require extra steps of processing to,
         * for example, try to fix automatically issues related with disconnections,
         * timeouts, SSL certificate verifications, etc. Some of these actions can not
         * be fixed if the server where the website is being hosted has a special
         * configuration, which then requires the human interaction of the admin user,
         * they will see extra information explaining the response and how to proceed
         * with it.
         */
        if (is_array($response)
            && array_key_exists('body', $response)
            && array_key_exists('headers', $response)
            && array_key_exists('response', $response)
        ) {
            // Keep a copy of the raw HTTP response.
            $response['body_raw'] = $response['body'];

            // Append the non-private HTTP request parameters.
            $response['params'] = $params;
            unset($response['params']['k']);

            /**
             * Check and decode the API response.
             *
             * Note that serialized data is going to be ignored, the old API service used to
             * respond to all endpoints with serialized data and considering the risk that
             * it poses to unserialize in PHP it was decided to drop that option and stick
             * to JSON which is a bit safer.
             */
            if (isset($response['headers']['content-type'])
                && $response['headers']['content-type'] == 'application/json'
            ) {
                $assoc = (isset($args['assoc']) && $args['assoc'] === true) ? true : false;
                $response['body'] = @json_decode($response['body_raw'], $assoc);
                $response['body_arr'] = @json_decode($response['body_raw'], true);
            } elseif (self::is_serialized($response['body'])) {
                $response['body_raw'] = null;
                $response['body'] = 'ERROR:Serialized data is not supported.';
            }

            return $response;
        }

        return false;
    }

    /**
     * Determine whether an API response was successful or not checking the expected
     * generic variables and types, in case of an error a notification will appears
     * in the administrator panel explaining the result of the operation.
     *
     * @param  array   $response HTTP response after API endpoint execution.
     * @param  boolean $enqueue  Add the log to the local queue on a failure.
     * @return boolean           False if the API call failed, true otherwise.
     */
    private static function handleResponse($response = array(), $enqueue = true)
    {
        $error_msg = '';

        if ($response) {
            if ($response['body'] instanceof stdClass) {
                if (isset($response['body']->status)) {
                    if ($response['body']->status == 1) {
                        return true;
                    } else {
                        return self::handleErrorResponse($response, $enqueue);
                    }
                } else {
                    $error_msg = 'Could not determine the status of an API call.';
                }
            } else {
                $message = 'non JSON-encoded response.';

                if (isset($response['response'])
                    && isset($response['response']['message'])
                    && isset($response['response']['code'])
                    && $response['response']['code'] !== 200
                ) {
                    $message = sprintf(
                        '(%s) %s',
                        $response['response']['code'],
                        $response['response']['message']
                    );
                }

                $error_msg = 'Malformed API response: ' . $message;
            }
        }

        if (!empty($error_msg) && $enqueue) {
            SucuriScanInterface::error($error_msg);
        }

        return false;
    }

    /**
     * Process failures in the HTTP response.
     *
     * Log file not found: means that the API key used to execute the request is
     * not associated to the website, this may indicate that either the key was
     * invalidated by an administrator of the service or that the API key was
     * custom generated with invalid data.
     *
     * Wrong API key: means that the TLD of the origin of the request is not the
     * domain used to generate the API key in the first place, or that the email
     * address of the site administrator was changed so the data is not valid
     * anymore.
     *
     * Connection timeout: means that the API service is down either because the
     * hosting provider has connectivity issues or because the code is being
     * deployed. There is an option in the settings page that allows to temporarily
     * disable the communication with the API service while the server is down, this
     * allows the admins to keep the latency at zero and continue working in their
     * websites without interruptions.
     *
     * SSL issues: depending on the options used to compile the OpenSSL library
     * built by each hosting provider, the connection with the HTTPs version of the
     * API service may be rejected because of a failure in the SSL algorithm check.
     * There is an option in the settings page that allows to disable the SSL pair
     * verification, this option it disable automatically when the error is detected
     * for the first time.
     *
     * @param  array   $response HTTP response after API endpoint execution.
     * @param  boolean $enqueue  Add the log to the local queue on a failure.
     * @return boolean           False if the API call failed, true otherwise.
     */
    private static function handleErrorResponse($response = array(), $enqueue = true)
    {
        $action_message = 'Unknown error, there is no more information.';

        // Check whether the message list is empty or not.
        if (isset($response['body']->messages[0])) {
            $action_message = $response['body']->messages[0] . '.';
        }

        // Keep a copy of the original API response message.
        $raw_message = $action_message;

        // Special response for invalid API keys.
        if (stripos($raw_message, 'log file not found') !== false) {
            SucuriScanOption::delete_option(':api_key');

            $action_message .= ' This generally happens when you add an invalid API key, the'
                . ' key will be deleted automatically to hide these warnings, if you want to'
                . ' recover it go to the settings page and use the recover button to send the'
                . ' key to your email address.';
        }

        // Special response for invalid CloudProxy API keys.
        if (stripos($raw_message, 'wrong api key') !== false) {
            SucuriScanOption::delete_option(':cloudproxy_apikey');
            SucuriScanOption::setRevProxy('disable');
            SucuriScanOption::setAddrHeader('REMOTE_ADDR');

            $action_message .= ' The CloudProxy API key does not seems to be valid.';
        }

        // Special response for connection timeouts.
        if ($enqueue && @preg_match('/time(d\s)?out/', $raw_message)) {
            $action_message = ''; /* Empty the error message. */
            $cache = new SucuriScanCache('auditqueue');
            $cache_key = md5($response['params']['time']);
            $cache_value = array(
                'created_at' => $response['params']['time'],
                'message' => $response['params']['m'],
            );
            $cache->add($cache_key, $cache_value);
        }

        // Stop SSL peer verification on connection failures.
        if (stripos($raw_message, 'no alternative certificate')
            || stripos($raw_message, 'error setting certificate')
            || stripos($raw_message, 'SSL connect error')
        ) {
            SucuriScanOption::update_option(':verify_ssl_cert', 'false');

            $action_message .= 'There were some issues with the SSL certificate either in this'
                . ' server or with the remote API service. The automatic verification of the'
                . ' certificates has been deactivated to reduce the noise during the execution'
                . ' of the HTTP requests.';
        }

        if (!empty($action_message)) {
            if ($enqueue) {
                SucuriScanInterface::error(
                    sprintf(
                        '(%d) %s: %s',
                        SucuriScan::local_time(),
                        ucwords($response['body']->action),
                        $action_message
                    )
                );
            }

            return false;
        }

        return true;
    }

    /**
     * Send a request to the API to register this site.
     *
     * @param  string  $email Optional email address for the registration.
     * @return boolean        True if the API key was generated, false otherwise.
     */
    public static function registerSite($email = '')
    {
        if (!is_string($email) || empty($email)) {
            $email = self::get_site_email();
        }

        $response = self::apiCallWordpress('POST', array(
            'e' => $email,
            's' => self::get_domain(),
            'a' => 'register_site',
        ), false);

        if (self::handleResponse($response)) {
            self::setPluginKey($response['body']->output->api_key);
            SucuriScanEvent::schedule_task();
            SucuriScanEvent::notify_event('plugin_change', 'Site registered and API key generated');
            SucuriScanInterface::info('The API key for your site was successfully generated and saved.');

            return true;
        }

        return false;
    }

    /**
     * Send a request to recover a previously registered API key.
     *
     * @return boolean true if the API key was sent to the administrator email, false otherwise.
     */
    public static function recoverKey()
    {
        $clean_domain = self::get_domain();

        $response = self::apiCallWordpress('GET', array(
            'e' => self::get_site_email(),
            's' => $clean_domain,
            'a' => 'recover_key',
        ), false);

        if (self::handleResponse($response)) {
            SucuriScanEvent::notify_event('plugin_change', 'API key recovered for domain: ' . $clean_domain);
            SucuriScanInterface::info($response['body']->output->message);

            return true;
        }

        return false;
    }

    /**
     * Send a request to the API to store and analyze the events of the site. An
     * event can be anything from a simple request, an internal modification of the
     * settings or files in the administrator panel, or a notification generated by
     * this plugin.
     *
     * @param  string  $event   Event triggered by the core system functions.
     * @param  integer $time    Timestamp when the event was originally triggered.
     * @param  boolean $enqueue Add the log to the local queue on a failure.
     * @return boolean          True if the event was logged, false otherwise.
     */
    public static function sendLog($event = '', $time = 0, $enqueue = true)
    {
        if (!empty($event)) {
            $params = array();
            $params['a'] = 'send_log';
            $params['m'] = $event;

            if (intval($time) > 0) {
                $params['time'] = (int) $time;
            }

            $response = self::apiCallWordpress('POST', $params, true);

            if (self::handleResponse($response, $enqueue)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send all logs from the queue.
     *
     * Retry the HTTP calls for the logs that were not sent to the API service
     * because of a connection failure or misconfiguration. Each successful call
     * will remove the log from the queue and the failures will keep them until the
     * next function call is executed.
     *
     * @return void
     */
    public static function sendLogsFromQueue()
    {
        $cache = new SucuriScanCache('auditqueue');
        $entries = $cache->getAll();

        if (is_array($entries) && !empty($entries)) {
            foreach ($entries as $key => $entry) {
                $result = self::sendLog(
                    $entry->message,
                    $entry->created_at,
                    false
                );

                if ($result === true) {
                    $cache->delete($key);
                } else {
                    /**
                     * Stop loop on failures.
                     *
                     * If the log was successfully sent to the API service then we can continue
                     * sending the other logs in the queue, otherwise the operation must be stopped
                     * so it can be executed next time when the service is online, not stopping the
                     * operation when one or more of the API calls fails will cause a very long
                     * delay in the load of the page that is being requested.
                     */
                    break;
                }
            }
        }
    }

    /**
     * Retrieve all the event logs registered by the API service.
     *
     * @return array The object with the data returned from the API service.
     */
    public static function getAllLogs()
    {
        // Get the total number of lines in the logs.
        $response = self::apiCallWordpress('GET', array(
            'a' => 'get_logs',
            'l' => 0,
        ));

        // If success continue with the retrieval of the logs data.
        if (self::handleResponse($response)) {
            return self::getLogs($response['body']->total_entries);
        }

        return false;
    }

    /**
     * Retrieve the event logs registered by the API service.
     *
     * @param  integer $lines How many lines from the log file will be retrieved.
     * @return string         The response of the API service.
     */
    public static function getLogs($lines = 50)
    {
        $response = self::apiCallWordpress('GET', array(
            'a' => 'get_logs',
            'l' => $lines,
        ));

        if (self::handleResponse($response)) {
            $response['body']->output_data = array();
            $log_pattern = '/^([0-9\-]+) ([0-9:]+) (\S+) : (.+)/';
            $extra_pattern = '/(.+ \(multiple entries\):) (.+)/';
            $generic_pattern = '/^@?([A-Z][a-z]{3,7}): ([^;]+; )?(.+)/';
            $auth_pattern = '/^User authentication (succeeded|failed): ([^<;]+)/';

            foreach ($response['body']->output as $log) {
                if (@preg_match($log_pattern, $log, $log_match)) {
                    $log_data = array(
                        'event' => 'notice',
                        'date' => '',
                        'time' => '',
                        'datetime' => '',
                        'timestamp' => 0,
                        'account' => $log_match[3],
                        'username' => 'system',
                        'remote_addr' => '127.0.0.1',
                        'message' => $log_match[4],
                        'file_list' => false,
                        'file_list_count' => 0,
                    );

                    // Extract and fix the date and time using the Eastern time zone.
                    $datetime = sprintf('%s %s EDT', $log_match[1], $log_match[2]);
                    $log_data['timestamp'] = strtotime($datetime);
                    $log_data['datetime'] = date('Y-m-d H:i:s', $log_data['timestamp']);
                    $log_data['date'] = date('Y-m-d', $log_data['timestamp']);
                    $log_data['time'] = date('H:i:s', $log_data['timestamp']);

                    // Extract more information from the generic audit logs.
                    $log_data['message'] = str_replace('<br>', '; ', $log_data['message']);

                    if (@preg_match($generic_pattern, $log_data['message'], $log_extra)) {
                        $log_data['event'] = strtolower($log_extra[1]);
                        $log_data['message'] = trim($log_extra[3]);

                        // Extract the username and remote address from the log.
                        if (!empty($log_extra[2])) {
                            $username_address = rtrim($log_extra[2], ";\x20");

                            // Separate the username from the remote address.
                            if (strpos($username_address, ",\x20") !== false) {
                                $usip_parts = explode(",\x20", $username_address, 2);

                                if (count($usip_parts) == 2) {
                                    // Separate the username from the display name.
                                    $log_data['username'] = @preg_replace('/^.+ \((.+)\)$/', '$1', $usip_parts[0]);
                                    $log_data['remote_addr'] = $usip_parts[1];
                                }
                            } else {
                                $log_data['remote_addr'] = $username_address;
                            }
                        }

                        // Fix old user authentication logs for backward compatibility.
                        $log_data['message'] = str_replace(
                            'logged in',
                            'authentication succeeded',
                            $log_data['message']
                        );

                        if (@preg_match($auth_pattern, $log_data['message'], $user_match)) {
                            $log_data['username'] = $user_match[2];
                        }
                    }

                    // Extract more information from the special formatted logs.
                    if (@preg_match($extra_pattern, $log_data['message'], $log_extra)) {
                        $log_data['message'] = $log_extra[1];
                        $log_extra[2] = str_replace(', new size', '; new size', $log_extra[2]);
                        $log_extra[2] = str_replace(",\x20", ";\x20", $log_extra[2]);
                        $log_data['file_list'] = explode(',', $log_extra[2]);
                        $log_data['file_list_count'] = count($log_data['file_list']);
                    }

                    $response['body']->output_data[] = $log_data;
                }
            }

            return $response['body'];
        }

        return false;
    }

    /**
     * Get a list of valid audit event types with their respective colors.
     *
     * @return array Valid audit event types with their colors.
     */
    public static function getAuditEventTypes()
    {
        return array(
            'critical' => '#000000',
            'debug' => '#c690ec',
            'error' => '#f27d7d',
            'info' => '#5bc0de',
            'notice' => '#428bca',
            'warning' => '#f0ad4e',
        );
    }

    /**
     * Parse the event logs with multiple entries.
     *
     * @param  string $event_log Event log that will be processed.
     * @return array             List of parts of the event log.
     */
    public static function parseMultipleEntries($event_log = '')
    {
        if (@preg_match('/^(.*:\s)\(multiple entries\):\s(.+)/', $event_log, $match)) {
            $event_log = array();
            $event_log[] = trim($match[1]);
            $grouped_items = @explode(',', $match[2]);
            $event_log = array_merge($event_log, $grouped_items);
        }

        return $event_log;
    }

    /**
     * Collect the information for the audit log report.
     *
     * @param  integer $lines How many lines from the log file will be retrieved.
     * @return array          All the information necessary to display the audit logs report.
     */
    public static function getAuditReport($lines = 50)
    {
        $audit_logs = self::getLogs($lines);

        if ($audit_logs instanceof stdClass
            && property_exists($audit_logs, 'total_entries')
            && property_exists($audit_logs, 'output_data')
            && !empty($audit_logs->output_data)
        ) {
            // Data structure that will be returned.
            $report = array(
                'total_events' => 0,
                'start_timestamp' => 0,
                'end_timestamp' => 0,
                'event_colors' => array(),
                'events_per_type' => array(),
                'events_per_user' => array(),
                'events_per_ipaddress' => array(),
                'events_per_login' => array(
                    'successful' => 0,
                    'failed' => 0,
                ),
            );

            // Get a list of valid audit event types.
            $event_types = self::getAuditEventTypes();
            foreach ($event_types as $event => $event_color) {
                $report['events_per_type'][$event] = 0;
                $report['event_colors'][] = sprintf("'%s'", $event_color);
            }

            // Collect information for each report chart.
            foreach ($audit_logs->output_data as $event) {
                $report['total_events'] += 1;

                // Increment the number of events for this event type.
                if (array_key_exists($event['event'], $report['events_per_type'])) {
                    $report['events_per_type'][ $event['event'] ] += 1;
                } else {
                    $report['events_per_type'][ $event['event'] ] = 1;
                }

                // Find the lowest datetime among the filtered events.
                if ($event['timestamp'] <= $report['start_timestamp']
                    || $report['start_timestamp'] === 0
                ) {
                    $report['start_timestamp'] = $event['timestamp'];
                }

                // Find the highest datetime among the filtered events.
                if ($event['timestamp'] >= $report['end_timestamp']) {
                    $report['end_timestamp'] = $event['timestamp'];
                }

                // Increment the number of events generated by this user account.
                $_username = SucuriScan::escape($event['username']);
                if (array_key_exists($_username, $report['events_per_user'])) {
                    $report['events_per_user'][$_username] += 1;
                } else {
                    $report['events_per_user'][$_username] = 1;
                }

                // Increment the number of events generated from this remote address.
                $_remote_addr = SucuriScan::escape($event['remote_addr']);
                if (array_key_exists($_remote_addr, $report['events_per_ipaddress'])) {
                    $report['events_per_ipaddress'][$_remote_addr] += 1;
                } else {
                    $report['events_per_ipaddress'][$_remote_addr] = 1;
                }

                // Detect successful and failed user authentications.
                $auth_pattern = '/^User authentication (succeeded|failed):/';

                if (@preg_match($auth_pattern, $event['message'], $match)) {
                    if ($match[1] == 'succeeded') {
                        $report['events_per_login']['successful'] += 1;
                    } else {
                        $report['events_per_login']['failed'] += 1;
                    }
                } elseif (@preg_match('/^User logged in:/', $event['message'])) {
                    // Backward compatibility for previous user login messages.
                    $report['events_per_login']['successful'] += 1;
                }
            }

            if ($report['total_events'] > 0) {
                return $report;
            }
        }

        return false;
    }

    /**
     * Send a request to the API to store and analyze the file's hashes of the site.
     * This will be the core of the monitoring tools and will enhance the
     * information of the audit logs alerting the administrator of suspicious
     * changes in the system.
     *
     * @param  string  $hashes The information gathered after the scanning of the site's files.
     * @return boolean         true if the hashes were stored, false otherwise.
     */
    public static function sendHashes($hashes = '')
    {
        if (!empty($hashes)) {
            $response = self::apiCallWordpress('POST', array(
                'a' => 'send_hashes',
                'h' => $hashes,
            ));

            if (self::handleResponse($response)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve the public settings of the account associated with the API keys
     * registered by the administrator of the site. This function will send a HTTP
     * request to the remote API service and process its response, when successful
     * it will return an array/object containing the public attributes of the site.
     *
     * @param  boolean $api_key The CloudProxy API key.
     * @return array            A hash with the settings of a CloudProxy account.
     */
    public static function getCloudproxySettings($api_key = false)
    {
        $params = array('a' => 'show_settings');

        if ($api_key) {
            $params = array_merge($params, $api_key);
        }

        $response = self::apiCallCloudproxy('GET', $params);

        if (self::handleResponse($response)) {
            return $response['body']->output;
        }

        return false;
    }

    /**
     * Flush the cache of the site(s) associated with the API key.
     *
     * @param  boolean $api_key The CloudProxy API key.
     * @return string           Message explaining the result of the operation.
     */
    public static function clearCloudproxyCache($api_key = false)
    {
        $params = array( 'a' => 'clear_cache' );

        if ($api_key) {
            $params = array_merge($params, $api_key);
        }

        $response = self::apiCallCloudproxy('GET', $params);

        if (self::handleResponse($response)) {
            return $response['body'];
        }

        return false;
    }

    /**
     * Retrieve the audit logs of the account associated with the API keys
     * registered b the administrator of the site. This function will send a HTTP
     * request to the remote API service and process its response, when successful
     * it will return an array/object containing a list of requests blocked by our
     * CloudProxy.
     *
     * By default the logs that will be retrieved are from today, if you need to see
     * the logs of previous days you will need to add a new parameter to the request
     * URL named "date" with format yyyy-mm-dd.
     *
     * @param  string  $api_key The CloudProxy API key.
     * @param  string  $date    Retrieve the data from this date.
     * @param  string  $query   Filter the data to match this query.
     * @param  integer $limit   Retrieve this maximum of data.
     * @param  integer $offset  Retrieve the data from this point.
     * @return array            Objects with details of each blocked request.
     */
    public static function firewallAuditLogs($api_key, $date = '', $query = '', $limit = 10, $offset = 0)
    {
        $params = array(
            'a' => 'audit_trails',
            'date' => $date,
            'query' => $query,
            'limit' => $limit,
            'offset' => $offset,
        );

        if (is_array($api_key) && !empty($api_key)) {
            $params = array_merge($params, $api_key);
        }

        $response = self::apiCallCloudproxy('GET', $params);

        if (self::handleResponse($response)) {
            return $response['body_arr']['output'];
        }

        return false;
    }

    /**
     * Scan a website through the public SiteCheck API [1] for known malware,
     * blacklisting status, website errors, and out-of-date software.
     *
     * [1] https://sitecheck.sucuri.net/
     *
     * @param  string $domain The clean version of the website's domain.
     * @return object         Serialized data of the scanning results for the site specified.
     */
    public static function getSitecheckResults($domain = '')
    {
        if (!empty($domain)) {
            $url = 'https://sitecheck.sucuri.net/';
            $response = self::apiCall(
                $url,
                'GET',
                array(
                    'scan' => $domain,
                    'fromwp' => 2,
                    'clear' => 1,
                    'json' => 1,
                ),
                array(
                    'assoc' => true,
                )
            );

            if ($response) {
                return $response['body'];
            }
        }

        return false;
    }

    /**
     * Extract detailed information from a SiteCheck malware payload.
     *
     * @param  array $malware Array with two entries with basic malware information.
     * @return array          Detailed information of the malware found by SiteCheck.
     */
    public static function getSitecheckMalware($malware = array())
    {
        if (count($malware) >= 2) {
            $data_set = array(
                'alert_message' => '',
                'infected_url' => '',
                'malware_type' => '',
                'malware_docs' => '',
                'malware_payload' => '',
            );

            // Extract the information from the alert message.
            $alert_parts = explode(':', $malware[0], 2);

            if (isset($alert_parts[1])) {
                $data_set['alert_message'] = $alert_parts[0];
                $data_set['infected_url'] = $alert_parts[1];
            }

            // Extract the information from the malware message.
            $malware_parts = explode("\n", $malware[1]);

            if (isset($malware_parts[1])) {
                if (@preg_match('/(.+)\. Details: (.+)/', $malware_parts[0], $match)) {
                    $data_set['malware_type'] = $match[1];
                    $data_set['malware_docs'] = $match[2];
                }

                $payload = trim($malware_parts[1]);
                $payload = html_entity_decode($payload);

                if (@preg_match('/<div id=\'HiddenDiv\'>(.+)<\/div>/', $payload, $match)) {
                    $data_set['malware_payload'] = trim($match[1]);
                }
            }

            return $data_set;
        }

        return false;
    }

    /**
     * Retrieve a new set of keys for the WordPress configuration file using the
     * official API provided by WordPress itself.
     *
     * @return array A list of the new set of keys generated by WordPress API.
     */
    public static function getNewSecretKeys()
    {
        $pattern = self::secret_key_pattern();
        $response = self::apiCall('https://api.wordpress.org/secret-key/1.1/salt/', 'GET');

        if ($response && @preg_match_all($pattern, $response['body'], $match)) {
            $new_keys = array();

            foreach ($match[1] as $i => $value) {
                $new_keys[$value] = $match[3][$i];
            }

            return $new_keys;
        }

        return false;
    }

    /**
     * Retrieve a list with the checksums of the files in a specific version of WordPress.
     *
     * @see Release Archive https://wordpress.org/download/release-archive/
     *
     * @param  integer $version Valid version number of the WordPress project.
     * @return object           Associative object with the relative filepath and the checksums of the project files.
     */
    public static function getOfficialChecksums($version = 0)
    {
        $url = 'https://api.wordpress.org/core/checksums/1.0/';
        $language = 'en_US'; /* WPLANG does not works. */
        $response = self::apiCall($url, 'GET', array(
            'version' => $version,
            'locale' => $language,
        ));

        if ($response) {
            if ($response['body'] instanceof stdClass) {
                $json_data = $response['body'];
            } else {
                $json_data = @json_decode($response['body']);
            }

            if (isset($json_data->checksums)
                && !empty($json_data->checksums)
            ) {
                if (count((array) $json_data->checksums) <= 1
                    && property_exists($json_data->checksums, $version)
                ) {
                    $checksums = $json_data->checksums->{$version};
                } else {
                    $checksums = $json_data->checksums;
                }

                // Check whether the list of file is an object.
                if ($checksums instanceof stdClass) {
                    return (array) $checksums;
                }
            }
        }

        return false;
    }

    /**
     * Check the plugins directory and retrieve all plugin files with plugin data.
     * This function will also retrieve the URL and name of the repository/page
     * where it is being published at the WordPress plugins market.
     *
     * @return array Key is the plugin file path and the value is an array of the plugin data.
     */
    public static function getPlugins()
    {
        // Check if the cache library was loaded.
        $can_cache = class_exists('SucuriScanCache');

        if ($can_cache) {
            $cache = new SucuriScanCache('plugindata');
            $cached_data = $cache->get('plugins', SUCURISCAN_GET_PLUGINS_LIFETIME, 'array');

            // Return the previously cached results of this function.
            if ($cached_data !== false) {
                return $cached_data;
            }
        }

        // Get the plugin's basic information from WordPress transient data.
        $plugins = get_plugins();
        $pattern = '/^http(s)?:\/\/wordpress\.org\/plugins\/(.*)\/$/';
        $wp_market = 'https://wordpress.org/plugins/%s/';

        // Loop through each plugin data and complement its information with more attributes.
        foreach ($plugins as $plugin_path => $plugin_data) {
            // Default values for the plugin extra attributes.
            $repository = '';
            $repository_name = '';
            $is_free_plugin = false;

            /**
             * Extract the information of the plugin which includes the repository name,
             * repository URL, and if the source code of the plugin is publicly released or
             * not, in this last case if the source code of the plugin is not hosted in the
             * official WordPress server it means that it is premium and is being
             * distributed by an independent developer.
             */
            if (isset($plugin_data['PluginURI'])
                && preg_match($pattern, $plugin_data['PluginURI'], $match)
            ) {
                $repository = $match[0];
                $repository_name = $match[2];
                $is_free_plugin = true;
            } else {
                if (strpos($plugin_path, '/') !== false) {
                    $plugin_path_parts = explode('/', $plugin_path, 2);
                } else {
                    $plugin_path_parts = explode('.', $plugin_path, 2);
                }

                if (isset($plugin_path_parts[0])) {
                    $possible_repository = sprintf($wp_market, $plugin_path_parts[0]);
                    $resp = wp_remote_head($possible_repository);

                    if (!is_wp_error($resp)
                        && $resp['response']['code'] == 200
                    ) {
                        $repository = $possible_repository;
                        $repository_name = $plugin_path_parts[0];
                        $is_free_plugin = true;
                    }
                }
            }

            // Complement the plugin's information with these attributes.
            $plugins[$plugin_path]['Repository'] = $repository;
            $plugins[$plugin_path]['RepositoryName'] = $repository_name;
            $plugins[$plugin_path]['InstallationPath'] = sprintf('%s/%s', WP_PLUGIN_DIR, $repository_name);
            $plugins[$plugin_path]['IsFreePlugin'] = $is_free_plugin;
            $plugins[$plugin_path]['PluginType'] = ( $is_free_plugin ? 'free' : 'premium' );
            $plugins[$plugin_path]['IsPluginActive'] = false;
            $plugins[$plugin_path]['IsPluginInstalled'] = false;

            if (is_plugin_active($plugin_path)) {
                $plugins[$plugin_path]['IsPluginActive'] = true;
            }

            if (is_dir($plugins[$plugin_path]['InstallationPath'])) {
                $plugins[$plugin_path]['IsPluginInstalled'] = true;
            }
        }

        if ($can_cache) {
            // Add the information of the plugins to the file-based cache.
            $cache->add('plugins', $plugins);
        }

        return $plugins;
    }

    /**
     * Retrieve plugin installer pages from WordPress Plugins API.
     *
     * It is possible for a plugin to override the Plugin API result with three
     * filters. Assume this is for plugins, which can extend on the Plugin Info to
     * offer more choices. This is very powerful and must be used with care, when
     * overriding the filters.
     *
     * The first filter, 'plugins_api_args', is for the args and gives the action as
     * the second parameter. The hook for 'plugins_api_args' must ensure that an
     * object is returned.
     *
     * The second filter, 'plugins_api', is the result that would be returned.
     *
     * @param  string $plugin Frienly name of the plugin.
     * @return object         Object on success, WP_Error on failure.
     */
    public static function getRemotePluginData($plugin = '')
    {
        if (!empty($plugin)) {
            $url = sprintf('https://api.wordpress.org/plugins/info/1.0/%s.json', $plugin);
            $response = self::apiCall($url, 'GET');

            if ($response) {
                if ($response['body'] instanceof stdClass) {
                    return $response['body'];
                }
            }
        }

        return false;
    }

    /**
     * Retrieve a specific file from the official WordPress subversion repository,
     * the content of the file is determined by the tags defined using the site
     * version specified. Only official core files are allowed to fetch.
     *
     * @see https://core.svn.wordpress.org/
     * @see https://i18n.svn.wordpress.org/
     * @see https://core.svn.wordpress.org/tags/VERSION_NUMBER/
     *
     * @param  string $filepath Relative file path of a project core file.
     * @param  string $version  Optional site version, default will be the global version number.
     * @return string           Full content of the official file retrieved, false if the file was not found.
     */
    public static function getOriginalCoreFile($filepath = '', $version = 0)
    {
        if (!empty($filepath)) {
            if ($version == 0) {
                $version = self::site_version();
            }

            $url = sprintf('https://core.svn.wordpress.org/tags/%s/%s', $version, $filepath);
            $response = self::apiCall($url, 'GET');

            if ($response) {
                if (isset($response['headers']['content-length'])
                    && $response['headers']['content-length'] > 0
                    && is_string($response['body'])
                ) {
                    return $response['body'];
                }
            }
        }

        return false;
    }
}

/**
 * Process and send emails.
 *
 * One of the core features of the plugin is the event alerts, a list of rules
 * will check if the site is being compromised, in which case a notification
 * will be sent to the site email address (an address that can be configured in
 * the settings page).
 */
class SucuriScanMail extends SucuriScanOption
{

    /**
     * Check whether the email notifications will be sent in HTML or Plain/Text.
     *
     * @return boolean Whether the emails will be in HTML or Plain/Text.
     */
    public static function prettify_mails()
    {
        return self::is_enabled(':prettify_mails');
    }

    /**
     * Send a message to a specific email address.
     *
     * @param  string  $email    The email address of the recipient that will receive the message.
     * @param  string  $subject  The reason of the message that will be sent.
     * @param  string  $message  Body of the message that will be sent.
     * @param  array   $data_set Optional parameter to add more information to the notification.
     * @return boolean           Whether the email contents were sent successfully.
     */
    public static function send_mail($email = '', $subject = '', $message = '', $data_set = array())
    {
        $headers = array();
        $subject = ucwords(strtolower($subject));
        $force = false;
        $debug = false;

        // Check whether the mail will be printed in the site instead of sent.
        if (isset($data_set['Debug'])
            && $data_set['Debug'] == true
        ) {
            $debug = true;
            unset($data_set['Debug']);
        }

        // Check whether the mail will be even if the limit per hour was reached or not.
        if (isset($data_set['Force'])
            && $data_set['Force'] == true
        ) {
            $force = true;
            unset($data_set['Force']);
        }

        // Check whether the email notifications will be sent in HTML or Plain/Text.
        if (self::prettify_mails()) {
            $headers = array( 'content-type: text/html' );
            $data_set['PrettifyType'] = 'pretty';
        } else {
            $message = strip_tags($message);
        }

        if (!self::emails_per_hour_reached() || $force || $debug) {
            $message = self::prettify_mail($subject, $message, $data_set);

            if ($debug) {
                die($message);
            }

            $subject = self::get_email_subject($subject);

            /**
             * WordPress uses a library named PHPMailer [1] to send emails through the
             * provided function wp_mail [2], unfortunately the debug information is
             * completely removed and this makes it difficult to troubleshoots issues
             * reported by users when the SMTP server in their sites is misconfigured. To
             * reduce the number of tickets related with this issue we will provide an
             * option to allow the users to choose which technique will be used to send the
             * notifications.
             *
             * [1] https://github.com/PHPMailer/PHPMailer
             * [2] https://developer.wordpress.org/reference/functions/wp_mail/
             *
             * @var boolean
             */
            if (SucuriScanOption::is_enabled(':use_wpmail')) {
                $mail_sent = wp_mail($email, $subject, $message, $headers);
            } else {
                $headers = implode("\r\n", $headers);
                $mail_sent = @mail($email, $subject, $message, $headers);
            }

            if ($mail_sent) {
                $emails_sent_num = (int) self::get_option(':emails_sent');
                self::update_option(':emails_sent', $emails_sent_num + 1);
                self::update_option(':last_email_at', time());

                return true;
            }
        }

        return false;
    }

    /**
     * Generate a subject for the email alerts.
     *
     * @param  string $event The reason of the message that will be sent.
     * @return string        A text with the subject for the email alert.
     */
    private static function get_email_subject($event = '')
    {
        $subject = self::get_option(':email_subject');

        /**
         * Probably a bad value in the options table. Delete the entry from the database
         * and call this function to try again, it will probably fall in an infinite
         * loop, but this is the easiest way to control this procedure.
         */
        if (!$subject) {
            self::delete_option(':email_subject');

            return self::get_email_subject($event);
        }

        $subject = strip_tags($subject);
        $subject = str_replace(':event', $event, $subject);
        $subject = str_replace(':domain', self::get_domain(), $subject);
        $subject = str_replace(':remoteaddr', self::get_remote_addr(), $subject);

        /**
         * Extract user data from the current session.
         *
         * Get the data of the user in the current session only if the pseudo-tags for
         * the username and/or email address are necessary to build the email subject,
         * otherwise this operation may delay the sending of the alerts.
         */
        if (preg_match('/:(username|email)/', $subject)) {
            $user = wp_get_current_user();
            $username = 'unknown';
            $eaddress = 'unknown';

            if ($user instanceof WP_User
                && isset($user->user_login)
                && isset($user->user_email)
            ) {
                $username = $user->user_login;
                $eaddress = $user->user_email;
            }

            $subject = str_replace(':username', $user->user_login, $subject);
            $subject = str_replace(':email', $user->user_email, $subject);
        }

        return $subject;
    }

    /**
     * Generate a HTML version of the message that will be sent through an email.
     *
     * @param  string $subject  The reason of the message that will be sent.
     * @param  string $message  Body of the message that will be sent.
     * @param  array  $data_set Optional parameter to add more information to the notification.
     * @return string           The message formatted in a HTML template.
     */
    private static function prettify_mail($subject = '', $message = '', $data_set = array())
    {
        $prettify_type = isset($data_set['PrettifyType']) ? $data_set['PrettifyType'] : 'simple';
        $template_name = 'notification-' . $prettify_type;
        $user = wp_get_current_user();
        $display_name = '';

        if ($user instanceof WP_User
            && isset($user->user_login)
            && !empty($user->user_login)
        ) {
            $display_name = sprintf('User: %s (%s)', $user->display_name, $user->user_login);
        }

        // Format list of items when the event has multiple entries.
        if (strpos($message, 'multiple') !== false) {
            $message_parts = SucuriScanAPI::parseMultipleEntries($message);

            if (is_array($message_parts)) {
                $message = ( $prettify_type == 'pretty' ) ? $message_parts[0] . '<ul>' : $message_parts[0];
                unset($message_parts[0]);

                foreach ($message_parts as $msg_part) {
                    if ($prettify_type == 'pretty') {
                        $message .= sprintf("<li>%s</li>\n", $msg_part);
                    } else {
                        $message .= sprintf("- %s\n", $msg_part);
                    }
                }

                $message .= ( $prettify_type == 'pretty' ) ? '</ul>' : '';
            }
        }

        $mail_variables = array(
            'TemplateTitle' => 'Sucuri Alert',
            'Subject' => $subject,
            'Website' => self::get_option('siteurl'),
            'RemoteAddress' => self::get_remote_addr(),
            'Message' => $message,
            'User' => $display_name,
            'Time' => SucuriScan::current_datetime(),
        );

        foreach ($data_set as $var_key => $var_value) {
            $mail_variables[ $var_key ] = $var_value;
        }

        return SucuriScanTemplate::getSection($template_name, $mail_variables);
    }

    /**
     * Check whether the maximum quantity of emails per hour was reached.
     *
     * @return boolean Whether the quota emails per hour was reached.
     */
    private static function emails_per_hour_reached()
    {
        $max_per_hour = self::get_option(':emails_per_hour');

        if ($max_per_hour != 'unlimited') {
            // Check if we are still in that sixty minutes.
            $current_time = time();
            $last_email_at = self::get_option(':last_email_at');
            $diff_time = abs($current_time - $last_email_at);

            if ($diff_time <= 3600) {
                // Check if the quantity of emails sent is bigger than the configured.
                $emails_sent = (int) self::get_option(':emails_sent');
                $max_per_hour = intval($max_per_hour);

                if ($emails_sent >= $max_per_hour) {
                    return true;
                }
            } else {
                // Reset the counter of emails sent.
                self::update_option(':emails_sent', 0);
            }
        }

        return false;
    }
}

/**
 * Read, parse and handle everything related with the templates.
 *
 * A web template system uses a template processor to combine web templates to
 * form finished web pages, possibly using some data source to customize the
 * pages or present a large amount of content on similar-looking pages. It is a
 * web publishing tool present in content management systems, web application
 * frameworks, and HTML editors.
 *
 * Web templates can be used like the template of a form letter to either
 * generate a large number of "static" (unchanging) web pages in advance, or to
 * produce "dynamic" web pages on demand.
 */
class SucuriScanTemplate extends SucuriScanRequest
{
    /**
     * Replace all pseudo-variables from a string of characters.
     *
     * @param  string $content The content of a template file which contains pseudo-variables.
     * @param  array  $params  List of pseudo-variables that will be replaced in the template.
     * @return string          The content of the template with the pseudo-variables replated.
     */
    private static function replacePseudoVars($content = '', $params = array())
    {
        if (is_array($params)) {
            foreach ($params as $keyname => $kvalue) {
                $tplkey = 'SUCURI.' . $keyname;
                $with_escape = '%%' . $tplkey . '%%';
                $wout_escape = '%%%' . $tplkey . '%%%';

                if (strpos($content, $wout_escape) !== false) {
                    $content = str_replace($wout_escape, $kvalue, $content);
                } elseif (strpos($content, $with_escape) !== false) {
                    $kvalue = SucuriScan::escape($kvalue);
                    $content = str_replace($with_escape, $kvalue, $content);
                }
            }

            return $content;
        }

        return false;
    }

    /**
     * Gather and generate the information required globally by all the template files.
     *
     * @param  array $params Key-value array with pseudo-variables shared with the template.
     * @return array         A complementary list of pseudo-variables for the template files.
     */
    private static function sharedParams($params = array())
    {
        $params = is_array($params) ? $params : array();

        // Base parameters, required to render all the pages.
        $params = self::linksAndNavbar($params);

        // Global parameters, used through out all the pages.
        $params['PageTitle'] = isset($params['PageTitle']) ? '('.$params['PageTitle'].')' : '';
        $params['PageNonce'] = wp_create_nonce('sucuriscan_page_nonce');
        $params['PageStyleClass'] = isset($params['PageStyleClass']) ? $params['PageStyleClass'] : 'base';
        $params['CleanDomain'] = self::get_domain();
        $params['AdminEmails'] = '';

        // Get a list of admin users for the API key generation.
        if (SucuriScanAPI::getPluginKey() === false) {
            $admin_users = SucuriScan::get_users_for_api_key();
            $params['AdminEmails'] = self::selectOptions($admin_users);
        }

        // Hide the advertisements from the layout.
        if (SucuriScanOption::is_disabled(':ads_visibility')) {
            $params['LayoutType'] = 'onecolumn';
            $params['AdsVisibility'] = 'hidden';
            $params['ReviewNavbarButton'] = 'visible';
            $params['PageSidebarContent'] = '';
        } else {
            $params['LayoutType'] = 'twocolumns';
            $params['AdsVisibility'] = 'visible';
            $params['ReviewNavbarButton'] = 'hidden';
            $params['PageSidebarContent'] = self::getTemplate('bsidebar', $params, 'section');
        }

        return $params;
    }

    /**
     * Return a string indicating the visibility of a HTML component.
     *
     * @param  boolean $visible Whether the condition executed returned a positive value or not.
     * @return string           A string indicating the visibility of a HTML component.
     */
    public static function visibility($visible = false)
    {
        return ($visible === true ? 'visible' : 'hidden');
    }

    /**
     * Generate an URL pointing to the page indicated in the function and that must
     * be loaded through the administrator panel.
     *
     * @param  string  $page Short name of the page that will be generated.
     * @param  boolean $ajax True if the URL should point to the Ajax handler.
     * @return string        Full string containing the link of the page.
     */
    public static function getUrl($page = '', $ajax = false)
    {
        $suffix = ($ajax === true) ? 'admin-ajax' : 'admin';
        $url_path = SucuriScan::admin_url($suffix . '.php?page=sucuriscan');

        if (!empty($page)) {
            $url_path .= '_' . strtolower($page);
        }

        return $url_path;
    }

    /**
     * Generate an URL pointing to the page indicated in the function and that must
     * be loaded through the Ajax handler of the administrator panel.
     *
     * @param  string $page Short name of the page that will be generated.
     * @return string       Full string containing the link of the page.
     */
    public static function getAjaxUrl($page = '')
    {
        return self::getUrl($page, true);
    }

    /**
     * Complement the list of pseudo-variables that will be used in the base
     * template files, this will also generate the navigation bar and detect which
     * items in it are selected by the current page.
     *
     * @param  array  $params Key-value array with pseudo-variables shared with the template.
     * @return array          A complementary list of pseudo-variables for the template files.
     */
    private static function linksAndNavbar($params = array())
    {
        global $sucuriscan_pages;

        $params = is_array($params) ? $params : array();
        $sub_pages = is_array($sucuriscan_pages) ? $sucuriscan_pages : array();

        $params['Navbar'] = '';
        $params['CurrentPageFunc'] = '';

        if ($_page = self::get('page', '_page')) {
            $params['CurrentPageFunc'] = $_page;
        }

        foreach ($sub_pages as $sub_page_func => $sub_page_title) {
            if ($sub_page_func == 'sucuriscan_scanner'
                && SucuriScanOption::is_disabled(':sitecheck_scanner')
            ) {
                continue;
            }

            $func_parts = explode('_', $sub_page_func, 2);

            if (isset($func_parts[1])) {
                $unique_name = $func_parts[1];
                $pseudo_var = 'URL.' . ucwords($unique_name);
            } else {
                $unique_name = '';
                $pseudo_var = 'URL.Home';
            }

            $params[$pseudo_var] = self::getUrl($unique_name);

            // Copy URL variable and create an Ajax handler.
            $pseudo_var_ajax = 'Ajax' . $pseudo_var;
            $params[$pseudo_var_ajax] = self::getAjaxUrl($unique_name);

            $navbar_item_css_class = 'nav-tab';

            if ($params['CurrentPageFunc'] == $sub_page_func) {
                $navbar_item_css_class .= "\x20nav-tab-active";
            }

            $params['Navbar'] .= sprintf(
                "<a class='%s' href='%s'>%s</a>\n",
                $navbar_item_css_class,
                $params[$pseudo_var],
                $sub_page_title
            );
        }

        return $params;
    }

    /**
     * Generate a HTML code using a template and replacing all the pseudo-variables
     * by the dynamic variables provided by the developer through one of the parameters
     * of the function.
     *
     * @param  string $html   The HTML content of a template file with its pseudo-variables parsed.
     * @param  array  $params Key-value array with pseudo-variables shared with the template.
     * @return string         The formatted HTML content of the base template.
     */
    public static function getBaseTemplate($html = '', $params = array())
    {
        $params = is_array($params) ? $params : array();

        $params = self::sharedParams($params);
        $params['PageContent'] = $html;

        return self::getTemplate('base', $params);
    }

    /**
     * Generate a HTML code using a template and replacing all the pseudo-variables
     * by the dynamic variables provided by the developer through one of the parameters
     * of the function.
     *
     * @param  string  $template Filename of the template that will be used to generate the page.
     * @param  array   $params   Key-value array with pseudo-variables shared with the template.
     * @param  boolean $type     Template type; either page, section or snippet.
     * @return string            Formatted HTML code after pseudo-variables replacement.
     */
    public static function getTemplate($template = '', $params = array(), $type = 'page')
    {
        if (!is_array($params)) {
            $params = array();
        }

        if ($type == 'page' || $type == 'section') {
            $fpath_pattern = '%s/%s/inc/tpl/%s.html.tpl';
        } elseif ($type == 'snippet') {
            $fpath_pattern = '%s/%s/inc/tpl/%s.snippet.tpl';
        } else {
            $fpath_pattern = null;
        }

        if ($fpath_pattern !== null) {
            $output = '';
            $fpath = sprintf($fpath_pattern, WP_PLUGIN_DIR, SUCURISCAN_PLUGIN_FOLDER, $template);

            if (file_exists($fpath) && is_readable($fpath)) {
                $output = @file_get_contents($fpath);

                $params['SucuriURL'] = SUCURISCAN_URL;

                // Detect the current page URL.
                if ($_page = self::get('page', '_page')) {
                    $params['CurrentURL'] = SucuriScan::admin_url('admin.php?page=' . $_page);
                } else {
                    $params['CurrentURL'] = SucuriScan::admin_url();
                }

                // Replace the global pseudo-variables in the section/snippets templates.
                if ($template == 'base'
                    && array_key_exists('PageContent', $params)
                    && @preg_match('/%%SUCURI\.(.+)%%/', $params['PageContent'])
                ) {
                    $params['PageContent'] = self::replacePseudoVars($params['PageContent'], $params);
                }

                $output = self::replacePseudoVars($output, $params);
            }

            if ($template == 'base' || $type != 'page') {
                return $output;
            }

            return self::getBaseTemplate($output, $params);
        }

        return '';
    }

    /**
     * Generate a HTML code using a template and replacing all the pseudo-variables
     * by the dynamic variables provided by the developer through one of the parameters
     * of the function.
     *
     * @param  string $template Filename of the template that will be used to generate the page.
     * @param  array  $params   Key-value array with pseudo-variables shared with the template.
     * @return string           The formatted HTML page after replace all the pseudo-variables.
     */
    public static function getSection($template = '', $params = array())
    {
        $params = self::sharedParams($params);

        return self::getTemplate($template, $params, 'section');
    }

    /**
     * Generate a HTML code using a template and replacing all the pseudo-variables
     * by the dynamic variables provided by the developer through one of the parameters
     * of the function.
     *
     * @param  string $template Filename of the template that will be used to generate the page.
     * @param  array  $params   Key-value array with pseudo-variables shared with the template.
     * @return string           The formatted HTML page after replace all the pseudo-variables.
     */
    public static function getModal($template = '', $params = array())
    {
        $required = array(
            'Title' => 'Lorem ipsum dolor sit amet',
            'Visibility' => 'visible',
            'Identifier' => 'foobar',
            'CssClass' => '',
            'Content' => '<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
                eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
                veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
                consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
                cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
                proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>',
        );

        if (!empty($template) && $template != 'none') {
            $params['Content'] = self::getSection($template);
        }

        foreach ($required as $param_name => $param_value) {
            if (!isset($params[$param_name])) {
                $params[$param_name] = $param_value;
            }
        }

        $params['Visibility'] = 'sucuriscan-' . $params['Visibility'];
        $params['Identifier'] = 'sucuriscan-' . $template . '-modal';
        $params = self::sharedParams($params);

        return self::getTemplate('modalwindow', $params, 'section');
    }

    /**
     * Generate a HTML code using a template and replacing all the pseudo-variables
     * by the dynamic variables provided by the developer through one of the parameters
     * of the function.
     *
     * @param  string $template Filename of the template that will be used to generate the page.
     * @param  array  $params   Key-value array with pseudo-variables shared with the template.
     * @return string           The formatted HTML page after replace all the pseudo-variables.
     */
    public static function getSnippet($template = '', $params = array())
    {
        return self::getTemplate($template, $params, 'snippet');
    }

    /**
     * Generate the HTML code necessary to render a list of options in a form.
     *
     * @param  array  $allowed_values List with keys and values allowed for the options.
     * @param  string $selected_val   Value of the option that will be selected by default.
     * @return string                 Option list for a select form field.
     */
    public static function selectOptions($allowed_values = array(), $selected_val = '')
    {
        $options = '';

        foreach ($allowed_values as $option_name => $option_label) {
            $options .= sprintf(
                "<option %s value='%s'>%s</option>\n",
                ($option_name === $selected_val ? 'selected="selected"' : ''),
                SucuriScan::escape($option_name),
                SucuriScan::escape($option_label)
            );
        }

        return $options;
    }

    /**
     * Detect which number in a pagination was clicked.
     *
     * @return integer Page number of the link clicked in a pagination.
     */
    public static function pageNumber()
    {
        $paged = self::get('paged', '[0-9]{1,5}');

        return ($paged ? intval($paged) : 1);
    }

    /**
     * Generate the HTML code to display a pagination.
     *
     * @param  string  $base_url     Base URL for the links before the page number.
     * @param  integer $total_items  Total quantity of items retrieved from a query.
     * @param  integer $max_per_page Maximum number of items that will be shown per page.
     * @return string                HTML code for a pagination generated using the provided data.
     */
    public static function pagination($base_url = '', $total_items = 0, $max_per_page = 1)
    {
        // Calculate the number of links for the pagination.
        $html_links = '';
        $page_number = self::pageNumber();
        $max_pages = ceil($total_items / $max_per_page);
        $extra_url = '';

        // Fix for inline anchor URLs.
        if (@preg_match('/^(.+)(#.+)$/', $base_url, $match)) {
            $base_url = $match[1];
            $extra_url = $match[2];
        }

        // Generate the HTML links for the pagination.
        for ($j = 1; $j <= $max_pages; $j++) {
            $link_class = 'sucuriscan-pagination-link';

            if ($page_number == $j) {
                $link_class .= "\x20sucuriscan-pagination-active";
            }

            $html_links .= sprintf(
                '<li><a href="%s&paged=%d%s" class="%s">%s</a></li>',
                $base_url,
                $j,
                $extra_url,
                $link_class,
                $j
            );
        }

        return $html_links;
    }
}

/**
 * File System Scanner
 *
 * The File System Scanner component performs full and incremental scans over a
 * file system folder, maintaining a snapshot of the filesystem and comparing it
 * with the current content to establish what content has been updated. Updated
 * content is then submitted to the remote server and it is stored for future
 * analysis.
 */
class SucuriScanFSScanner extends SucuriScan
{

    /**
     * Retrieve the last time when the filesystem scan was ran.
     *
     * @param  boolean $format Whether the timestamp must be formatted as date/time or not.
     * @return string          The timestamp of the runtime, or an string with the date/time.
     */
    public static function get_filesystem_runtime($format = false)
    {
        $runtime = SucuriScanOption::get_option(':runtime');

        if ($runtime > 0) {
            if ($format) {
                return SucuriScan::datetime($runtime);
            }

            return $runtime;
        }

        if ($format) {
            return 'Unknown';
        }

        return false;
    }

    /**
     * Check whether the administrator enabled the feature to ignore some
     * directories during the file system scans. This function is overwritten by a
     * GET parameter in the settings page named no_scan which must be equal to the
     * number one.
     *
     * @return boolean Whether the feature to ignore files is enabled or not.
     */
    public static function will_ignore_scanning()
    {
        return SucuriScanOption::is_enabled(':ignore_scanning');
    }

    /**
     * Add a new directory path to the list of ignored paths.
     *
     * @param  string  $directory_path The (full) absolute path of a directory.
     * @return boolean                 TRUE if the directory path was added to the list, FALSE otherwise.
     */
    public static function ignore_directory($directory_path = '')
    {
        $cache = new SucuriScanCache('ignorescanning');

        // Use the checksum of the directory path as the cache key.
        $cache_key = md5($directory_path);
        $resource_type = SucuriScanFileInfo::get_resource_type($directory_path);
        $cache_value = array(
            'directory_path' => $directory_path,
            'ignored_at' => self::local_time(),
            'resource_type' => $resource_type,
        );
        $cached = $cache->add($cache_key, $cache_value);

        return $cached;
    }

    /**
     * Remove a directory path from the list of ignored paths.
     *
     * @param  string  $directory_path The (full) absolute path of a directory.
     * @return boolean                 TRUE if the directory path was removed to the list, FALSE otherwise.
     */
    public static function unignore_directory($directory_path = '')
    {
        $cache = new SucuriScanCache('ignorescanning');

        // Use the checksum of the directory path as the cache key.
        $cache_key = md5($directory_path);
        $removed = $cache->delete($cache_key);

        return $removed;
    }

    /**
     * Retrieve a list of directories ignored.
     *
     * Retrieve a list of directory paths that will be ignored during the file
     * system scans, any sub-directory and files inside these folders will be
     * skipped automatically and will not be used to detect malware or modifications
     * in the site.
     *
     * The structure of the array returned by the function will always be composed
     * by four (4) indexes which will facilitate the execution of common conditions
     * in the implementation code.
     *
     * <ul>
     * <li>raw: Will contains the raw data retrieved from the built-in cache system.</li>
     * <li>checksums: Will contains the md5 of all the directory paths.</li>
     * <li>directories: Will contains a list of directory paths.</li>
     * <li>ignored_at_list: Will contains a list of timestamps for when the directories were ignored.</li>
     * </ul>
     *
     * @return array List of ignored directory paths.
     */
    public static function get_ignored_directories()
    {
        $response = array(
            'raw' => array(),
            'checksums' => array(),
            'directories' => array(),
            'ignored_at_list' => array(),
        );

        $cache = new SucuriScanCache('ignorescanning');
        $cache_lifetime = 0; // It is not necessary to expire this cache.
        $ignored_directories = $cache->getAll($cache_lifetime, 'array');

        if ($ignored_directories) {
            $response['raw'] = $ignored_directories;

            foreach ($ignored_directories as $checksum => $data) {
                if (array_key_exists('directory_path', $data)
                    && array_key_exists('ignored_at', $data)
                ) {
                    $response['checksums'][] = $checksum;
                    $response['directories'][] = $data['directory_path'];
                    $response['ignored_at_list'][] = $data['ignored_at'];
                }
            }
        }

        return $response;
    }

    /**
     * Run file system scan and retrieve ignored folders.
     *
     * Run a file system scan and retrieve an array with two indexes, the first
     * containing a list of ignored directory paths and their respective timestamps
     * of when they were added by an administrator user, and the second containing a
     * list of directories that are not being ignored.
     *
     * @return array List of ignored and not ignored directories.
     */
    public static function get_ignored_directories_live()
    {
        $response = array(
            'is_ignored' => array(),
            'is_not_ignored' => array(),
        );

        // Get the ignored directories from the cache.
        $ignored_directories = self::get_ignored_directories();

        if ($ignored_directories) {
            $response['is_ignored'] = $ignored_directories['raw'];
        }

        // Scan the project and file all directories.
        $file_info = new SucuriScanFileInfo();
        $file_info->ignore_files = true;
        $file_info->ignore_directories = true;
        $file_info->scan_interface = SucuriScanOption::get_option(':scan_interface');
        $directory_list = $file_info->get_diretories_only(ABSPATH);

        if ($directory_list) {
            $response['is_not_ignored'] = $directory_list;
        }

        return $response;
    }

    /**
     * Read and parse the lines inside a PHP error log file.
     *
     * @param  array $error_logs The content of an error log file, or an array with the lines.
     * @return array             List of valid error logs with their attributes separated.
     */
    public static function parse_error_logs($error_logs = array())
    {
        $logs_arr = array();
        $pattern = '/^'
            . '(\[(\S+) ([0-9:]{5,8})( \S+)?\] )?' // Detect date, time, and timezone.
            . '(PHP )?([a-zA-Z ]+):\s'             // Detect PHP error severity.
            . '(.+) in (.+)'                       // Detect error message, and file path.
            . '(:| on line )([0-9]+)'              // Detect line number.
            . '$/';

        if (is_string($error_logs)) {
            $error_logs = explode("\n", $error_logs);
        }

        foreach ((array) $error_logs as $line) {
            if (!is_string($line) || empty($line)) {
                continue;
            }

            if (preg_match($pattern, $line, $match)) {
                $data_set = array(
                    'date' => '',
                    'time' => '',
                    'timestamp' => 0,
                    'date_time' => '',
                    'time_zone' => '',
                    'error_type' => '',
                    'error_code' => 'unknown',
                    'error_message' => '',
                    'file_path' => '',
                    'line_number' => 0,
                );

                // Basic attributes from the scrapping.
                $data_set['date'] = $match[2];
                $data_set['time'] = $match[3];
                $data_set['time_zone'] = trim($match[4]);
                $data_set['error_type'] = trim($match[6]);
                $data_set['error_message'] = trim($match[7]);
                $data_set['file_path'] = trim($match[8]);
                $data_set['line_number'] = (int) $match[10];

                // Additional data from the attributes.
                if ($data_set['date']) {
                    $data_set['date_time'] = $data_set['date']
                        . "\x20" . $data_set['time']
                        . "\x20" . $data_set['time_zone'];
                    $data_set['timestamp'] = strtotime($data_set['date_time']);
                }

                if ($data_set['error_type']) {
                    $valid_types = array( 'warning', 'notice', 'error' );

                    foreach ($valid_types as $valid_type) {
                        if (stripos($data_set['error_type'], $valid_type) !== false) {
                            $data_set['error_code'] = $valid_type;
                            break;
                        }
                    }
                }

                $logs_arr[] = (object) $data_set;
            }
        }

        return $logs_arr;
    }
}

/**
 * Heartbeat library.
 *
 * The purpose of the Heartbeat API is to simulate bidirectional connection
 * between the browser and the server. Initially it was used for autosave, post
 * locking and log-in expiration warning while a user is writing or editing. The
 * idea was to have an API that sends XHR (XML HTTP Request) requests to the
 * server every fifteen seconds and triggers events (or callbacks) on receiving
 * data.
 *
 * @see https://core.trac.wordpress.org/ticket/23216
 */
class SucuriScanHeartbeat extends SucuriScanOption
{

    /**
     * Stop execution of the heartbeat API in certain parts of the site.
     *
     * @return void
     */
    public static function register_script()
    {
        global $pagenow;

        $status = SucuriScanOption::get_option(':heartbeat');

        // Enable heartbeat everywhere.
        if ($status == 'enabled') {
            /* Do nothing */
        } // Disable heartbeat everywhere.
        elseif ($status == 'disabled') {
            wp_deregister_script('heartbeat');
        } // Disable heartbeat only on the dashboard and home pages.
        elseif ($status == 'dashboard'
            && $pagenow == 'index.php'
        ) {
            wp_deregister_script('heartbeat');
        } // Disable heartbeat everywhere except in post edition.
        elseif ($status == 'addpost'
            && $pagenow != 'post.php'
            && $pagenow != 'post-new.php'
        ) {
            wp_deregister_script('heartbeat');
        }
    }

    /**
     * Update the settings of the Heartbeat API according to the values set by an
     * administrator. This tool may cause an increase in the CPU usage, a bad
     * configuration may cause low account to run out of resources, but in better
     * cases it may improve the performance of the site by reducing the quantity of
     * requests sent to the server per session.
     *
     * @param  array $settings Heartbeat settings.
     * @return array           Updated version of the heartbeat settings.
     */
    public static function update_settings($settings = array())
    {
        $pulse = SucuriScanOption::get_option(':heartbeat_pulse');
        $autostart = SucuriScanOption::get_option(':heartbeat_autostart');

        if ($pulse < 15 || $pulse > 60) {
            SucuriScanOption::delete_option(':heartbeat_pulse');
            $pulse = 15;
        }

        $settings['interval'] = $pulse;
        $settings['autostart'] = ( $autostart == 'disabled' ? false : true );

        return $settings;
    }

    /**
     * Respond to the browser according to the data received.
     *
     * @param  array  $response  Response received.
     * @param  array  $data      Data received from the beat.
     * @param  string $screen_id Identifier of the screen the heartbeat occurred on.
     * @return array             Response with new data.
     */
    public static function respond_to_received($response = array(), $data = array(), $screen_id = '')
    {
        $interval = SucuriScanOption::get_option(':heartbeat_interval');

        if ($interval == 'slow'
            || $interval == 'fast'
            || $interval == 'standard'
        ) {
            $response['heartbeat_interval'] = $interval;
        } else {
            SucuriScanOption::delete_option(':heartbeat_interval');
        }

        return $response;
    }

    /**
     * Respond to the browser according to the data sent.
     *
     * @param  array  $response  Response sent.
     * @param  string $screen_id Identifier of the screen the heartbeat occurred on.
     * @return array             Response with new data.
     */
    public static function respond_to_send($response = array(), $screen_id = '')
    {
        return $response;
    }

    /**
     * Allowed values for the heartbeat status.
     *
     * @return array Allowed values for the heartbeat status.
     */
    public static function statuses_allowed()
    {
        return array(
            'enabled' => 'Enable everywhere',
            'disabled' => 'Disable everywhere',
            'dashboard' => 'Disable on dashboard page',
            'addpost' => 'Everywhere except post addition',
        );
    }

    /**
     * Allowed values for the heartbeat intervals.
     *
     * @return array Allowed values for the heartbeat intervals.
     */
    public static function intervals_allowed()
    {
        return array(
            'slow' => 'Slow interval',
            'fast' => 'Fast interval',
            'standard' => 'Standard interval',
        );
    }

    /**
     * Allowed values for the heartbeat pulses.
     *
     * @return array Allowed values for the heartbeat pulses.
     */
    public static function pulses_allowed()
    {
        $pulses = array();

        for ($i = 15; $i <= 60; $i++) {
            $pulses[ $i ] = sprintf('Run every %d seconds', $i);
        }

        return $pulses;
    }
}

/**
 * Plugin initializer.
 *
 * Define all the required variables, script, styles, and basic functions needed
 * when the site is loaded, not even the administrator panel but also the front
 * page, some bug-fixes will/are applied here for sites behind a proxy, and
 * sites with old versions of the premium plugin (that was deprecated at
 * July/2014).
 */
class SucuriScanInterface
{
    /**
     * Initialization code for the plugin.
     *
     * The initial variables and information needed by the plugin during the
     * execution of other functions will be generated. Things like the real IP
     * address of the client when it has been forwarded or it's behind an external
     * service like a Proxy.
     *
     * @return void
     */
    public static function initialize()
    {
        if (SucuriScan::support_reverse_proxy()
            || SucuriScan::is_behind_cloudproxy()
        ) {
            $_SERVER['SUCURIREAL_REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
            $_SERVER['REMOTE_ADDR'] = SucuriScan::get_remote_addr();
        }
    }

    /**
     * Define which javascript and css files will be loaded in the header of the
     * plugin pages, only when the administrator panel is accessed.
     *
     * @return void
     */
    public static function enqueue_scripts()
    {
        $asset_version = '';

        if (strlen(SUCURISCAN_PLUGIN_CHECKSUM) >= 7) {
            $asset_version = substr(SUCURISCAN_PLUGIN_CHECKSUM, 0, 7);
        }

        wp_register_style('sucuriscan', SUCURISCAN_URL . '/inc/css/sucuri-scanner.min.css', array(), $asset_version);
        wp_register_script('sucuriscan', SUCURISCAN_URL . '/inc/js/sucuri-scanner.min.js', array(), $asset_version);
        wp_enqueue_style('sucuriscan');
        wp_enqueue_script('sucuriscan');

        if (SucuriScanRequest::get('page', 'sucuriscan') !== false) {
            wp_register_script('sucuriscan2', SUCURISCAN_URL . '/inc/js/d3.min.js', array(), $asset_version);
            wp_register_script('sucuriscan3', SUCURISCAN_URL . '/inc/js/c3.min.js', array(), $asset_version);
            wp_enqueue_script('sucuriscan2');
            wp_enqueue_script('sucuriscan3');
        }
    }

    /**
     * Generate the menu and submenus for the plugin in the admin interface.
     *
     * @return void
     */
    public static function add_interface_menu()
    {
        global $sucuriscan_pages;

        if (function_exists('add_menu_page')
            && $sucuriscan_pages
            && is_array($sucuriscan_pages)
            && array_key_exists('sucuriscan', $sucuriscan_pages)
        ) {
            // Add main menu link.
            add_menu_page(
                'Sucuri Security',
                'Sucuri Security',
                'manage_options',
                'sucuriscan',
                'sucuriscan_page',
                SUCURISCAN_URL . '/inc/images/menu-icon.png'
            );

            foreach ($sucuriscan_pages as $sub_page_func => $sub_page_title) {
                if ($sub_page_func == 'sucuriscan_scanner'
                    && SucuriScanOption::is_disabled(':sitecheck_scanner')
                ) {
                    continue;
                }

                $page_func = $sub_page_func . '_page';

                add_submenu_page(
                    'sucuriscan',
                    $sub_page_title,
                    $sub_page_title,
                    'manage_options',
                    $sub_page_func,
                    $page_func
                );
            }
        }
    }

    /**
     * Remove the old Sucuri plugins considering that with the new version (after
     * 1.6.0) all the functionality of the others will be merged here, this will
     * remove duplicated functionality, duplicated bugs and/or duplicated
     * maintenance reports allowing us to focus in one unique project.
     *
     * @return void
     */
    public static function handle_old_plugins()
    {
        if (class_exists('SucuriScanFileInfo')) {
            $file_info = new SucuriScanFileInfo();
            $file_info->ignore_files = false;
            $file_info->ignore_directories = false;

            $plugins = array(
                'sucuri-wp-plugin/sucuri.php',
                'sucuri-cloudproxy-waf/cloudproxy.php',
            );

            foreach ($plugins as $plugin) {
                $plugin_directory = dirname(WP_PLUGIN_DIR . '/' . $plugin);

                if (file_exists($plugin_directory)) {
                    if (is_plugin_active($plugin)) {
                        deactivate_plugins($plugin);
                    }

                    $plugin_removed = $file_info->remove_directory_tree($plugin_directory);
                }
            }
        }
    }

    /**
     * Create a folder in the WordPress upload directory where the plugin will
     * store all the temporal or dynamic information.
     *
     * @return void
     */
    public static function create_datastore_folder()
    {
        $directory = SucuriScan::datastore_folder_path();

        if (!file_exists($directory)) {
            @mkdir($directory, 0755, true);
        }

        if (@preg_match(';/uploads/$;', $directory)) {
            SucuriScanOption::delete_option(':datastore_path');
            SucuriScanInterface::error('Uploads directory must not be used as the data store path.');
        } elseif (file_exists($directory)) {
            // Create last-logins datastore file.
            sucuriscan_lastlogins_datastore_exists();

            // Create a htaccess file to deny access from all.
            @file_put_contents(
                $directory . '/.htaccess',
                "Order Deny,Allow\nDeny from all\n",
                LOCK_EX
            );

            // Create an index.html to avoid directory listing.
            @file_put_contents(
                $directory . '/index.html',
                '<!-- Prevent the directory listing. -->',
                LOCK_EX
            );
        } else {
            SucuriScanOption::delete_option(':datastore_path');
            SucuriScanInterface::error(
                'Data folder does not exists and could not be created. Try to <a href="' .
                SucuriScanTemplate::getUrl('settings') . '">click this link</a> to see
                if the plugin is able to fix this error automatically, if this message
                reappears you will need to either change the location of the directory from
                the plugin general settings page or create this directory manually and give
                it write permissions: <code>' . $directory . '</code>'
            );
        }
    }

    /**
     * Check whether a user has the permissions to see a page from the plugin.
     *
     * @return void
     */
    public static function check_permissions()
    {
        if (!function_exists('current_user_can')
            || !current_user_can('manage_options')
        ) {
            $page = SucuriScanRequest::get('page', '_page');
            $page = SucuriScan::escape($page);
            wp_die(__('Access denied by <b>Sucuri</b> to see <code>' . $page . '</code>'));
        }
    }

    /**
     * Verify the nonce of the previous page after a form submission. If the
     * validation fails the execution of the script will be stopped and a dead page
     * will be printed to the client using the official WordPress method.
     *
     * @return boolean Either TRUE or FALSE if the nonce is valid or not respectively.
     */
    public static function check_nonce()
    {
        if (!empty($_POST)) {
            $nonce_name = 'sucuriscan_page_nonce';
            $nonce_value = SucuriScanRequest::post($nonce_name, '_nonce');

            if (!$nonce_value || !wp_verify_nonce($nonce_value, $nonce_name)) {
                wp_die(__('WordPress Nonce verification failed, try again going back and checking the form.'));

                return false;
            }
        }

        return true;
    }

    /**
     * Prints a HTML alert in the WordPress admin interface.
     *
     * @param  string $type    The type of alert, it can be either Updated or Error.
     * @param  string $message The message that will be printed in the alert.
     * @return void
     */
    private static function admin_notice($type = 'updated', $message = '')
    {
        $display_notice = true;

        /**
         * Do not render notice during user authentication.
         *
         * There are some special cases when the error or warning messages should not be
         * rendered to the end user because it may break the default functionality of
         * the request handler. For instance, rendering an HTML alert like this when the
         * user authentication process is executed may cause a "headers already sent"
         * error.
         */
        if (!empty($_POST)
            && SucuriScanRequest::post('log')
            && SucuriScanRequest::post('pwd')
            && SucuriScanRequest::post('wp-submit')
        ) {
            $display_notice = false;
        }

        // Display the HTML notice to the current user.
        if ($display_notice === true && !empty($message)) {
            echo SucuriScanTemplate::getSection(
                'notification-admin',
                array(
                    'AlertType' => $type,
                    'AlertUnique' => rand(100, 999),
                    'AlertMessage' => $message,
                )
            );
        }
    }

    /**
     * Prints a HTML alert of type ERROR in the WordPress admin interface.
     *
     * @param  string $error_msg The message that will be printed in the alert.
     * @return void
     */
    public static function error($error_msg = '')
    {
        self::admin_notice('error', '<b>Sucuri:</b> ' . $error_msg);
    }

    /**
     * Prints a HTML alert of type INFO in the WordPress admin interface.
     *
     * @param  string $info_msg The message that will be printed in the alert.
     * @return void
     */
    public static function info($info_msg = '')
    {
        self::admin_notice('updated', '<b>Sucuri:</b> ' . $info_msg);
    }

    /**
     * Display a notice message with instructions to continue the setup of the
     * plugin, this includes the generation of the API key and other steps that need
     * to be done to fully activate this plugin.
     *
     * @return void
     */
    public static function setup_notice()
    {
        if (current_user_can('manage_options')
            && SucuriScan::no_notices_here() === false
            && !SucuriScanAPI::getPluginKey()
            && SucuriScanRequest::post(':plugin_api_key') === false
            && SucuriScanRequest::post(':recover_key') === false
            && !SucuriScanRequest::post(':manual_api_key')
        ) {
            if (SucuriScanRequest::get(':dismiss_setup') !== false) {
                SucuriScanOption::update_option(':dismiss_setup', 'enabled');
            } elseif (SucuriScanOption::is_enabled(':dismiss_setup')) {
                /* Do not display API key generation form. */
            } else {
                echo SucuriScanTemplate::getSection('setup-notice');
                echo SucuriScanTemplate::getModal('setup-form', array(
                    'Visibility' => 'hidden',
                    'Title' => 'Sucuri API key generation',
                    'CssClass' => 'sucuriscan-setup-instructions',
                ));
            }
        }
    }
}

/**
 * Scan the content directory for files that were modified during the last seven
 * days (by default) or during the number of days specified by the user in the
 * request. Note that this operation may fail with an internal server error if
 * the project contains too many files that the PHP interpreter can check in a
 * single run.
 *
 * @return void
 */
function sucuriscan_modified_files()
{
    // TODO: Keep the array values hardcoded for now.
    $valid_day_ranges = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 30, 60);
    $template_variables = array(
        'ModifiedFiles.List' => '',
        'ModifiedFiles.SelectOptions' => '',
        'ModifiedFiles.Days' => 0,
    );

    // Generate the options for the select field of the page form.
    if ($valid_day_ranges) {
        $options = array();
        foreach ($valid_day_ranges as $day) {
            $options[$day] = sprintf(
                '%d day%s ago',
                $day,
                ($day === 1)?'':'s'
            );
        }
        $options_html = SucuriScanTemplate::selectOptions($options, 7);
        $template_variables['ModifiedFiles.SelectOptions'] = $options_html;
    }

    return SucuriScanTemplate::getSection('integrity-modifiedfiles', $template_variables);
}

function sucuriscan_scanner_modfiles_days($back_days = 0)
{
    $default_number_of_days = 7;
    $back_days = intval($back_days);

    // Keep the number of days in range.
    if ($back_days === false) {
        $back_days = $default_number_of_days;
    } else {
        if ($back_days <= 0) {
            $back_days = 1;
        } elseif ($back_days >= 60) {
            $back_days = 60;
        }
    }

    return $back_days;
}

function sucuriscan_scanner_modfiles_ajax()
{
    if (SucuriScanRequest::post('form_action') == 'get_modfiles') {
        $response = '';
        $hashes = sucuriscan_get_integrity_tree(WP_CONTENT_DIR, true);
        $back_days = SucuriScanRequest::post(':last_days', '[0-9]+');
        $back_days = sucuriscan_scanner_modfiles_days($back_days);

        if (!empty($hashes)) {
            $counter = 0;
            $back_days = current_time('timestamp') - ($back_days * 86400);

            foreach ($hashes as $file_path => $file_info) {
                if (isset($file_info['modified_at'])
                    && $file_info['modified_at'] >= $back_days
                ) {
                    $css_class = ($counter % 2 === 0) ? '' : 'alternate';
                    $mod_date = SucuriScan::datetime($file_info['modified_at']);

                    $response .= SucuriScanTemplate::getSnippet(
                        'integrity-modifiedfiles',
                        array(
                            'ModifiedFiles.DateTime' => $mod_date,
                            'ModifiedFiles.FilePath' => $file_path,
                            'ModifiedFiles.CheckSum' => $file_info['checksum'],
                            'ModifiedFiles.FileSize' => $file_info['filesize'],
                            'ModifiedFiles.FileSizeHuman' => SucuriScan::human_filesize($file_info['filesize']),
                            'ModifiedFiles.FileSizeNumber' => number_format($file_info['filesize']),
                            'ModifiedFiles.CssClass' => $css_class,
                        )
                    );
                    $counter++;
                }
            }
        }

        print($response);
        exit(0);
    }
}

/**
 * Display the page with a temporary message explaining the action that will be
 * performed once the hidden form is submitted to retrieve the scanning results
 * from the public SiteCheck API.
 *
 * @return void
 */
function sucuriscan_scanner_page()
{
    SucuriScanInterface::check_permissions();

    $params = array();
    $cache = new SucuriScanCache('sitecheck');
    $scan_results = $cache->get('scan_results', SUCURISCAN_SITECHECK_LIFETIME, 'array');
    $report_results = (bool) ($scan_results && !empty($scan_results));

    if (SucuriScanInterface::check_nonce()
        && SucuriScanRequest::post(':malware_scan', '1')
    ) {
        $report_results = true;
    }

    if ($report_results === true) {
        $template_name = 'malwarescan-results';
        $params = sucuriscan_sitecheck_info($scan_results);
        $params['PageTitle'] = 'Malware Scan';
        $params['PageStyleClass'] = 'scanner-results';
    } else {
        $template_name = 'malwarescan';
        $params['PageTitle'] = 'Malware Scan';
        $params['PageStyleClass'] = 'scanner-loading';
    }

    echo SucuriScanTemplate::getTemplate($template_name, $params);
}

/**
 * Handle an Ajax request for this specific page.
 *
 * @return mixed.
 */
function sucuriscan_scanner_ajax()
{
    SucuriScanInterface::check_permissions();

    if (SucuriScanInterface::check_nonce()) {
        sucuriscan_scanner_modfiles_ajax();
    }

    wp_die();
}

/**
 * Display the result of site scan made through SiteCheck.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @return array               Array with psuedo-variables to build the template.
 */
function sucuriscan_sitecheck_info($scan_results = array())
{
    $clean_domain = SucuriScan::get_domain();
    $params = array(
        'ScannedDomainName' => $clean_domain,
        'ScannerResults.CssClass' => '',
        'ScannerResults.Content' => '',
        'WebsiteDetails.CssClass' => '',
        'WebsiteDetails.Content' => '',
        'BlacklistStatus.CssClass' => '',
        'BlacklistStatus.Content' => '',
        'WebsiteLinks.CssClass' => '',
        'WebsiteLinks.Content' => '',
        'ModifiedFiles.CssClass' => '',
        'ModifiedFiles.Content' => '',
        'SignupButtonVisibility' => 'hidden',
    );

    // If the results are not cached, then request a new scan and store in cache.
    if ($scan_results === false) {
        $scan_results = SucuriScanAPI::getSitecheckResults($clean_domain);

        // Check for error messages in the request's response.
        if (is_string($scan_results)) {
            if (@preg_match('/^ERROR:(.*)/', $scan_results, $error_m)) {
                SucuriScanInterface::error(
                    'The site <code>' . SucuriScan::escape($clean_domain) . '</code>'
                    . ' was not scanned: ' . SucuriScan::escape($error_m[1])
                );
            } else {
                SucuriScanInterface::error('SiteCheck error: ' . $scan_results);
            }
        } else {
            $cache = new SucuriScanCache('sitecheck');
            $results_were_cached = $cache->add('scan_results', $scan_results);

            if (!$results_were_cached) {
                SucuriScanInterface::error('Could not cache the malware scan results.');
            }
        }
    }

    if (is_array($scan_results) && !empty($scan_results)) {
        // Increase the malware scan counter.
        $sitecheck_counter = (int) SucuriScanOption::get_option(':sitecheck_counter');
        SucuriScanOption::update_option(':sitecheck_counter', $sitecheck_counter + 1);
        add_thickbox();

        $params = sucuriscan_sitecheck_scanner_results($scan_results, $params);
        $params = sucuriscan_sitecheck_website_details($scan_results, $params);
        $params = sucuriscan_sitecheck_website_links($scan_results, $params);
        $params = sucuriscan_sitecheck_blacklist_status($scan_results, $params);
        $params = sucuriscan_sitecheck_modified_files($scan_results, $params);

        if (isset($scan_results['MALWARE']['WARN'])
            || isset($scan_results['BLACKLIST']['WARN'])
        ) {
            $params['SignupButtonVisibility'] = 'visible';
        }
    }

    return $params;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * remote scanner results tab.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_scanner_results($scan_results = false, $params = array())
{
    $secvars = array(
        'CacheLifeTime' => SUCURISCAN_SITECHECK_LIFETIME,
        'WebsiteStatus' => 'Site status unknown',
        'NoMalwareRowVisibility' => 'visible',
        'FixButtonVisibility' => 'hidden',
        'MalwarePayloadList' => '',
    );

    if (isset($scan_results['MALWARE']['WARN'])) {
        $params['ScannerResults.CssClass'] = 'sucuriscan-red-tab';
        $secvars['WebsiteStatus'] = 'Site compromised (malware was identified)';
        $secvars['NoMalwareRowVisibility'] = 'hidden';
        $secvars['FixButtonVisibility'] = 'visible';

        foreach ($scan_results['MALWARE']['WARN'] as $key => $malres) {
            $malres = SucuriScanAPI::getSitecheckMalware($malres);

            if ($malres !== false) {
                $secvars['MalwarePayloadList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-resmalware',
                    array(
                        'MalwareKey' => $key,
                        'MalwareDocs' => $malres['malware_docs'],
                        'MalwareType' => $malres['malware_type'],
                        'MalwarePayload' => $malres['malware_payload'],
                        'AlertMessage' => $malres['alert_message'],
                        'InfectedUrl' => $malres['infected_url'],
                    )
                );
            }
        }
    } else {
        $secvars['WebsiteStatus'] = 'Site clean (no malware was identified)';
    }

    $params['ScannerResults.Content'] = SucuriScanTemplate::getSection('malwarescan-resmalware', $secvars);

    return $params;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_website_details($scan_results = false, $params = array())
{
    $secvars = array(
        'UpdateWebsiteButtonVisibility' => 'hidden',
        'VersionNumberOfTheUpdate' => '0.0',
        'AdminUrlForUpdates' => SucuriScan::admin_url('update-core.php'),
        'GenericInformationList' => '',
        'NoAppDetailsVisibility' => 'visible',
        'ApplicationDetailsList' => '',
        'SystemNoticeList' => '',
        'OutdatedSoftwareList' => '',
        'HasRecommendationsVisibility' => 'hidden',
        'SecurityRecomendationList' => '',
    );

    // Check whether this WordPress installation needs an update.
    if (function_exists('get_core_updates')) {
        $site_updates = get_core_updates();

        if (!is_array($site_updates)
            || empty($site_updates)
            || $site_updates[0]->response == 'latest'
        ) {
            $secvars['VersionNumberOfTheUpdate'] = $site_updates[0]->version;
        }
    }

    if (isset($scan_results['OUTDATEDSCAN'])
        || isset($scan_results['RECOMMENDATIONS'])
    ) {
        $params['WebsiteDetails.CssClass'] = 'sucuriscan-red-tab';
    }

    $secvars = sucuriscan_sitecheck_general_information($scan_results, $secvars);
    $secvars = sucuriscan_sitecheck_application_details($scan_results, $secvars);
    $secvars = sucuriscan_sitecheck_system_notices($scan_results, $secvars);
    $secvars = sucuriscan_sitecheck_outdated_software($scan_results, $secvars);
    $secvars = sucuriscan_sitecheck_recommendations($scan_results, $secvars);

    $params['WebsiteDetails.Content'] = SucuriScanTemplate::getSection('malwarescan-reswebdetails', $secvars);

    return $params;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab and specifically in the general information panel.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_general_information($scan_results = false, $secvars = array())
{
    $possible_keys = array(
        'DOMAIN' => 'Domain Scanned',
        'IP' => 'Site IP Address',
        'HOSTING' => 'Hosting Company',
        'CMS' => 'CMS Found',
        'WP_VERSION' => 'WordPress Version',
        'PHP_VERSION' => 'PHP Version',
    );

    if (isset($scan_results['SCAN'])) {
        $scan_results['SCAN']['WP_VERSION'] = array(SucuriScan::site_version());
        $scan_results['SCAN']['PHP_VERSION'] = array(phpversion());

        foreach ($possible_keys as $result_key => $result_title) {
            if (isset($scan_results['SCAN'][$result_key])) {
                if (is_array($scan_results['SCAN'][$result_key])) {
                    $result_value = implode(', ', $scan_results['SCAN'][$result_key]);
                } else {
                    $result_value = json_encode($scan_results['SCAN'][$result_key]);
                }

                $secvars['GenericInformationList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-appdetail',
                    array(
                        'InformationTitle' => $result_title,
                        'InformationValue' => $result_value,
                    )
                );
            }
        }
    }

    return $secvars;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab and specifically in the application details panel.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_application_details($scan_results = false, $secvars = array())
{
    if (isset($scan_results['WEBAPP'])) {
        foreach ($scan_results['WEBAPP'] as $webapp_key => $webapp_details) {
            if (is_array($webapp_details)) {
                foreach ($webapp_details as $i => $details) {
                    $secvars['NoAppDetailsVisibility'] = 'hidden';

                    if (is_array($details)) {
                        $details = isset($details[0]) ? $details[0] : '';
                    }

                    $details_parts = explode(':', $details, 2);
                    $result_title = isset($details_parts[0]) ? trim($details_parts[0]) : '';
                    $result_value = isset($details_parts[1]) ? trim($details_parts[1]) : '';

                    $secvars['ApplicationDetailsList'] .= SucuriScanTemplate::getSnippet(
                        'malwarescan-appdetail',
                        array(
                            'InformationTitle' => $result_title,
                            'InformationValue' => $result_value,
                        )
                    );
                }
            }
        }
    }

    return $secvars;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab and specifically in the system notices panel.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_system_notices($scan_results = false, $secvars = array())
{
    if (isset($scan_results['SYSTEM']['NOTICE'])) {
        foreach ($scan_results['SYSTEM']['NOTICE'] as $notice) {
            $secvars['NoAppDetailsVisibility'] = 'hidden';

            if (is_array($notice)) {
                $notice = implode(', ', $notice);
            }

            $secvars['SystemNoticeList'] .= SucuriScanTemplate::getSnippet(
                'malwarescan-sysnotice',
                array(
                    'SystemNotice' => $notice,
                )
            );
        }
    }

    return $secvars;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab and specifically in the outdated software panel.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_outdated_software($scan_results = false, $secvars = array())
{
    if (isset($scan_results['OUTDATEDSCAN'])) {
        foreach ($scan_results['OUTDATEDSCAN'] as $outdated) {
            if (count($outdated) >= 3) {
                $secvars['HasRecommendationsVisibility'] = 'visible';
                $secvars['OutdatedSoftwareList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-outdated',
                    array(
                        'OutdatedSoftwareTitle' => $outdated[0],
                        'OutdatedSoftwareUrl' => $outdated[1],
                        'OutdatedSoftwareValue' => $outdated[2],
                    )
                );
            }
        }
    }

    return $secvars;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website details tab and specifically in the security recommendations panel.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_recommendations($scan_results = false, $secvars = array())
{
    if (isset($scan_results['RECOMMENDATIONS'])) {
        foreach ($scan_results['RECOMMENDATIONS'] as $recommendation) {
            if (count($recommendation) >= 3) {
                $secvars['HasRecommendationsVisibility'] = 'visible';
                $secvars['SecurityRecomendationList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-recommendation',
                    array(
                        'RecommendationTitle' => $recommendation[0],
                        'RecommendationValue' => $recommendation[1],
                        'RecommendationUrl' => $recommendation[2],
                        'RecommendationUrlTitle' => $recommendation[2],
                    )
                );
            }
        }
    }

    return $secvars;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * website links tab.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra information.
 */
function sucuriscan_sitecheck_website_links($scan_results = false, $params = array())
{
    $possible_url_keys = array(
        'IFRAME' => 'List of iframes found',
        'JSEXTERNAL' => 'List of external scripts included',
        'JSLOCAL' => 'List of scripts included',
        'URL' => 'List of links found',
    );
    $secvars = array(
        'WebsiteLinksAllList' => '',
        'NoLinksVisibility' => 'hidden',
    );

    if (isset($scan_results['LINKS'])) {
        foreach ($possible_url_keys as $result_key => $result_title) {
            if (isset($scan_results['LINKS'][$result_key])) {
                $result_value = 0;
                $result_items = '';

                foreach ($scan_results['LINKS'][$result_key] as $url_path) {
                    $result_value += 1;
                    $result_items .= SucuriScanTemplate::getSnippet(
                        'malwarescan-weblinkitems',
                        array(
                            'WebsiteLinksItemTitle' => $url_path,
                        )
                    );
                }

                $secvars['WebsiteLinksAllList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-weblinktitle',
                    array(
                        'WebsiteLinksSectionTitle' => $result_title,
                        'WebsiteLinksSectionTotal' => $result_value,
                        'WebsiteLinksSectionItems' => $result_items /* Do not escape. */,
                    )
                );
            }
        }
    } else {
        $secvars['NoLinksVisibility'] = 'visible';
    }

    $params['WebsiteLinks.Content'] = SucuriScanTemplate::getSection('malwarescan-resweblinks', $secvars);

    return $params;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * blacklist status tab.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_blacklist_status($scan_results = false, $params = array())
{
    $blacklist_types = array(
        'INFO' => 'CLEAN',
        'WARN' => 'WARNING',
    );
    $secvars = array(
        'BlacklistStatusTitle' => 'Site blacklist-free',
        'BlacklistStatusList' => '',
    );

    if (isset($scan_results['BLACKLIST']['WARN'])) {
        $params['BlacklistStatusTitle'] = 'Site blacklisted';
        $params['BlacklistStatus.CssClass'] = 'sucuriscan-red-tab';
    }

    foreach ($blacklist_types as $type => $group_title) {
        if (isset($scan_results['BLACKLIST'][$type])) {
            foreach ($scan_results['BLACKLIST'][$type] as $blres) {
                $css_blacklist = ($type == 'INFO') ? 'success' : 'danger';

                $secvars['BlacklistStatusList'] .= SucuriScanTemplate::getSnippet(
                    'malwarescan-resblacklist',
                    array(
                        'BlacklistStatusCssClass' => $css_blacklist,
                        'BlacklistStatusGroupTitle' => $group_title,
                        'BlacklistStatusReporterName' => $blres[0],
                        'BlacklistStatusReporterUrl' => $blres[1],
                    )
                );
            }
        }
    }

    $params['BlacklistStatus.Content'] = SucuriScanTemplate::getSection('malwarescan-resblacklist', $secvars);

    return $params;
}

/**
 * Process the data returned from the results of a SiteCheck scan and generate
 * the HTML code to display the information in the malware scan page inside the
 * modified files tab.
 *
 * @param  array $scan_results Array with information of the scanning.
 * @param  array $params       Array with psuedo-variables to build the template.
 * @return array               Psuedo-variables to build the template including extra info.
 */
function sucuriscan_sitecheck_modified_files($scan_results = false, $params = array())
{
    $params['ModifiedFiles.Content'] = sucuriscan_modified_files();

    return $params;
}

/**
 * Generate the HTML code for the firewall settings panel.
 *
 * @param  string $api_key The CloudProxy API key.
 * @return string          The parsed-content of the firewall settings panel.
 */
function sucuriscan_firewall_settings($api_key = '')
{
    $params = array(
        'Firewall.APIKey' => '',
        'Firewall.APIKeyVisibility' => 'hidden',
        'Firewall.APIKeyFormVisibility' => 'visible',
        'Firewall.SettingsVisibility' => 'hidden',
        'Firewall.SettingOptions' => '',
    );

    if ($api_key && array_key_exists('string', $api_key)) {
        $settings = SucuriScanAPI::getCloudproxySettings($api_key);

        $params['Firewall.APIKeyVisibility'] = 'visible';
        $params['Firewall.APIKeyFormVisibility'] = 'hidden';
        $params['Firewall.APIKey'] = $api_key['string'];

        if ($settings) {
            $counter = 0;
            $params['Firewall.SettingsVisibility'] = 'visible';
            $settings = sucuriscan_explain_firewall_settings($settings);

            foreach ($settings as $option_name => $option_value) {
                $css_class = ($counter % 2 === 0) ? 'alternate' : '';
                $option_title = ucwords(str_replace('_', "\x20", $option_name));

                // Generate a HTML list when the option's value is an array.
                if (is_array($option_value)) {
                    $css_scrollable = count($option_value) > 10 ? 'sucuriscan-list-as-table-scrollable' : '';
                    $html_list  = '<ul class="sucuriscan-list-as-table ' . $css_scrollable . '">';

                    foreach ($option_value as $single_value) {
                        $html_list .= '<li>' . SucuriScan::escape($single_value) . '</li>';
                    }

                    $html_list .= '</ul>';
                    $option_value = $html_list;
                } else {
                    $option_value = SucuriScan::escape($option_value);
                }

                // Parse the snippet template and replace the pseudo-variables.
                $params['Firewall.SettingOptions'] .= SucuriScanTemplate::getSnippet(
                    'firewall-settings',
                    array(
                        'Firewall.OptionCssClass' => $css_class,
                        'Firewall.OptionName' => $option_title,
                        'Firewall.OptionValue' => $option_value,
                    )
                );
                $counter++;
            }
        }
    }

    return SucuriScanTemplate::getSection('firewall-settings', $params);
}

/**
 * Converts the value of some of the firewall settings into a human-readable
 * text, for example changing numbers or variable names into a more explicit
 * text so the administrator can understand the meaning of these settings.
 *
 * @param  array $settings A hash with the settings of a CloudProxy account.
 * @return array           The explained version of the CloudProxy settings.
 */
function sucuriscan_explain_firewall_settings($settings = array())
{
    $cache_modes = array(
        'docache' => 'enabled (recommended)',
        'sitecache' => 'site caching (using your site headers)',
        'nocache' => 'minimal (only for a few minutes)',
        'nocacheatall' => 'caching disabled (use with caution)',
    );

    // TODO: Prefer Array over stdClass, modify the API library.
    $settings = @json_decode(json_encode($settings), true);

    foreach ($settings as $keyname => $value) {
        if ($keyname == 'proxy_active') {
            $settings[$keyname] = ($value === 1) ? 'active' : 'not active';
        } elseif ($keyname == 'cache_mode') {
            if (array_key_exists($keyname, $cache_modes)) {
                $settings[$keyname] = $cache_modes[$keyname];
            } else {
                $settings[$keyname] = 'unknown';
            }
        }
    }

    return $settings;
}

/**
 * Generate the HTML code for the firewall logs panel.
 *
 * @param  string $api_key The CloudProxy API key.
 * @return string          The parsed-content of the firewall logs panel.
 */
function sucuriscan_firewall_auditlogs($api_key = '')
{
    $date = date('Y-m-d');
    $params = array();

    $params['AuditLogs.DateYears'] = sucuriscan_firewall_dates('years', $date);
    $params['AuditLogs.DateMonths'] = sucuriscan_firewall_dates('months', $date);
    $params['AuditLogs.DateDays'] = sucuriscan_firewall_dates('days', $date);

    return SucuriScanTemplate::getSection('firewall-auditlogs', $params);
}

function sucuriscan_firewall_auditlogs_ajax()
{
    if (SucuriScanRequest::post('form_action') == 'get_audit_logs') {
        $response = '';
        $api_key = SucuriScanAPI::getCloudproxyKey();

        if ($api_key) {
            $query = SucuriScanRequest::post(':query');
            $month = SucuriScanRequest::post(':month');
            $year = SucuriScanRequest::post(':year');
            $day = SucuriScanRequest::post(':day');
            $limit = 50;
            $offset = 1;

            if ($year && $month && $day) {
                $date = sprintf('%s-%s-%s', $year, $month, $day);
            } else {
                $date = date('Y-m-d');
            }

            $auditlogs = SucuriScanAPI::firewallAuditLogs(
                $api_key,
                $date, /* Retrieve the data from this date. */
                $query, /* Filter the data to match this query. */
                $limit, /* Retrieve this maximum of data. */
                $offset /* Retrieve the data from this point. */
            );

            if ($auditlogs && array_key_exists('total_lines', $auditlogs)) {
                $response = sucuriscan_firewall_auditlogs_entries($auditlogs['access_logs']);

                if (empty($response)) {
                    $response = '<tr><td>No data available for this filter.</td></tr>';
                }
            }
        } else {
            SucuriScanInterface::error('CloudProxy API Key was not found.');
        }

        print($response);
        exit(0);
    }
}

function sucuriscan_firewall_auditlogs_entries($entries = array())
{
    $output = '';
    $attributes = array(
        'remote_addr',
        'request_date',
        'request_time',
        'request_timezone',
        'request_method',
        'resource_path',
        'http_protocol',
        'http_status',
        'http_status_title',
        'http_referer',
        'http_user_agent',
        'sucuri_block_code',
        'sucuri_block_reason',
        'request_country_name',
        'request_country_code',
    );

    if (is_array($entries) && !empty($entries)) {
        $counter = 0;

        foreach ($entries as $entry) {
            $data_set = array();
            $data_set['AccessLog.CssClass'] = ($counter % 2 == 0) ? '' : 'alternate';

            foreach ($attributes as $attr) {
                // Generate variable name for the template pseudo-tags.
                $keyname = str_replace('_', "\x20", $attr);
                $keyname = ucwords($keyname);
                $keyname = str_replace("\x20", '', $keyname);
                $keyname = 'AccessLog.' . $keyname;

                // Assign and escape variable value before rendering.
                if (array_key_exists($attr, $entry)) {
                    $data_set[$keyname] = $entry[$attr];
                } else {
                    $data_set[$keyname] = '';
                }

                // Special cases to convert value to readable data.
                if ($attr == 'resource_path' && $data_set[$keyname] == '/') {
                    $data_set[$keyname] = '/ (root of the website)';
                } elseif ($attr == 'http_referer' && $data_set[$keyname] == '-') {
                    $data_set[$keyname] = '- (no referer)';
                } elseif ($attr == 'request_country_name' && $data_set[$keyname] == '') {
                    $data_set[$keyname] = 'Anonymous';
                }
            }

            $output .= SucuriScanTemplate::getSnippet('firewall-auditlogs', $data_set);
            $counter++;
        }
    }

    return $output;
}

/**
 * Get a list of years, months or days depending of the type specified.
 *
 * @param  string  $type    Either years, months or days.
 * @param  string  $date    Year, month and day selected from the request.
 * @param  boolean $in_html Whether the list should be converted to a HTML select options or not.
 * @return array            Either an array with the expected values, or a HTML code.
 */
function sucuriscan_firewall_dates($type = '', $date = '', $in_html = true)
{
    $options = array();
    $selected = '';
    $pattern = '/^([0-9]{4})\-([0-9]{2})\-([0-9]{2})$/';
    $s_year = '';
    $s_month = '';
    $s_day = '';

    if (@preg_match($pattern, $date, $date_m)) {
        $s_year = $date_m[1];
        $s_month = $date_m[2];
        $s_day = $date_m[3];
    }

    switch ($type) {
        case 'years':
            $selected = $s_year;
            $current_year = (int) date('Y');
            $max_years = 5; /* Maximum number of years to keep the logs. */
            $options = range(($current_year - $max_years), $current_year);
            break;
        case 'months':
            $selected = $s_month;
            $options = array(
                '01' => 'January',
                '02' => 'February',
                '03' => 'March',
                '04' => 'April',
                '05' => 'May',
                '06' => 'June',
                '07' => 'July',
                '08' => 'August',
                '09' => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December',
            );
            break;
        case 'days':
            $options = range(1, 31);
            $selected = $s_day;
            break;
    }

    if ($in_html) {
        $html_options = '';

        foreach ($options as $key => $value) {
            if (is_numeric($value)) {
                $value = str_pad($value, 2, 0, STR_PAD_LEFT);
            }

            if ($type != 'months') {
                $key = $value;
            }

            $selected_tag = ( $key == $selected ) ? 'selected="selected"' : '';
            $html_options .= sprintf('<option value="%s" %s>%s</option>', $key, $selected_tag, $value);
        }

        return $html_options;
    }

    return $options;
}

/**
 * Generate the HTML code for the firewall clear cache panel.
 *
 * @param  string $nonce Identifier of the HTTP request for CSRF protection
 * @return string        The parsed-content of the firewall clear cache panel.
 */
function sucuriscan_firewall_clearcache($nonce)
{
    $params = array();

    if ($nonce) {
        // Flush the cache of the site(s) associated with the API key.
        if (SucuriScanRequest::post(':clear_cache') == 1) {
            $response = SucuriScanAPI::clearCloudproxyCache();

            if ($response) {
                if (isset($response->messages[0])) {
                    // Clear W3 Total Cache if it is installed.
                    if (function_exists('w3tc_flush_all')) {
                        w3tc_flush_all();
                    }

                    SucuriScanInterface::info($response->messages[0]);
                } else {
                    SucuriScanInterface::error('Could not clear the cache of your site, try later again.');
                }
            } else {
                SucuriScanInterface::error('CloudProxy is not enabled on your site, or your API key is invalid.');
            }
        }
    }

    return SucuriScanTemplate::getSection('firewall-clearcache', $params);
}

/**
 * CloudProxy firewall page.
 *
 * It checks whether the WordPress core files are the original ones, and the state
 * of the themes and plugins reporting the availability of updates. It also checks
 * the user accounts under the administrator group.
 *
 * @return void
 */
function sucuriscan_firewall_page()
{
    SucuriScanInterface::check_permissions();

    // Process all form submissions.
    $nonce = SucuriScanInterface::check_nonce();
    sucuriscan_firewall_form_submissions($nonce);

    // Get the dynamic values for the template variables.
    $api_key = SucuriScanAPI::getCloudproxyKey();

    // Page pseudo-variables initialization.
    $params = array(
        'PageTitle' => 'Firewall WAF',
        'Firewall.Settings' => sucuriscan_firewall_settings($api_key),
        'Firewall.AuditLogs' => sucuriscan_firewall_auditlogs($api_key),
        'Firewall.ClearCache' => sucuriscan_firewall_clearcache($nonce),
    );

    echo SucuriScanTemplate::getTemplate('firewall', $params);
}

/**
 * Handle an Ajax request for this specific page.
 *
 * @return mixed.
 */
function sucuriscan_firewall_ajax()
{
    SucuriScanInterface::check_permissions();

    if (SucuriScanInterface::check_nonce()) {
        sucuriscan_firewall_auditlogs_ajax();
    }

    wp_die();
}

/**
 * Process the requests sent by the form submissions originated in the firewall
 * page, all forms must have a nonce field that will be checked against the one
 * generated in the template render function.
 *
 * @return void
 */
function sucuriscan_firewall_form_submissions($nonce)
{
    if ($nonce) {
        // Add and/or Update the Sucuri WAF API Key (do it before anything else).
        $option_name = ':cloudproxy_apikey';
        $api_key = SucuriScanRequest::post($option_name);

        if ($api_key !== false) {
            $api_key = trim($api_key);

            if (SucuriScanAPI::isValidCloudproxyKey($api_key)) {
                SucuriScanOption::update_option($option_name, $api_key);
                SucuriScanInterface::info('CloudProxy API key saved successfully');
                SucuriScanOption::setRevProxy('enable');
                SucuriScanOption::setAddrHeader('HTTP_X_SUCURI_CLIENTIP');
            } else {
                SucuriScanInterface::error('Invalid CloudProxy API key.');
            }
        }

        // Delete CloudProxy API key from the plugin.
        if (SucuriScanRequest::post(':delete_wafkey') !== false) {
            SucuriScanOption::delete_option($option_name);
            SucuriScanInterface::info('CloudProxy API key removed successfully');
            SucuriScanOption::setRevProxy('disable');
            SucuriScanOption::setAddrHeader('REMOTE_ADDR');
        }
    }
}

/**
 * Project hardening library.
 *
 * In computing, hardening is usually the process of securing a system by
 * reducing its surface of vulnerability. A system has a larger vulnerability
 * surface the more functions it fulfills; in principle a single-function system
 * is more secure than a multipurpose one. Reducing available vectors of attack
 * typically includes the removal of unnecessary software, unnecessary usernames
 * or logins and the disabling or removal of unnecessary services.
 *
 * There are various methods of hardening Unix and Linux systems. This may
 * involve, among other measures, applying a patch to the kernel such as Exec
 * Shield or PaX; closing open network ports; and setting up intrusion-detection
 * systems, firewalls and intrusion-prevention systems. There are also hardening
 * scripts and tools like Bastille Linux, JASS for Solaris systems and
 * Apache/PHP Hardener that can, for example, deactivate unneeded features in
 * configuration files or perform various other protective measures.
 */
class SucuriScanHardening extends SucuriScan
{

    /**
     * Returns a list of access control rules for the Apache web server that can be
     * used to deny and allow certain files to be accessed by certain network nodes.
     * Currently supports Apache 2.2 and 2.4 and denies access to all PHP files with
     * any mixed extension case.
     *
     * @return array List of access control rules.
     */
    private static function get_rules()
    {
        return array(
            '<FilesMatch "\.(?i:php)$">',
            '  <IfModule !mod_authz_core.c>',
            '    Order allow,deny',
            '    Deny from all',
            '  </IfModule>',
            '  <IfModule mod_authz_core.c>',
            '    Require all denied',
            '  </IfModule>',
            '</FilesMatch>',
        );
    }

    /**
     * Adds some rules to an existing access control file (or creates it if does not
     * exists) to deny access to all files with certain extension in any mixed case.
     * The permissions to modify the file are checked before anything else, this
     * function is self-contained.
     *
     * @param  string  $directory Valid directory path where to place the access rules.
     * @return boolean            True if the rules are successfully added, false otherwise.
     */
    public static function harden_directory($directory = '')
    {
        if (file_exists($directory)
            && is_writable($directory)
            && is_dir($directory)
        ) {
            $fhandle = false;
            $target = self::htaccess($directory);
            $deny_rules = self::get_rules();

            if (file_exists($target)) {
                self::fix_previous_hardening($directory);
                $fhandle = @fopen($target, 'a');
            } else {
                $fhandle = @fopen($target, 'w');
            }

            if ($fhandle) {
                $rules_str = implode("\n", $deny_rules) . "\n";
                $written = @fwrite($fhandle, $rules_str);
                @fclose($fhandle);

                return (bool) ($written !== false);
            }
        }

        return false;
    }

    /**
     * Deletes some rules from an existing access control file to allow access to
     * all files with certain extension in any mixed case. The file is truncated if
     * after the operation its size is equals to zero.
     *
     * @param  string  $directory Valid directory path where to access rules are.
     * @return boolean            True if the rules are successfully deleted, false otherwise.
     */
    public static function unharden_directory($directory = '')
    {
        if (self::is_hardened($directory)) {
            $deny_rules = self::get_rules();
            $fpath = self::htaccess($directory);
            $content = @file_get_contents($fpath);

            if ($content) {
                $rules_str = implode("\n", $deny_rules);
                $content = str_replace($rules_str, '', $content);
                $written = @file_put_contents($fpath, $content);

                if (filesize($fpath) === 0) {
                    @unlink($fpath);
                }

                return (bool) ($written !== false);
            }
        }

        return false;
    }

    /**
     * Remove the hardening applied in previous versions.
     *
     * @param  string  $directory Valid directory path.
     * @return boolean            True if the access control file was fixed.
     */
    private static function fix_previous_hardening($directory = '')
    {
        $fpath = self::htaccess($directory);
        $content = @file_get_contents($fpath);
        $rules = "<Files *.php>\ndeny from all\n</Files>";

        if ($content) {
            if (strpos($content, $rules) !== false) {
                $content = str_replace($rules, '', $content);
                $written = @file_put_contents($fpath, $content);

                return (bool) ($written !== false);
            }
        }

        return true;
    }

    /**
     * Check whether a directory is hardened or not.
     *
     * @param  string  $directory Valid directory path.
     * @return boolean            True if the directory is hardened, false otherwise.
     */
    public static function is_hardened($directory = '')
    {
        if (file_exists($directory) && is_dir($directory)) {
            $fpath = self::htaccess($directory);

            if (file_exists($fpath) && is_readable($fpath)) {
                $rules = self::get_rules();
                $rules_str = implode("\n", $rules);
                $content = @file_get_contents($fpath);

                if (strpos($content, $rules_str) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function htaccess($folder = '')
    {
        $folder = str_replace(ABSPATH, '', $folder);
        $bpath = rtrim(ABSPATH, DIRECTORY_SEPARATOR);
        $folder_path = $bpath . '/' . $folder;
        $htaccess = $folder_path . '/.htaccess';

        return $htaccess;
    }

    private static function whitelist_rule($file = '')
    {
        $file = str_replace('/', '', $file);
        $file = str_replace('<', '', $file);
        $file = str_replace('>', '', $file);

        return sprintf("<Files %s>\n\x20\x20Allow from all\n</Files>\n", $file);
    }

    public static function whitelist($file = '', $folder = '')
    {
        $htaccess = self::htaccess($folder);

        if (file_exists($htaccess)) {
            if (is_writable($htaccess)) {
                $rules = self::whitelist_rule($file);
                @file_put_contents($htaccess, $rules, FILE_APPEND);
            } else {
                throw new Exception('Access control file is not writable');
            }
        } else {
            throw new Exception('Access control file does not exists');
        }
    }

    public static function dewhitelist($file = '', $folder = '')
    {
        $htaccess = self::htaccess($folder);

        if (file_exists($htaccess)
            && is_readable($htaccess)
            && is_writable($htaccess)
        ) {
            $content = @file_get_contents($htaccess);
            $rules = self::whitelist_rule($file);
            $content = str_replace($rules, '', $content);
            @file_put_contents($htaccess, $content);
        }
    }

    public static function get_whitelisted($folder = '')
    {
        $htaccess = self::htaccess($folder);
        $content = @file_get_contents($htaccess);

        if (@preg_match_all('/<Files (\S+)>/', $content, $matches)) {
            return $matches[1];
        }

        return false;
    }
}

/**
 * Sucuri one-click hardening page.
 *
 * It loads all the functions defined in /lib/hardening.php and shows the forms
 * that the administrator can use to harden multiple parts of the site.
 *
 * @return void
 */
function sucuriscan_hardening_page()
{
    SucuriScanInterface::check_permissions();

    $template_variables = array(
        'Hardening.Panel' => sucuriscan_hardening_panel(),
        'Hardening.Whitelist' => sucuriscan_hardening_whitelist(),
    );

    echo SucuriScanTemplate::getTemplate('hardening', $template_variables);
}

function sucuriscan_hardening_panel()
{
    if (SucuriScanRequest::post(':run_hardening')
        && !SucuriScanInterface::check_nonce()
    ) {
        unset($_POST['sucuriscan_run_hardening']);
    }

    $template_variables = array(
        'PageTitle' => 'Hardening',
        'Hardening.Version' => sucuriscan_harden_version(),
        'Hardening.CloudProxy' => sucuriscan_cloudproxy_enabled(),
        'Hardening.RemoveGenerator' => sucuriscan_harden_removegenerator(),
        'Hardening.NginxPhpFpm' => '',
        'Hardening.Upload' => '',
        'Hardening.WpContent' => '',
        'Hardening.WpIncludes' => '',
        'Hardening.PhpVersion' => sucuriscan_harden_phpversion(),
        'Hardening.SecretKeys' => sucuriscan_harden_secretkeys(),
        'Hardening.Readme' => sucuriscan_harden_readme(),
        'Hardening.AdminUser' => sucuriscan_harden_adminuser(),
        'Hardening.FileEditor' => sucuriscan_harden_fileeditor(),
        'Hardening.DBTables' => sucuriscan_harden_dbtables(),
        'Hardening.ErrorLog' => sucuriscan_harden_errorlog(),
    );

    if (SucuriScan::is_nginx_server() === true) {
        $template_variables['Hardening.NginxPhpFpm'] = sucuriscan_harden_nginx_phpfpm();
    } elseif (SucuriScan::is_iis_server() === true) {
        /* TODO: Include IIS (Internet Information Services) hardening options. */
    } else {
        $template_variables['Hardening.Upload'] = sucuriscan_harden_upload();
        $template_variables['Hardening.WpContent'] = sucuriscan_harden_wpcontent();
        $template_variables['Hardening.WpIncludes'] = sucuriscan_harden_wpincludes();
    }

    return SucuriScanTemplate::getSection('hardening-panel', $template_variables);
}

function sucuriscan_hardening_whitelist()
{
    $template_variables = array(
        'HardeningWhitelist.List' => '',
        'HardeningWhitelist.NoItemsVisibility' => 'visible',
    );
    $allowed_folders = array(
        'wp-includes',
        'wp-content',
        'wp-content/uploads',
    );

    // Add a new file to the hardening whitelist.
    if ($fwhite = SucuriScanRequest::post(':hardening_whitelist')) {
        $folder = SucuriScanRequest::post(':hardening_folder');

        if (in_array($folder, $allowed_folders)) {
            try {
                SucuriScanHardening::whitelist($fwhite, $folder);
                SucuriScanInterface::info('File was whitelisted from the hardening');
            } catch (Exception $e) {
                SucuriScanInterface::error($e->getMessage());
            }
        } else {
            SucuriScanInterface::error('Specified folder is not hardened by this plugin');
        }
    }

    // Remove a file from the hardening whitelist.
    if ($rmfwhite = SucuriScanRequest::post(':hardening_rmfwhite', '_array')) {
        foreach ($rmfwhite as $fpath) {
            $fpath = str_replace('/.*/', '|', $fpath);
            $parts = explode('|', $fpath, 2);
            SucuriScanHardening::dewhitelist($parts[1], $parts[0]);
        }

        SucuriScanInterface::info('Selected files were processed successfully');
    }

    // Read the access control file and retrieve the whitelisted files.
    $counter = 0;
    foreach ($allowed_folders as $folder) {
        $files = SucuriScanHardening::get_whitelisted($folder);

        if ($files !== false) {
            $template_variables['HardeningWhitelist.NoItemsVisibility'] = 'hidden';

            foreach ($files as $file) {
                $css_class = ($counter % 2 === 0) ? '' : 'alternate';
                $fregexp = sprintf('%s/.*/%s', $folder, $file);
                $html = SucuriScanTemplate::getSnippet(
                    'hardening-whitelist',
                    array(
                        'HardeningWhitelist.CssClass' => $css_class,
                        'HardeningWhitelist.Regexp' => $fregexp,
                        'HardeningWhitelist.Folder' => $folder,
                        'HardeningWhitelist.File' => $file,
                    )
                );
                $template_variables['HardeningWhitelist.List'] .= $html;
                $counter++;
            }
        }
    }

    return SucuriScanTemplate::getSection('hardening-whitelist', $template_variables);
}

/**
 * Generate the HTML code necessary to show a form with the options to harden
 * a specific part of the WordPress installation, if the Status variable is
 * set as a positive integer the button is shown as "unharden".
 *
 * @param  string  $title       Title of the panel.
 * @param  integer $status      Either one or zero representing the state of the hardening, one for secure, zero for insecure.
 * @param  string  $type        Name of the hardening option, this will be used through out the form generation.
 * @param  string  $messageok   Message that will be shown if the hardening was executed.
 * @param  string  $messagewarn Message that will be shown if the hardening is not executed.
 * @param  string  $desc        Optional description of the hardening.
 * @param  string  $updatemsg   Optional explanation of the hardening after the submission of the form.
 * @return void
 */
function sucuriscan_harden_status($title = '', $status = 0, $type = '', $messageok = '', $messagewarn = '', $desc = null, $updatemsg = null)
{
    $template_variables = array(
        'Hardening.Title' => $title,
        'Hardening.Description' => '',
        'Hardening.Status' => 'unknown',
        'Hardening.FieldName' => '',
        'Hardening.FieldValue' => '',
        'Hardening.FieldAttributes' => '',
        'Hardening.Information' => '',
        'Hardening.UpdateMessage' => '',
    );

    if (is_null($type)) {
        $type = 'unknown';
        $template_variables['Hardening.FieldAttributes'] = 'disabled="disabled"';
    }

    $template_variables['Hardening.Status'] = (string) $status;

    if ($status === 1) {
        $template_variables['Hardening.FieldName'] = $type . '_unharden';
        $template_variables['Hardening.FieldValue'] = 'Revert hardening';
        $template_variables['Hardening.Information'] = $messageok;
    } elseif ($status === 0) {
        $template_variables['Hardening.FieldName'] = $type;
        $template_variables['Hardening.FieldValue'] = 'Harden';
        $template_variables['Hardening.Information'] = $messagewarn;
    } else {
        $template_variables['Hardening.FieldName'] = '';
        $template_variables['Hardening.FieldValue'] = 'Unavailable';
        $template_variables['Hardening.Information'] = 'Can not be determined.';
        $template_variables['Hardening.FieldAttributes'] = 'disabled="disabled"';
    }

    if (!is_null($desc)) {
        $template_variables['Hardening.Description'] = '<p>' . $desc . '</p>';
    }

    if (!is_null($updatemsg)) {
        $template_variables['Hardening.UpdateMessage'] = '<p>' . $updatemsg . '</p>';
    }

    return SucuriScanTemplate::getSnippet('hardening', $template_variables);
}

/**
 * Check whether the version number of the WordPress installed is the latest
 * version available officially.
 *
 * @return void
 */
function sucuriscan_harden_version()
{
    $site_version = SucuriScan::site_version();
    $updates = get_core_updates();
    $cp = (!is_array($updates) || empty($updates) ? 1 : 0);

    if (isset($updates[0]) && $updates[0] instanceof stdClass) {
        if ($updates[0]->response == 'latest'
            || $updates[0]->response == 'development'
        ) {
            $cp = 1;
        }
    }

    if (strcmp($site_version, '3.7') < 0) {
        $cp = 0;
    }

    $initial_msg = 'Why keep your site updated? WordPress is an open-source
        project which means that with every update the details of the changes made
        to the source code are made public, if there were security fixes then
        someone with malicious intent can use this information to attack any site
        that has not been upgraded.';
    $messageok = sprintf('Your WordPress installation (%s) is current.', $site_version);
    $messagewarn = sprintf(
        'Your current version (%s) is not current.<br>
        <a href="update-core.php" class="button-primary">Update now!</a>',
        $site_version
    );

    return sucuriscan_harden_status('Verify WordPress version', $cp, null, $messageok, $messagewarn, $initial_msg);
}

/**
 * Notify the state of the hardening for the removal of the Generator tag in
 * HTML code printed by WordPress to show the current version number of the
 * installation.
 *
 * @return void
 */
function sucuriscan_harden_removegenerator()
{
    return sucuriscan_harden_status(
        'Remove WordPress version',
        1,
        null,
        'WordPress version properly hidden',
        null,
        'It checks if your WordPress version is being hidden from being displayed '
        .'in the generator tag (enabled by default with this plugin).'
    );
}

function sucuriscan_harden_nginx_phpfpm()
{
    $description = 'It seems that you are using the Nginx web server, if that is
        the case then you will need to add the following code into the global
        <code>nginx.conf</code> file or the virtualhost associated with this
        website. Choose the correct rules for the directories that you want to
        protect. If you encounter errors after restart the web server then revert
        the changes and contact the support team of your hosting company, or read
        the official article about <a href="https://codex.wordpress.org/Nginx">
        WordPress on Nginx</a>.</p>';

    $description .= "<pre class='code'># Block PHP files in uploads directory.\nlocation ~* /(?:uploads|files)/.*\.php$ {\n\x20\x20deny all;\n}</pre>";
    $description .= "<pre class='code'># Block PHP files in content directory.\nlocation ~* /wp-content/.*\.php$ {\n\x20\x20deny all;\n}</pre>";
    $description .= "<pre class='code'># Block PHP files in includes directory.\nlocation ~* /wp-includes/.*\.php$ {\n\x20\x20deny all;\n}</pre>";

    $description .= "<pre class='code'>";
    $description .= "# Block PHP files in uploads, content, and includes directory.\n";
    $description .= "location ~* /(?:uploads|files|wp-content|wp-includes)/.*\.php$ {\n";
    $description .= "\x20\x20deny all;\n";
    $description .= '}</pre>';

    $description .= '<p class="sucuriscan-hidden">';

    return sucuriscan_harden_status(
        'Block PHP files',
        999,
        null,
        null,
        null,
        $description
    );
}

/**
 * Check whether the WordPress upload folder is protected or not.
 *
 * A htaccess file is placed in the upload folder denying the access to any php
 * file that could be uploaded through a vulnerability in a Plugin, Theme or
 * WordPress itself.
 *
 * @return void
 */
function sucuriscan_harden_upload()
{
    $dpath = WP_CONTENT_DIR . '/uploads';

    if (SucuriScanRequest::post(':run_hardening')) {
        if (SucuriScanRequest::post(':harden_upload')) {
            $result = SucuriScanHardening::harden_directory($dpath);

            if ($result === true) {
                $message = 'Hardening applied to the uploads directory';
                SucuriScanEvent::report_notice_event($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Error hardening directory, check the permissions.');
            }
        } elseif (SucuriScanRequest::post(':harden_upload_unharden')) {
            $result = SucuriScanHardening::unharden_directory($dpath);

            if ($result === true) {
                $message = 'Hardening reverted in the uploads directory';
                SucuriScanEvent::report_error_event($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::info('Access file is not writable, check the permissions.');
            }
        }
    }

    // Check whether the directory is already hardened or not.
    $is_hardened = SucuriScanHardening::is_hardened($dpath);
    $cp = ( $is_hardened === true ) ? 1 : 0;

    $description = 'It checks if the uploads directory of this site allows the direct execution'
        . ' of PHP files. It is recommendable to prevent this because someone may try to exploit'
        . ' a vulnerability of a plugin, theme, and/or other PHP-based code located in this'
        . ' directory sending requests directory to these files.</p><p><b>Note:</b> Many plugins'
        . ' and themes in the WordPress marketplace put <em>(insecure)</em> PHP files in this'
        . ' folder for <em>"X"</em> or <em>"Y"</em> reasons, they may not want to change their'
        . ' code to prevent security issues, so you will have to keep this option un-hardened'
        . ' or else you will end up breaking their functionality.';

    return sucuriscan_harden_status(
        'Protect uploads directory',
        $cp,
        'sucuriscan_harden_upload',
        'Upload directory properly hardened',
        'Upload directory not hardened',
        $description,
        null
    );
}

/**
 * Check whether the WordPress content folder is protected or not.
 *
 * A htaccess file is placed in the content folder denying the access to any php
 * file that could be uploaded through a vulnerability in a Plugin, Theme or
 * WordPress itself.
 *
 * @return void
 */
function sucuriscan_harden_wpcontent()
{
    if (SucuriScanRequest::post(':run_hardening')) {
        if (SucuriScanRequest::post(':harden_wpcontent')) {
            $result = SucuriScanHardening::harden_directory(WP_CONTENT_DIR);

            if ($result === true) {
                $message = 'Hardening applied to the content directory';
                SucuriScanEvent::report_notice_event($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Error hardening directory, check the permissions.');
            }
        } elseif (SucuriScanRequest::post(':harden_wpcontent_unharden')) {
            $result = SucuriScanHardening::unharden_directory(WP_CONTENT_DIR);

            if ($result === true) {
                $message = 'Hardening reverted in the content directory';
                SucuriScanEvent::report_error_event($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::info('Access file is not writable, check the permissions.');
            }
        }
    }

    // Check whether the directory is already hardened or not.
    $is_hardened = SucuriScanHardening::is_hardened(WP_CONTENT_DIR);
    $cp = ( $is_hardened === true ) ? 1 : 0;

    $description = 'This option blocks direct access to any PHP file located under the content'
        . ' directory of this site. The note under the <em>"Protect uploads directory"</em>'
        . ' section also applies to this option so you may want to read that part too. If you'
        . ' experience any kind of issues in your site after you apply this hardening go to the'
        . ' content directory using a FTP client or a file manager <em>(generally available in'
        . ' your hosting panel)</em> and rename a file named <code>.htaccess</code>.';

    return sucuriscan_harden_status(
        'Restrict wp-content access',
        $cp,
        'sucuriscan_harden_wpcontent',
        'WP-content directory properly hardened',
        'WP-content directory not hardened',
        $description,
        null
    );
}

/**
 * Check whether the WordPress includes folder is protected or not.
 *
 * A htaccess file is placed in the includes folder denying the access to any php
 * file that could be uploaded through a vulnerability in a Plugin, Theme or
 * WordPress itself, there are some exceptions for some specific files that must
 * be available publicly.
 *
 * @return void
 */
function sucuriscan_harden_wpincludes()
{
    $dpath = ABSPATH . '/wp-includes';

    if (SucuriScanRequest::post(':run_hardening')) {
        if (SucuriScanRequest::post(':harden_wpincludes')) {
            $result = SucuriScanHardening::harden_directory($dpath);

            if ($result === true) {
                $message = 'Hardening applied to the library directory';
                SucuriScanHardening::whitelist('wp-tinymce.php', 'wp-includes');
                SucuriScanHardening::whitelist('ms-files.php', 'wp-includes');
                SucuriScanEvent::report_notice_event($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Error hardening directory, check the permissions.');
            }
        } elseif (SucuriScanRequest::post(':harden_wpincludes_unharden')) {
            $result = SucuriScanHardening::unharden_directory($dpath);

            if ($result === true) {
                $message = 'Hardening reverted in the library directory';
                SucuriScanHardening::dewhitelist('wp-tinymce.php', 'wp-includes');
                SucuriScanHardening::dewhitelist('ms-files.php', 'wp-includes');
                SucuriScanEvent::report_error_event($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::info('Access file is not writable, check the permissions.');
            }
        }
    }

    // Check whether the directory is already hardened or not.
    $is_hardened = SucuriScanHardening::is_hardened($dpath);
    $cp = ( $is_hardened === true ) ? 1 : 0;

    return sucuriscan_harden_status(
        'Restrict wp-includes access',
        $cp,
        'sucuriscan_harden_wpincludes',
        'WP-Includes directory properly hardened',
        'WP-Includes directory not hardened',
        'This option blocks direct PHP access to any file inside <code>wp-includes</code>.',
        null
    );
}

/**
 * Check the version number of the PHP interpreter set to work with the site,
 * is considered that old versions of the PHP interpreter are insecure.
 *
 * @return void
 */
function sucuriscan_harden_phpversion()
{
    $phpv = phpversion();
    $cp = ( strncmp($phpv, '5.', 2) < 0 ) ? 0 : 1;

    return sucuriscan_harden_status(
        'Verify PHP version',
        $cp,
        null,
        'Using an updated version of PHP (' . $phpv . ')',
        'The version of PHP you are using (' . $phpv . ') is not current, not recommended, and/or not supported',
        'This checks if you have the latest version of PHP installed.',
        null
    );
}

/**
 * Check whether the site is behind a secure proxy server or not.
 *
 * @return void
 */
function sucuriscan_cloudproxy_enabled()
{
    $btn_string = '';
    $proxy_info = SucuriScan::is_behind_cloudproxy();
    $status = 1;

    $description = 'A WAF is a protection layer for your web site, blocking all sort of attacks (brute force attempts, '
        . 'DDoS, SQL injections, etc) and helping it remain malware and blacklist free. This test checks if your site is '
        . 'using <a href="https://cloudproxy.sucuri.net/" target="_blank">Sucuri\'s CloudProxy WAF</a> to protect your site.';

    if ($proxy_info === false) {
        $status = 0;
        $btn_string = '<a href="https://goo.gl/qfNkMq" target="_blank" class="button button-primary">Harden</a>';
    }

    return sucuriscan_harden_status(
        'Website Firewall protection',
        $status,
        null,
        'Your website is protected by a Website Firewall (WAF)',
        $btn_string . 'Your website is not protected by a Website Firewall (WAF)',
        $description,
        null
    );
}

/**
 * Check whether the Wordpress configuration file has the security keys recommended
 * to avoid any unauthorized access to the interface.
 *
 * WordPress Security Keys is a set of random variables that improve encryption of
 * information stored in the user’s cookies. There are a total of four security
 * keys: AUTH_KEY, SECURE_AUTH_KEY, LOGGED_IN_KEY, and NONCE_KEY.
 *
 * @return void
 */
function sucuriscan_harden_secretkeys()
{
    $wp_config_path = SucuriScan::get_wpconfig_path();
    $current_keys = SucuriScanOption::get_security_keys();

    if ($wp_config_path) {
        $cp = 1;
        $wp_config_path = SucuriScan::escape($wp_config_path);
        $message = 'The main configuration file was found at: <code>' . $wp_config_path . '</code><br>';

        if (!empty($current_keys['bad']) || !empty($current_keys['missing'])) {
            $cp = 0;
        }
    } else {
        $cp = 0;
        $message = 'The <code>wp-config.php</code> file was not found.<br>';
    }

    $message .= '<br>It checks whether you have proper random keys/salts created for WordPress. A
        <a href="https://codex.wordpress.org/Editing_wp-config.php#Security_Keys" target="_blank">
        secret key</a> makes your site harder to hack and access harder to crack by adding
        random elements to the password. In simple terms, a secret key is a password with
        elements that make it harder to generate enough options to break through your
        security barriers.';
    $messageok = 'Security keys and salts not set, we recommend to create them for security reasons'
        . '<a href="' . SucuriScanTemplate::getUrl('posthack') . '" class="button button-primary">'
        . 'Harden</a>';

    return sucuriscan_harden_status(
        'Security keys',
        $cp,
        null,
        'Security keys and salts properly created',
        $messageok,
        $message,
        null
    );
}

/**
 * Check whether the "readme.html" file is still available in the root of the
 * site or not, which can lead to an attacker to know which version number of
 * Wordpress is being used and search for possible vulnerabilities.
 *
 * @return void
 */
function sucuriscan_harden_readme()
{
    $upmsg = null;
    $cp = is_readable(ABSPATH.'/readme.html') ? 0 : 1;

    // TODO: After hardening create an option to automatically remove this after WP upgrade.
    if (SucuriScanRequest::post(':run_hardening')) {
        if (SucuriScanRequest::post(':harden_readme') && $cp == 0) {
            if (@unlink(ABSPATH.'/readme.html') === false) {
                $upmsg = SucuriScanInterface::error('Unable to remove <code>readme.html</code> file.');
            } else {
                $cp = 1;
                $message = 'Hardening applied to the <code>readme.html</code> file';
                SucuriScanEvent::report_notice_event($message);
                SucuriScanInterface::info($message);
            }
        } elseif (SucuriScanRequest::post(':harden_readme_unharden')) {
            SucuriScanInterface::error('We can not revert this action, you must create the <code>readme.html</code> manually.');
        }
    }

    return sucuriscan_harden_status(
        'Information leakage (readme.html)',
        $cp,
        ( $cp == 0 ? 'sucuriscan_harden_readme' : null ),
        '<code>readme.html</code> file properly deleted',
        '<code>readme.html</code> not deleted and leaking the WordPress version',
        'It checks whether you have the <code>readme.html</code> file available that leaks your WordPress version',
        $upmsg
    );
}

/**
 * Check whether the main administrator user still has the default name "admin"
 * or not, which can lead to an attacker to perform a brute force attack.
 *
 * @return void
 */
function sucuriscan_harden_adminuser()
{
    global $wpdb;

    $upmsg = null;
    $user_query = new WP_User_Query(array(
        'search' => 'admin',
        'fields' => array( 'ID', 'user_login' ),
        'search_columns' => array( 'user_login' ),
    ));
    $results = $user_query->get_results();
    $account_removed = ( count($results) === 0 ? 1 : 0 );

    if ($account_removed === 0) {
        $upmsg = '<i><strong>Notice.</strong> We do not offer an option to automatically change the user name.
        Go to the <a href="'.SucuriScan::admin_url('users.php').'" target="_blank">user list</a> and create
        a new administrator user. Once created, log in as that user and remove the default <code>admin</code>
        (make sure to assign all the admin posts to the new user too).</i>';
    }

    return sucuriscan_harden_status(
        'Default admin account',
        $account_removed,
        null,
        'Default admin user account (admin) not being used',
        'Default admin user account (admin) being used. Not recommended',
        'It checks whether you have the default <code>admin</code> account enabled, security guidelines recommend creating a new admin user name.',
        $upmsg
    );
}

/**
 * Enable or disable the user of the built-in Wordpress file editor.
 *
 * @return void
 */
function sucuriscan_harden_fileeditor()
{
    $file_editor_disabled = defined('DISALLOW_FILE_EDIT') ? DISALLOW_FILE_EDIT : false;

    if (SucuriScanRequest::post(':run_hardening')) {
        $current_time = date('r');
        $wp_config_path = SucuriScan::get_wpconfig_path();

        $wp_config_writable = ( file_exists($wp_config_path) && is_writable($wp_config_path) ) ? true : false;
        $new_wpconfig = $wp_config_writable ? @file_get_contents($wp_config_path) : '';

        if (SucuriScanRequest::post(':harden_fileeditor')) {
            if ($wp_config_writable) {
                if (preg_match('/(.*define\(.DB_COLLATE..*)/', $new_wpconfig, $match)) {
                    $disallow_fileedit_definition = "\n\ndefine('DISALLOW_FILE_EDIT', TRUE); // Sucuri Security: {$current_time}\n";
                    $new_wpconfig = str_replace($match[0], $match[0].$disallow_fileedit_definition, $new_wpconfig);
                }

                $file_editor_disabled = true;
                @file_put_contents($wp_config_path, $new_wpconfig, LOCK_EX);
                $message = 'Hardening applied to the plugin and theme editor';
                SucuriScanEvent::report_notice_event($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('The <code>wp-config.php</code> file is not in the default location
                    or is not writable, you will need to put the following code manually there:
                    <code>define("DISALLOW_FILE_EDIT", TRUE);</code>');
            }
        } elseif (SucuriScanRequest::post(':harden_fileeditor_unharden')) {
            if (preg_match("/(.*define\('DISALLOW_FILE_EDIT', TRUE\);.*)/", $new_wpconfig, $match)) {
                if ($wp_config_writable) {
                    $new_wpconfig = str_replace("\n{$match[1]}", '', $new_wpconfig);
                    file_put_contents($wp_config_path, $new_wpconfig, LOCK_EX);
                    $file_editor_disabled = false;
                    $message = 'Hardening reverted in the plugin and theme editor';
                    SucuriScanEvent::report_error_event($message);
                    SucuriScanInterface::info($message);
                } else {
                    SucuriScanInterface::error('The <code>wp-config.php</code> file is not in the default location
                        or is not writable, you will need to remove the following code manually from there:
                        <code>define("DISALLOW_FILE_EDIT", TRUE);</code>');
                }
            } else {
                SucuriScanInterface::error('The theme and plugin editor are not disabled from the configuration file.');
            }
        }
    }

    $message = 'Occasionally you may wish to disable the plugin or theme editor to prevent overzealous
        users from being able to edit sensitive files and potentially crash the site. Disabling these
        also provides an additional layer of security if a hacker gains access to a well-privileged
        user account.';

    return sucuriscan_harden_status(
        'Plugin &amp; Theme editor',
        ( $file_editor_disabled === false ? 0 : 1 ),
        'sucuriscan_harden_fileeditor',
        'File editor for Plugins and Themes is disabled',
        'File editor for Plugins and Themes is enabled',
        $message,
        null
    );
}

/**
 * Check whether the prefix of each table in the database designated for the site
 * is the same as the default prefix defined by Wordpress "_wp", in that case the
 * "harden" button will generate randomly a new prefix and rename all those tables.
 *
 * @return void
 */
function sucuriscan_harden_dbtables()
{
    global $table_prefix;

    $hardened = ( $table_prefix == 'wp_' ? 0 : 1 );

    return sucuriscan_harden_status(
        'Database table prefix',
        $hardened,
        null,
        'Database table prefix properly modified',
        'Database table set to the default value <code>wp_</code>.',
        'It checks whether your database table prefix has been changed from the default <code>wp_</code>',
        '<strong>Be aware that this hardening procedure can cause your site to go down</strong>'
    );
}

/**
 * Check whether an error_log file exists in the project.
 *
 * @return void
 */
function sucuriscan_harden_errorlog()
{
    $hardened = 1;
    $log_filename = SucuriScan::ini_get('error_log');
    $scan_errorlogs = SucuriScanOption::get_option(':scan_errorlogs');

    $description = 'PHP uses files named as <code>' . $log_filename . '</code> to log errors found in '
        . 'the code, these files may leak sensitive information of your project allowing an attacker '
        . 'to find vulnerabilities in the code. You must use these files to fix any bug while using '
        . 'a development environment, and remove them in production mode.';

    // Search error log files in the project.
    if ($scan_errorlogs != 'disabled') {
        $file_info = new SucuriScanFileInfo();
        $file_info->ignore_files = false;
        $file_info->ignore_directories = false;
        $error_logs = $file_info->find_file($log_filename);
        $total_log_files = count($error_logs);
    } else {
        $hardened = 2;
        $error_logs = array();
        $total_log_files = 0;
        $description .= '<div class="sucuriscan-inline-alert-error"><p>The filesystem scan for error '
            . 'log files is disabled, so even if there are logs in your project they will be not '
            . 'shown here. You can enable the scanner again from the plugin <em>Settings</em> '
            . 'page.</p></div>';
    }

    // Remove every error log file found in the filesystem scan.
    if (SucuriScanRequest::post(':run_hardening')) {
        if (SucuriScanRequest::post(':harden_errorlog')) {
            $removed_logs = 0;
            SucuriScanEvent::report_notice_event(sprintf(
                'Error log files deleted: (multiple entries): %s',
                @implode(',', $error_logs)
            ));

            foreach ($error_logs as $i => $error_log_path) {
                if (unlink($error_log_path)) {
                    unset($error_logs[ $i ]);
                    $removed_logs += 1;
                }
            }

            SucuriScanInterface::info('Error log files deleted <code>' . $removed_logs . ' out of ' . $total_log_files . '</code>');
        }
    }

    // List the error log files in a HTML table.
    if (!empty($error_logs)) {
        $hardened = 0;
        $description .= '</p><ul class="sucuriscan-list-as-table">';

        foreach ($error_logs as $error_log_path) {
            $error_log_path = str_replace(ABSPATH, '/', $error_log_path);
            $description .= '<li>' . SucuriScan::escape($error_log_path) . '</li>';
        }

        $description .= '</ul><p>';
    }

    return sucuriscan_harden_status(
        'Error logs',
        $hardened,
        ( $hardened == 0 ? 'sucuriscan_harden_errorlog' : null ),
        'There are no error log files in your project.',
        'There are ' . $total_log_files . ' error log files in your project.',
        $description,
        null
    );
}

/**
 * WordPress core integrity page.
 *
 * It checks whether the WordPress core files are the original ones, and the state
 * of the themes and plugins reporting the availability of updates. It also checks
 * the user accounts under the administrator group.
 *
 * @return void
 */
function sucuriscan_page()
{
    SucuriScanInterface::check_permissions();

    // Process all form submissions.
    sucuriscan_integrity_form_submissions();

    $params = array(
        'WordpressVersion' => sucuriscan_wordpress_outdated(),
        'CoreFiles' => sucuriscan_core_files(),
        'AuditReports' => sucuriscan_auditreport(),
        'AuditLogs' => sucuriscan_auditlogs(),
    );

    echo SucuriScanTemplate::getTemplate('integrity', $params);
}

/**
 * Handle an Ajax request for this specific page.
 *
 * @return mixed.
 */
function sucuriscan_ajax()
{
    SucuriScanInterface::check_permissions();

    if (SucuriScanInterface::check_nonce()) {
        sucuriscan_core_files_ajax();
    }

    wp_die();
}

/**
 * Print a HTML code with the content of the logs audited by the remote Sucuri
 * API service, this page is part of the monitoring tool.
 *
 * @return void
 */
function sucuriscan_auditlogs()
{
    // Initialize the values for the pagination.
    $max_per_page = SUCURISCAN_AUDITLOGS_PER_PAGE;
    $page_number = SucuriScanTemplate::pageNumber();
    $logs_limit = $page_number * $max_per_page;
    $audit_logs = SucuriScanAPI::getLogs($logs_limit);

    $params = array(
        'PageTitle' => 'Audit Logs',
        'AuditLogs.List' => '',
        'AuditLogs.Count' => 0,
        'AuditLogs.MaxPerPage' => $max_per_page,
        'AuditLogs.NoItemsVisibility' => 'visible',
        'AuditLogs.PaginationVisibility' => 'hidden',
        'AuditLogs.PaginationLinks' => '',
        'AuditLogs.EnableAuditReportVisibility' => 'hidden',
    );

    if ($audit_logs) {
        $counter_i = 0;
        $total_items = count($audit_logs->output_data);
        $iterator_start = ($page_number - 1) * $max_per_page;
        $iterator_end = $total_items;

        if ($audit_logs->total_entries >= $max_per_page
            && SucuriScanOption::is_disabled(':audit_report')
        ) {
            $params['AuditLogs.EnableAuditReportVisibility'] = 'visible';
        }

        for ($i = $iterator_start; $i < $total_items; $i++) {
            if ($counter_i > $max_per_page) {
                break;
            }

            if (isset($audit_logs->output_data[ $i ])) {
                $audit_log = $audit_logs->output_data[ $i ];

                $css_class = ( $counter_i % 2 == 0 ) ? '' : 'alternate';
                $snippet_data = array(
                    'AuditLog.CssClass' => $css_class,
                    'AuditLog.Event' => $audit_log['event'],
                    'AuditLog.EventTitle' => ucfirst($audit_log['event']),
                    'AuditLog.Timestamp' => $audit_log['timestamp'],
                    'AuditLog.DateTime' => SucuriScan::datetime($audit_log['timestamp']),
                    'AuditLog.Account' => $audit_log['account'],
                    'AuditLog.Username' => $audit_log['username'],
                    'AuditLog.RemoteAddress' => $audit_log['remote_addr'],
                    'AuditLog.Message' => $audit_log['message'],
                    'AuditLog.Extra' => '',
                );

                // Print every file_list information item in a separate table.
                if ($audit_log['file_list']) {
                    $css_scrollable = $audit_log['file_list_count'] > 10 ? 'sucuriscan-list-as-table-scrollable' : '';
                    $snippet_data['AuditLog.Extra'] .= '<ul class="sucuriscan-list-as-table ' . $css_scrollable . '">';
                    foreach ($audit_log['file_list'] as $log_extra) {
                        $snippet_data['AuditLog.Extra'] .= '<li>' . SucuriScan::escape($log_extra) . '</li>';
                    }
                    $snippet_data['AuditLog.Extra'] .= '</ul>';
                }

                $params['AuditLogs.List'] .= SucuriScanTemplate::getSnippet('integrity-auditlogs', $snippet_data);
                $counter_i += 1;
            }
        }

        $params['AuditLogs.Count'] = $counter_i;
        $params['AuditLogs.NoItemsVisibility'] = 'hidden';

        if ($total_items > 1) {
            $max_pages = ceil($audit_logs->total_entries / $max_per_page);

            if ($max_pages > SUCURISCAN_MAX_PAGINATION_BUTTONS) {
                $max_pages = SUCURISCAN_MAX_PAGINATION_BUTTONS;
            }

            if ($max_pages > 1) {
                $params['AuditLogs.PaginationVisibility'] = 'visible';
                $params['AuditLogs.PaginationLinks'] = SucuriScanTemplate::pagination(
                    '%%SUCURI.URL.Home%%',
                    $max_per_page * $max_pages,
                    $max_per_page
                );
            }
        }
    }

    return SucuriScanTemplate::getSection('integrity-auditlogs', $params);
}

/**
 * Print a HTML code with the content of the logs audited by the remote Sucuri
 * API service, this page is part of the monitoring tool.
 *
 * @return void
 */
function sucuriscan_auditreport()
{
    $audit_report = false;
    $logs4report = SucuriScanOption::get_option(':logs4report');

    if (SucuriScanOption::is_enabled(':audit_report')) {
        $audit_report = SucuriScanAPI::getAuditReport($logs4report);
    }

    $params = array(
        'PageTitle' => 'Audit Reports',
        'AuditReport.EventColors' => '',
        'AuditReport.EventsPerType' => '',
        'AuditReport.EventsPerLogin' => '',
        'AuditReport.EventsPerUserCategories' => '',
        'AuditReport.EventsPerUserSeries' => '',
        'AuditReport.EventsPerIPAddressCategories' => '',
        'AuditReport.EventsPerIPAddressSeries' => '',
        'AuditReport.Logs4Report' => $logs4report,
    );

    if ($audit_report) {
        $params['AuditReport.EventColors'] = @implode(',', $audit_report['event_colors']);

        // Generate report chart data for the events per type.
        foreach ($audit_report['events_per_type'] as $event => $times) {
            $params['AuditReport.EventsPerType'] .= sprintf(
                "[ '%s', %d ],\n",
                ucwords($event . "\x20events"),
                $times
            );
        }

        // Generate report chart data for the events per login.
        foreach ($audit_report['events_per_login'] as $event => $times) {
            $params['AuditReport.EventsPerLogin'] .= sprintf(
                "[ '%s', %d ],\n",
                ucwords($event . "\x20logins"),
                $times
            );
        }

        // Generate report chart data for the events per user.
        foreach ($audit_report['events_per_user'] as $event => $times) {
            $params['AuditReport.EventsPerUserCategories'] .= sprintf('"%s",', $event);
            $params['AuditReport.EventsPerUserSeries'] .= sprintf('%d,', $times);
        }

        // Generate report chart data for the events per remote address.
        foreach ($audit_report['events_per_ipaddress'] as $event => $times) {
            $params['AuditReport.EventsPerIPAddressCategories'] .= sprintf('"%s",', $event);
            $params['AuditReport.EventsPerIPAddressSeries'] .= sprintf('%d,', $times);
        }

        return SucuriScanTemplate::getSection('integrity-auditreport', $params);
    }

    return '';
}

/**
 * Check whether the WordPress version is outdated or not.
 *
 * @return string Panel with a warning advising that WordPress is outdated.
 */
function sucuriscan_wordpress_outdated()
{
    $site_version = SucuriScan::site_version();
    $updates = get_core_updates();
    $cp = (!is_array($updates) || empty($updates) ? 1 : 0);

    $params = array(
        'WordPress.Version' => $site_version,
        'WordPress.NewVersion' => '0.0.0',
        'WordPress.NewLocale' => 'default',
        'WordPress.UpdateURL' => SucuriScan::admin_url('update-core.php'),
        'WordPress.DownloadURL' => '#',
        'WordPress.UpdateVisibility' => 'hidden',
    );

    if (isset($updates[0])
        && $updates[0] instanceof stdClass
        && property_exists($updates[0], 'version')
        && property_exists($updates[0], 'download')
    ) {
        $params['WordPress.NewVersion'] = $updates[0]->version;
        $params['WordPress.DownloadURL'] = $updates[0]->download;

        if (property_exists($updates[0], 'locale')) {
            $params['WordPress.NewLocale'] = $updates[0]->locale;
        }

        if ($updates[0]->response == 'latest'
            || $updates[0]->response == 'development'
            || $updates[0]->version == $site_version
        ) {
            $cp = 1;
        }
    }

    if ($cp == 0) {
        $params['WordPress.UpdateVisibility'] = 'visible';
    }

    return SucuriScanTemplate::getSection('integrity-wpoutdate', $params);
}

/**
 * Compare the md5sum of the core files in the current site with the hashes hosted
 * remotely in Sucuri servers. These hashes are updated every time a new version
 * of WordPress is released. If the "Send Email" parameter is set the function will
 * send a notification to the administrator with a list of files that were added,
 * modified and/or deleted so far.
 *
 * @return string HTML code with a list of files that were affected.
 */
function sucuriscan_core_files()
{
    $params = array();

    return SucuriScanTemplate::getSection('corefiles-page', $params);
}

function sucuriscan_core_files_ajax()
{
    if (SucuriScanRequest::post('form_action') == 'get_core_files') {
        $response = sucuriscan_core_files_data();

        print($response);
        exit(0);
    }
}

function sucuriscan_core_files_data($send_email = false)
{
    $affected_files = 0;
    $site_version = SucuriScan::site_version();

    $params = array(
        'CoreFiles.List' => '',
        'CoreFiles.ListCount' => 0,
        'CoreFiles.RemoteChecksumsURL' => '',
        'CoreFiles.BadVisibility' => 'hidden',
        'CoreFiles.GoodVisibility' => 'visible',
        'CoreFiles.FailureVisibility' => 'hidden',
        'CoreFiles.NotFixableVisibility' => 'hidden',
    );

    if ($site_version && SucuriScanOption::is_enabled(':scan_checksums')) {
        // Check if there are added, removed, or modified files.
        $latest_hashes = sucuriscan_check_core_integrity($site_version);
        $params['CoreFiles.RemoteChecksumsURL'] =
            'https://api.wordpress.org/core/checksums/1.0/'
            . '?version=' . $site_version . '&locale=en_US';

        if ($latest_hashes) {
            $cache = new SucuriScanCache('integrity');
            $ignored_files = $cache->getAll();
            $counter = 0;

            foreach ($latest_hashes as $list_type => $file_list) {
                if ($list_type == 'stable' || empty($file_list)) {
                    continue;
                }

                foreach ($file_list as $file_info) {
                    $file_path = $file_info['filepath'];
                    $full_filepath = sprintf('%s/%s', rtrim(ABSPATH, '/'), $file_path);

                    // Skip files that were marked as fixed.
                    if ($ignored_files) {
                        // Get the checksum of the base file name.
                        $file_path_checksum = md5($file_path);

                        if (array_key_exists($file_path_checksum, $ignored_files)) {
                            continue;
                        }
                    }

                    // Add extra information to the file list.
                    $css_class = ( $counter % 2 == 0 ) ? '' : 'alternate';
                    $file_size = @filesize($full_filepath);
                    $is_fixable_text = '';

                    // Check whether the file can be fixed automatically or not.
                    if ($file_info['is_fixable'] !== true) {
                        $css_class .= ' sucuriscan-opacity';
                        $is_fixable_text = '(not fixable)';
                        $params['CoreFiles.NotFixableVisibility'] = 'visible';
                    }

                    // Generate the HTML code from the snippet template for this file.
                    $params['CoreFiles.List'] .= SucuriScanTemplate::getSnippet(
                        'corefiles',
                        array(
                            'CoreFiles.CssClass' => $css_class,
                            'CoreFiles.StatusType' => $list_type,
                            'CoreFiles.FilePath' => $file_path,
                            'CoreFiles.FileSize' => $file_size,
                            'CoreFiles.FileSizeHuman' => SucuriScan::human_filesize($file_size),
                            'CoreFiles.FileSizeNumber' => number_format($file_size),
                            'CoreFiles.ModifiedAt' => SucuriScan::datetime($file_info['modified_at']),
                            'CoreFiles.IsNotFixable' => $is_fixable_text,
                        )
                    );
                    $counter += 1;
                    $affected_files += 1;
                }
            }

            if ($counter > 0) {
                $params['CoreFiles.ListCount'] = $counter;
                $params['CoreFiles.GoodVisibility'] = 'hidden';
                $params['CoreFiles.BadVisibility'] = 'visible';
            }
        } else {
            $params['CoreFiles.GoodVisibility'] = 'hidden';
            $params['CoreFiles.BadVisibility'] = 'hidden';
            $params['CoreFiles.FailureVisibility'] = 'visible';
        }
    }

    // Send an email notification with the affected files.
    if ($send_email === true) {
        if ($affected_files > 0) {
            $content = SucuriScanTemplate::getSection('corefiles-notification', $params);
            $sent = SucuriScanEvent::notify_event('scan_checksums', $content);

            return $sent;
        }

        return false;
    }

    return SucuriScanTemplate::getSection('corefiles', $params);
}

/**
 * Process the requests sent by the form submissions originated in the integrity
 * page, all forms must have a nonce field that will be checked against the one
 * generated in the template render function.
 *
 * @return void
 */
function sucuriscan_integrity_form_submissions()
{
    if (SucuriScanInterface::check_nonce()) {
        // Force the execution of the filesystem scanner.
        if (SucuriScanRequest::post(':force_scan') !== false) {
            SucuriScanEvent::notify_event('plugin_change', 'Filesystem scan forced at: ' . date('r'));
            SucuriScanEvent::filesystem_scan(true);
            sucuriscan_core_files_data(true);
        }

        // Restore, Remove, Mark as fixed the core files.
        $action = SucuriScanRequest::post(':integrity_action');

        if ($action !== false) {
            if (SucuriScanRequest::post(':process_form') == 1) {
                if ($action == 'fixed'
                    || $action == 'delete'
                    || $action == 'restore'
                ) {
                    $cache = new SucuriScanCache('integrity');
                    $core_files = SucuriScanRequest::post(':corefiles', '_array');
                    $files_selected = count($core_files);
                    $files_affected = array();
                    $files_processed = 0;
                    $action_titles = array(
                        'restore' => 'Core file restored',
                        'delete' => 'Non-core file deleted',
                        'fixed' => 'Core file marked as fixed',
                    );

                    if ($core_files) {
                        $delimiter = '@';
                        $parts_count = 2;

                        foreach ($core_files as $file_meta) {
                            if (strpos($file_meta, $delimiter)) {
                                $parts = explode($delimiter, $file_meta, $parts_count);

                                if (count($parts) === $parts_count) {
                                    $file_path = $parts[1];
                                    $status_type = $parts[0];

                                    // Do not use realpath as the file may not exists.
                                    $full_path = ABSPATH . '/' . $file_path;

                                    switch ($action) {
                                        case 'restore':
                                            $file_content = SucuriScanAPI::getOriginalCoreFile($file_path);
                                            if ($file_content) {
                                                $restored = @file_put_contents($full_path, $file_content);
                                                $files_processed += ($restored ? 1 : 0);
                                                $files_affected[] = $full_path;
                                            }
                                            break;
                                        case 'fixed':
                                            $cache_key = md5($file_path);
                                            $cache_value = array(
                                                'file_path' => $file_path,
                                                'file_status' => $status_type,
                                                'ignored_at' => time(),
                                            );
                                            $cached = $cache->add($cache_key, $cache_value);
                                            $files_processed += ($cached ? 1 : 0);
                                            $files_affected[] = $full_path;
                                            break;
                                        case 'delete':
                                            if (@unlink($full_path)) {
                                                $files_processed += 1;
                                                $files_affected[] = $full_path;
                                            }
                                            break;
                                    }
                                }
                            }
                        }

                        // Report files affected as a single event.
                        if (!empty($files_affected)) {
                            $message_tpl = (count($files_affected) > 1)
                                ? '%s: (multiple entries): %s'
                                : '%s: %s';
                            $message = sprintf(
                                $message_tpl,
                                $action_titles[$action],
                                @implode(',', $files_affected)
                            );

                            switch ($action) {
                                case 'restore':
                                    SucuriScanEvent::report_info_event($message);
                                    break;
                                case 'delete':
                                    SucuriScanEvent::report_notice_event($message);
                                    break;
                                case 'fixed':
                                    SucuriScanEvent::report_warning_event($message);
                                    break;
                            }
                        }

                        SucuriScanInterface::info(sprintf(
                            '<b>%d</b> out of <b>%d</b> files were successfully processed.',
                            $files_processed,
                            $files_selected
                        ));
                    } else {
                        SucuriScanInterface::error('No files were selected.');
                    }
                } else {
                    SucuriScanInterface::error('Action requested is not supported.');
                }
            } else {
                SucuriScanInterface::error('You need to confirm that you understand the risk of this operation.');
            }
        }
    }
}

/**
 * Retrieve a list of md5sum and last modification time of all the files in the
 * folder specified. This is a recursive function.
 *
 * @param  string  $dir       The base path where the scanning will start.
 * @param  boolean $recursive Either TRUE or FALSE if the scan should be performed recursively.
 * @return array              List of arrays containing the md5sum and last modification time of the files found.
 */
function sucuriscan_get_integrity_tree($dir = './', $recursive = false)
{
    $abs_path = rtrim(ABSPATH, '/');

    $file_info = new SucuriScanFileInfo();
    $file_info->ignore_files = false;
    $file_info->ignore_directories = false;
    $file_info->run_recursively = $recursive;
    $file_info->scan_interface = SucuriScanOption::get_option(':scan_interface');
    $integrity_tree = $file_info->get_directory_tree_md5($dir, true);

    if (!$integrity_tree) {
        $integrity_tree = array();
    }

    return $integrity_tree;
}

/**
 * Check whether the core WordPress files where modified, removed or if any file
 * was added to the core folders. This function returns an associative array with
 * these keys:
 *
 * <ul>
 *   <li>modified: Files with a different checksum according to the official WordPress archives,</li>
 *   <li>stable: Files with the same checksums than the official files,</li>
 *   <li>removed: Official files which are not present in the local project,</li>
 *   <li>added: Files present in the local project but not in the official WordPress packages.</li>
 * </ul>
 *
 * @param  integer $version Valid version number of the WordPress project.
 * @return array            Associative array with these keys: modified, stable, removed, added.
 */
function sucuriscan_check_core_integrity($version = 0)
{
    $latest_hashes = SucuriScanAPI::getOfficialChecksums($version);
    $base_content_dir = defined('WP_CONTENT_DIR')
        ? basename(rtrim(WP_CONTENT_DIR, '/'))
        : '';

    if (!$latest_hashes) {
        return false;
    }

    $output = array(
        'added' => array(),
        'removed' => array(),
        'modified' => array(),
        'stable' => array(),
    );

    // Get current filesystem tree.
    $wp_top_hashes = sucuriscan_get_integrity_tree(ABSPATH, false);
    $wp_admin_hashes = sucuriscan_get_integrity_tree(ABSPATH . 'wp-admin', true);
    $wp_includes_hashes = sucuriscan_get_integrity_tree(ABSPATH . 'wp-includes', true);
    $wp_core_hashes = array_merge($wp_top_hashes, $wp_admin_hashes, $wp_includes_hashes);

    // Compare remote and local checksums and search removed files.
    foreach ($latest_hashes as $file_path => $remote_checksum) {
        if (sucuriscan_ignore_integrity_filepath($file_path)) {
            continue;
        }

        $full_filepath = sprintf('%s/%s', ABSPATH, $file_path);

        // Patch for custom content directory path.
        if (!file_exists($full_filepath)
            && strpos($file_path, 'wp-content') !== false
            && defined('WP_CONTENT_DIR')
        ) {
            $file_path = str_replace('wp-content', $base_content_dir, $file_path);
            $dir_content_dir = dirname(rtrim(WP_CONTENT_DIR, '/'));
            $full_filepath = sprintf('%s/%s', $dir_content_dir, $file_path);
        }

        // Check whether the official file exists or not.
        if (file_exists($full_filepath)) {
            $local_checksum = @md5_file($full_filepath);

            if ($local_checksum !== false
                && $local_checksum === $remote_checksum
            ) {
                $output['stable'][] = array(
                    'filepath' => $file_path,
                    'is_fixable' => false,
                    'modified_at' => 0,
                );
            } else {
                $modified_at = @filemtime($full_filepath);
                $is_fixable = (bool) is_writable($full_filepath);
                $output['modified'][] = array(
                    'filepath' => $file_path,
                    'is_fixable' => $is_fixable,
                    'modified_at' => $modified_at,
                );
            }
        } else {
            $is_fixable = is_writable(dirname($full_filepath));
            $output['removed'][] = array(
                'filepath' => $file_path,
                'is_fixable' => $is_fixable,
                'modified_at' => 0,
            );
        }
    }

    // Search added files (files not common in a normal wordpress installation).
    foreach ($wp_core_hashes as $file_path => $extra_info) {
        $file_path = str_replace(DIRECTORY_SEPARATOR, '/', $file_path);
        $file_path = @preg_replace('/^\.\/(.*)/', '$1', $file_path);

        if (sucuriscan_ignore_integrity_filepath($file_path)) {
            continue;
        }

        if (!array_key_exists($file_path, $latest_hashes)) {
            $full_filepath = ABSPATH . '/' . $file_path;
            $modified_at = @filemtime($full_filepath);
            $is_fixable = (bool) is_writable($full_filepath);
            $output['added'][] = array(
                'filepath' => $file_path,
                'is_fixable' => $is_fixable,
                'modified_at' => $modified_at,
            );
        }
    }

    return $output;
}

/**
 * Ignore irrelevant files and directories from the integrity checking.
 *
 * @param  string  $file_path File path that will be compared.
 * @return boolean            TRUE if the file should be ignored, FALSE otherwise.
 */
function sucuriscan_ignore_integrity_filepath($file_path = '')
{
    global $wp_local_package;

    // List of files that will be ignored from the integrity checking.
    $ignore_files = array(
        '^sucuri-[0-9a-z\-]+\.php$',
        '^\S+-sucuri-db-dump-gzip-[0-9]{10}-[0-9a-z]{32}\.gz$',
        '^favicon\.ico$',
        '^php\.ini$',
        '^\.htaccess$',
        '^wp-includes\/\.htaccess$',
        '^wp-admin\/setup-config\.php$',
        '^wp-(config|pass|rss|feed|register|atom|commentsrss2|rss2|rdf)\.php$',
        '^wp-content\/(themes|plugins)\/.+', // TODO: Add the popular themes/plugins integrity checks.
        '^sitemap\.xml($|\.gz)$',
        '^readme\.html$',
        '^(503|404)\.php$',
        '^500\.(shtml|php)$',
        '^40[0-9]\.shtml$',
        '^([^\/]*)\.(pdf|css|txt)$',
        '^google[0-9a-z]{16}\.html$',
        '^pinterest-[0-9a-z]{5}\.html$',
        '(^|\/)error_log$',
    );

    /**
     * Ignore i18n files.
     *
     * Sites with i18n have differences compared with the official English version
     * of the project, basically they have files with new variables specifying the
     * language that will be used in the admin panel, site options, and emails.
     */
    if (isset($wp_local_package)
        && $wp_local_package != 'en_US'
    ) {
        $ignore_files[] = 'wp-includes\/version\.php';
        $ignore_files[] = 'wp-config-sample\.php';
    }

    // Determine whether a file must be ignored from the integrity checks or not.
    foreach ($ignore_files as $ignore_pattern) {
        if (@preg_match('/'.$ignore_pattern.'/', $file_path)) {
            return true;
        }
    }

    return false;
}

/**
 * Generate and print the HTML code for the Post-Hack page.
 *
 * @return void
 */
function sucuriscan_posthack_page()
{
    SucuriScanInterface::check_permissions();

    $process_form = sucuriscan_posthack_process_form();

    // Page pseudo-variables initialization.
    $params = array(
        'PageTitle' => 'Post-Hack',
        'UpdateSecretKeys' => sucuriscan_update_secret_keys($process_form),
        'ResetPassword' => sucuriscan_posthack_users($process_form),
        'ResetPlugins' => sucuriscan_posthack_plugins($process_form),
    );

    echo SucuriScanTemplate::getTemplate('posthack', $params);
}

/**
 * Handle an Ajax request for this specific page.
 *
 * @return mixed.
 */
function sucuriscan_posthack_ajax()
{
    SucuriScanInterface::check_permissions();

    if (SucuriScanInterface::check_nonce()) {
        sucuriscan_posthack_plugins_ajax();
    }

    wp_die();
}

/**
 * Check whether the "I understand this operation" checkbox was marked or not.
 *
 * @return boolean TRUE if a form submission should be processed, FALSE otherwise.
 */
function sucuriscan_posthack_process_form()
{
    $process_form = SucuriScanRequest::post(':process_form', '(0|1)');

    if (SucuriScanInterface::check_nonce()
        && $process_form !== false
    ) {
        if ($process_form === '1') {
            return true;
        } else {
            SucuriScanInterface::error('You need to confirm that you understand the risk of this operation.');
        }
    }

    return false;
}

/**
 * Update the WordPress secret keys.
 *
 * @param  $process_form Whether a form was submitted or not.
 * @return string        HTML code with the information of the process.
 */
function sucuriscan_update_secret_keys($process_form = false)
{
    $params = array(
        'WPConfigUpdate.Visibility' => 'hidden',
        'WPConfigUpdate.NewConfig' => '',
        'SecurityKeys.List' => '',
    );

    // Update all WordPress secret keys.
    if ($process_form && SucuriScanRequest::post(':update_wpconfig', '1')) {
        $wpconfig_process = SucuriScanEvent::set_new_config_keys();

        if ($wpconfig_process) {
            $params['WPConfigUpdate.Visibility'] = 'visible';
            SucuriScanEvent::report_notice_event('Generate new security keys');

            if ($wpconfig_process['updated'] === true) {
                SucuriScanInterface::info('Secret keys updated successfully (summary of the operation bellow).');
                $params['WPConfigUpdate.NewConfig'] .= "// Old Keys\n";
                $params['WPConfigUpdate.NewConfig'] .= $wpconfig_process['old_keys_string'];
                $params['WPConfigUpdate.NewConfig'] .= "//\n";
                $params['WPConfigUpdate.NewConfig'] .= "// New Keys\n";
                $params['WPConfigUpdate.NewConfig'] .= $wpconfig_process['new_keys_string'];
            } else {
                SucuriScanInterface::error(
                    '<code>wp-config.php</code> file is not writable, replace the '
                    . 'old configuration file with the new values shown bellow.'
                );
                $params['WPConfigUpdate.NewConfig'] = $wpconfig_process['new_wpconfig'];
            }
        } else {
            SucuriScanInterface::error('<code>wp-config.php</code> file was not found in the default location.');
        }
    }

    // Display the current status of the security keys.
    $current_keys = SucuriScanOption::get_security_keys();
    $counter = 0;

    foreach ($current_keys as $key_status => $key_list) {
        foreach ($key_list as $key_name => $key_value) {
            $css_class = ( $counter % 2 == 0 ) ? '' : 'alternate';
            $key_value = SucuriScan::excerpt($key_value, 50);

            switch ($key_status) {
                case 'good':
                    $key_status_text = 'good';
                    $key_status_css_class = 'success';
                    break;
                case 'bad':
                    $key_status_text = 'not randomized';
                    $key_status_css_class = 'warning';
                    break;
                case 'missing':
                    $key_value = '';
                    $key_status_text = 'not set';
                    $key_status_css_class = 'danger';
                    break;
            }

            if (isset($key_status_text)) {
                $params['SecurityKeys.List'] .= SucuriScanTemplate::getSnippet(
                    'posthack-updatesecretkeys',
                    array(
                        'SecurityKey.CssClass' => $css_class,
                        'SecurityKey.KeyName' => $key_name,
                        'SecurityKey.KeyValue' => $key_value,
                        'SecurityKey.KeyStatusText' => $key_status_text,
                        'SecurityKey.KeyStatusCssClass' => $key_status_css_class,
                    )
                );
                $counter++;
            }
        }
    }

    return SucuriScanTemplate::getSection('posthack-updatesecretkeys', $params);
}

/**
 * Display a list of users in a table that will be used to select the accounts
 * where a password reset action will be executed.
 *
 * @param  $process_form Whether a form was submitted or not.
 * @return string        HTML code for a table where a list of user accounts will be shown.
 */
function sucuriscan_posthack_users($process_form = false)
{
    $params = array(
        'ResetPassword.UserList' => '',
        'ResetPassword.PaginationLinks' => '',
        'ResetPassword.PaginationVisibility' => 'hidden',
    );

    // Process the form submission (if any).
    sucuriscan_reset_user_password($process_form);

    // Fill the user list for ResetPassword action.
    $user_list = false;
    $page_number = SucuriScanTemplate::pageNumber();
    $max_per_page = SUCURISCAN_MAX_PAGINATION_BUTTONS;
    $dbquery = new WP_User_Query(array(
        'number' => $max_per_page,
        'offset' => ($page_number - 1) * $max_per_page,
        'fields' => 'all_with_meta',
        'orderby' => 'ID',
    ));

    // Retrieve the results and build the pagination links.
    if ($dbquery) {
        $total_items = $dbquery->get_total();
        $user_list = $dbquery->get_results();

        $params['ResetPassword.PaginationLinks'] = SucuriScanTemplate::pagination(
            '%%SUCURI.URL.Posthack%%#reset-users-password',
            $total_items,
            $max_per_page
        );

        if ($total_items > $max_per_page) {
            $params['ResetPassword.PaginationVisibility'] = 'visible';
        }
    }

    if ($user_list !== false) {
        $counter = 0;

        foreach ($user_list as $user) {
            $user->user_registered_timestamp = strtotime($user->user_registered);
            $user->user_registered_formatted = SucuriScan::datetime($user->user_registered_timestamp);
            $css_class = ( $counter % 2 == 0 ) ? '' : 'alternate';
            $display_username = ( $user->user_login != $user->display_name )
                ? sprintf('%s (%s)', $user->user_login, $user->display_name)
                : $user->user_login;

            $params['ResetPassword.UserList'] .= SucuriScanTemplate::getSnippet(
                'posthack-resetpassword',
                array(
                    'ResetPassword.UserId' => $user->ID,
                    'ResetPassword.Username' => $user->user_login,
                    'ResetPassword.Displayname' => $user->display_name,
                    'ResetPassword.DisplayUsername' => $display_username,
                    'ResetPassword.Email' => $user->user_email,
                    'ResetPassword.Registered' => $user->user_registered_formatted,
                    'ResetPassword.Roles' => @implode(', ', $user->roles),
                    'ResetPassword.CssClass' => $css_class,
                )
            );
            $counter++;
        }
    }

    return SucuriScanTemplate::getSection('posthack-resetpassword', $params);
}

/**
 * Update the password of the user accounts specified.
 *
 * @param  $process_form Whether a form was submitted or not.
 * @return void
 */
function sucuriscan_reset_user_password($process_form = false)
{
    if ($process_form && SucuriScanRequest::post(':reset_password')) {
        $user_identifiers = SucuriScanRequest::post('user_ids', '_array');
        $pwd_changed = array();
        $pwd_not_changed = array();

        if (is_array($user_identifiers) && !empty($user_identifiers)) {
            arsort($user_identifiers);

            foreach ($user_identifiers as $user_id) {
                $user_id = intval($user_id);

                if (SucuriScanEvent::set_new_password($user_id)) {
                    $pwd_changed[] = $user_id;
                } else {
                    $pwd_not_changed[] = $user_id;
                }
            }

            if (!empty($pwd_changed)) {
                $message = 'Password changed for user identifiers <code>' . @implode(', ', $pwd_changed) . '</code>';

                SucuriScanEvent::report_notice_event($message);
                SucuriScanInterface::info($message);
            }

            if (!empty($pwd_not_changed)) {
                SucuriScanInterface::error('Password change failed for users: ' . implode(', ', $pwd_not_changed));
            }
        } else {
            SucuriScanInterface::error('You did not select a user from the list.');
        }
    }
}

/**
 * Reset all the FREE plugins, even if they are not activated.
 *
 * @param  boolean $process_form Whether a form was submitted or not.
 * @return void
 */
function sucuriscan_posthack_plugins($process_form = false)
{
    $params = array(
        'ResetPlugin.PluginList' => '',
        'ResetPlugin.CacheLifeTime' => 'unknown',
    );

    if (defined('SUCURISCAN_GET_PLUGINS_LIFETIME')) {
        $params['ResetPlugin.CacheLifeTime'] = SUCURISCAN_GET_PLUGINS_LIFETIME;
    }

    sucuriscan_posthack_reinstall_plugins($process_form);

    return SucuriScanTemplate::getSection('posthack-resetplugins', $params);
}

/**
 * Process the Ajax request to retrieve the plugins metadata.
 *
 * @return string HTML code for a table with the plugins metadata.
 */
function sucuriscan_posthack_plugins_ajax()
{
    if (SucuriScanRequest::post('form_action') == 'get_plugins_data') {
        $all_plugins = SucuriScanAPI::getPlugins();
        $response = '';
        $counter = 0;

        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $css_class = ( $counter % 2 == 0 ) ? '' : 'alternate';
            $plugin_type_class = ( $plugin_data['PluginType'] == 'free' ) ? 'primary' : 'warning';
            $input_disabled = ( $plugin_data['PluginType'] == 'free' ) ? '' : 'disabled="disabled"';
            $plugin_status = $plugin_data['IsPluginActive'] ? 'active' : 'not active';
            $plugin_status_class = $plugin_data['IsPluginActive'] ? 'success' : 'default';

            $response .= SucuriScanTemplate::getSnippet(
                'posthack-resetplugins',
                array(
                    'ResetPlugin.CssClass' => $css_class,
                    'ResetPlugin.Disabled' => $input_disabled,
                    'ResetPlugin.PluginPath' => $plugin_path,
                    'ResetPlugin.Plugin' => SucuriScan::excerpt($plugin_data['Name'], 35),
                    'ResetPlugin.Version' => $plugin_data['Version'],
                    'ResetPlugin.Type' => $plugin_data['PluginType'],
                    'ResetPlugin.TypeClass' => $plugin_type_class,
                    'ResetPlugin.Status' => $plugin_status,
                    'ResetPlugin.StatusClass' => $plugin_status_class,
                )
            );
            $counter++;
        }

        print( $response );
        exit(0);
    }
}

/**
 * Process the request that will start the execution of the plugin
 * reinstallation, it will check if the plugins submitted are (in fact)
 * installed in the system, then check if they are free download from the
 * WordPress market place, and finally download and install them.
 *
 * @param  boolean $process_form Whether a form was submitted or not.
 * @return void
 */
function sucuriscan_posthack_reinstall_plugins($process_form = false)
{
    if ($process_form && isset($_POST['sucuriscan_reset_plugins'])) {
        include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        include_once(ABSPATH . 'wp-admin/includes/plugin-install.php'); // For plugins_api.

        if ($plugin_list = SucuriScanRequest::post('plugin_path', '_array')) {
            // Create an instance of the FileInfo interface.
            $file_info = new SucuriScanFileInfo();
            $file_info->ignore_files = false;
            $file_info->ignore_directories = false;
            $file_info->skip_directories = false;

            // Get (possible) cached information from the installed plugins.
            $all_plugins = SucuriScanAPI::getPlugins();

            // Loop through all the installed plugins.
            foreach ($_POST['plugin_path'] as $plugin_path) {
                if (array_key_exists($plugin_path, $all_plugins)) {
                    $plugin_data = $all_plugins[ $plugin_path ];

                    // Check if the plugin can be downloaded from the free market.
                    if ($plugin_data['IsFreePlugin'] === true) {
                        $plugin_info = SucuriScanAPI::getRemotePluginData($plugin_data['RepositoryName']);

                        if ($plugin_info) {
                            // First, remove all files/sub-folders from the plugin's directory.
                            if (substr_count($plugin_path, '/') >= 1) {
                                $plugin_directory = dirname(WP_PLUGIN_DIR . '/' . $plugin_path);
                                $file_info->remove_directory_tree($plugin_directory);
                            }

                            // Install a fresh copy of the plugin's files.
                            $upgrader_skin = new Plugin_Installer_Skin();
                            $upgrader = new Plugin_Upgrader($upgrader_skin);
                            $upgrader->install($plugin_info->download_link);
                            SucuriScanEvent::report_notice_event('Plugin re-installed: ' . $plugin_path);
                        } else {
                            SucuriScanInterface::error('Connection with the WordPress plugin market failed.');
                        }
                    }
                }
            }
        } else {
            SucuriScanInterface::error('You did not select a free plugin to reinstall.');
        }
    }
}

/**
 * Generate and print the HTML code for the Last Logins page.
 *
 * This page will contains information of all the logins of the registered users.
 *
 * @return string Last-logings for the administrator accounts.
 */
function sucuriscan_lastlogins_page()
{
    SucuriScanInterface::check_permissions();

    // Reset the file with the last-logins logs.
    if (SucuriScanInterface::check_nonce()
        && SucuriScanRequest::post(':reset_lastlogins') !== false
    ) {
        $file_path = sucuriscan_lastlogins_datastore_filepath();

        if (unlink($file_path)) {
            sucuriscan_lastlogins_datastore_exists();
            SucuriScanInterface::info('Last-Logins logs were reset successfully.');
        } else {
            SucuriScanInterface::error('Could not reset the last-logins logs.');
        }
    }

    // Page pseudo-variables initialization.
    $params = array(
        'PageTitle' => 'Last Logins',
        'LastLogins.Admins' => sucuriscan_lastlogins_admins(),
        'LastLogins.AllUsers' => sucuriscan_lastlogins_all(),
        'LoggedInUsers' => sucuriscan_loggedin_users_panel(),
        'FailedLogins' => sucuriscan_failed_logins_panel(),
    );

    echo SucuriScanTemplate::getTemplate('lastlogins', $params);
}

/**
 * List all the user administrator accounts.
 *
 * @see https://codex.wordpress.org/Class_Reference/WP_User_Query
 *
 * @return void
 */
function sucuriscan_lastlogins_admins()
{
    // Page pseudo-variables initialization.
    $params = array(
        'AdminUsers.List' => '',
    );

    $user_query = new WP_User_Query(array('role' => 'Administrator'));
    $admins = $user_query->get_results();

    foreach ((array) $admins as $admin) {
        $last_logins = sucuriscan_get_logins(5, 0, $admin->ID);
        $admin->lastlogins = $last_logins['entries'];

        $user_snippet = array(
            'AdminUsers.Username' => $admin->user_login,
            'AdminUsers.Email' => $admin->user_email,
            'AdminUsers.LastLogins' => '',
            'AdminUsers.RegisteredAt' => 'Unknown',
            'AdminUsers.UserURL' => SucuriScan::admin_url('user-edit.php?user_id=' . $admin->ID),
            'AdminUsers.NoLastLogins' => 'visible',
            'AdminUsers.NoLastLoginsTable' => 'hidden',
        );

        if (!empty($admin->lastlogins)) {
            $user_snippet['AdminUsers.NoLastLogins'] = 'hidden';
            $user_snippet['AdminUsers.NoLastLoginsTable'] = 'visible';
            $user_snippet['AdminUsers.RegisteredAt'] = 'Unknown';
            $counter = 0;

            foreach ($admin->lastlogins as $i => $lastlogin) {
                if ($i == 0) {
                    $user_snippet['AdminUsers.RegisteredAt'] = SucuriScan::datetime(
                        $lastlogin->user_registered_timestamp
                    );
                }

                $css_class = ($counter % 2 === 0) ? '' : 'alternate';
                $user_snippet['AdminUsers.LastLogins'] .= SucuriScanTemplate::getSnippet(
                    'lastlogins-admins-lastlogin',
                    array(
                        'AdminUsers.RemoteAddr' => $lastlogin->user_remoteaddr,
                        'AdminUsers.Datetime' => SucuriScan::datetime($lastlogin->user_lastlogin_timestamp),
                        'AdminUsers.CssClass' => $css_class,
                    )
                );
                $counter++;
            }
        }

        $params['AdminUsers.List'] .= SucuriScanTemplate::getSnippet('lastlogins-admins', $user_snippet);
    }

    return SucuriScanTemplate::getSection('lastlogins-admins', $params);
}

/**
 * List the last-logins for all user accounts in the site.
 *
 * This page will contains information of all the logins of the registered users.
 *
 * @return string Last-logings for all user accounts.
 */
function sucuriscan_lastlogins_all()
{
    $max_per_page = SUCURISCAN_LASTLOGINS_USERSLIMIT;
    $page_number = SucuriScanTemplate::pageNumber();
    $offset = ($max_per_page * $page_number) - $max_per_page;

    $params = array(
        'UserList' => '',
        'UserList.Limit' => $max_per_page,
        'UserList.Total' => 0,
        'UserList.Pagination' => '',
        'UserList.PaginationVisibility' => 'hidden',
        'UserList.NoItemsVisibility' => 'visible',
    );

    if (!sucuriscan_lastlogins_datastore_is_writable()) {
        $fpath = SucuriScan::escape(sucuriscan_lastlogins_datastore_filepath());
        SucuriScanInterface::error('Last-logins datastore file is not writable: <code>' . $fpath . '</code>');
    }

    $counter = 0;
    $last_logins = sucuriscan_get_logins($max_per_page, $offset);
    $params['UserList.Total'] = $last_logins['total'];

    if ($last_logins['total'] > $max_per_page) {
        $params['UserList.PaginationVisibility'] = 'visible';
    }

    if ($last_logins['total'] > 0) {
        $params['UserList.NoItemsVisibility'] = 'hidden';
    }

    foreach ($last_logins['entries'] as $user) {
        $css_class = ($counter % 2 === 0) ? '' : 'alternate';

        $user_dataset = array(
            'UserList.Number' => $user->line_num,
            'UserList.UserId' => $user->user_id,
            'UserList.Username' => 'Unknown',
            'UserList.Displayname' => '',
            'UserList.Email' => '',
            'UserList.Registered' => '',
            'UserList.RemoteAddr' => $user->user_remoteaddr,
            'UserList.Hostname' => $user->user_hostname,
            'UserList.Datetime' => $user->user_lastlogin,
            'UserList.TimeAgo' => SucuriScan::time_ago($user->user_lastlogin),
            'UserList.UserURL' => SucuriScan::admin_url('user-edit.php?user_id=' . $user->user_id),
            'UserList.CssClass' => $css_class,
        );

        if ($user->user_exists) {
            $user_dataset['UserList.Username'] = $user->user_login;
            $user_dataset['UserList.Displayname'] = $user->display_name;
            $user_dataset['UserList.Email'] = $user->user_email;
            $user_dataset['UserList.Registered'] = $user->user_registered;
        }

        $params['UserList'] .= SucuriScanTemplate::getSnippet('lastlogins-all', $user_dataset);
        $counter++;
    }

    // Generate the pagination for the list.
    $params['UserList.Pagination'] = SucuriScanTemplate::pagination(
        '%%SUCURI.URL.Lastlogins%%',
        $last_logins['total'],
        $max_per_page
    );

    return SucuriScanTemplate::getSection('lastlogins-all', $params);
}

/**
 * Get the filepath where the information of the last logins of all users is stored.
 *
 * @return string Absolute filepath where the user's last login information is stored.
 */
function sucuriscan_lastlogins_datastore_filepath()
{
    return SucuriScan::datastore_folder_path('sucuri-lastlogins.php');
}

/**
 * Check whether the user's last login datastore file exists or not, if not then
 * we try to create the file and check again the success of the operation.
 *
 * @return string Absolute filepath where the user's last login information is stored.
 */
function sucuriscan_lastlogins_datastore_exists()
{
    $fpath = sucuriscan_lastlogins_datastore_filepath();

    if (!file_exists($fpath)) {
        @file_put_contents($fpath, "<?php exit(0); ?>\n", LOCK_EX);
    }

    if (file_exists($fpath)) {
        return $fpath;
    }

    return false;
}

/**
 * Check whether the user's last login datastore file is writable or not, if not
 * we try to set the right permissions and check again the success of the operation.
 *
 * @return boolean Whether the user's last login datastore file is writable or not.
 */
function sucuriscan_lastlogins_datastore_is_writable()
{
    $datastore_filepath = sucuriscan_lastlogins_datastore_exists();

    if ($datastore_filepath) {
        if (!is_writable($datastore_filepath)) {
            @chmod($datastore_filepath, 0644);
        }

        if (is_writable($datastore_filepath)) {
            return $datastore_filepath;
        }
    }

    return false;
}

/**
 * Check whether the user's last login datastore file is readable or not, if not
 * we try to set the right permissions and check again the success of the operation.
 *
 * @return boolean Whether the user's last login datastore file is readable or not.
 */
function sucuriscan_lastlogins_datastore_is_readable()
{
    $datastore_filepath = sucuriscan_lastlogins_datastore_exists();

    if ($datastore_filepath && is_readable($datastore_filepath)) {
        return $datastore_filepath;
    }

    return false;
}

if (!function_exists('sucuri_set_lastlogin')) {
    /**
     * Add a new user session to the list of last user logins.
     *
     * @param  string $user_login The name of the user account involved in the operation.
     * @return void
     */
    function sucuriscan_set_lastlogin($user_login = '')
    {
        $datastore_filepath = sucuriscan_lastlogins_datastore_is_writable();

        if ($datastore_filepath) {
            $current_user = get_user_by('login', $user_login);
            $remote_addr = SucuriScan::get_remote_addr();

            $login_info = array(
                'user_id' => $current_user->ID,
                'user_login' => $current_user->user_login,
                'user_remoteaddr' => $remote_addr,
                'user_hostname' => @gethostbyaddr($remote_addr),
                'user_lastlogin' => current_time('mysql')
            );

            @file_put_contents($datastore_filepath, json_encode($login_info)."\n", FILE_APPEND);
        }
    }
    add_action('wp_login', 'sucuriscan_set_lastlogin', 50);
}

/**
 * Retrieve the list of all the user logins from the datastore file.
 *
 * The results of this operation can be filtered by specific user identifiers,
 * or limiting the quantity of entries.
 *
 * @param  integer $limit   How many entries will be returned from the operation.
 * @param  integer $offset  Initial point where the logs will be start counting.
 * @param  integer $user_id Optional user identifier to filter the results.
 * @return array            The list of all the user logins, and total of entries registered.
 */
function sucuriscan_get_logins($limit = 10, $offset = 0, $user_id = 0)
{
    $datastore_filepath = sucuriscan_lastlogins_datastore_is_readable();
    $last_logins = array(
        'total' => 0,
        'entries' => array(),
    );

    if ($datastore_filepath) {
        $parsed_lines = 0;
        $data_lines = SucuriScanFileInfo::file_lines($datastore_filepath);

        if ($data_lines) {
            /**
             * This count will not be 100% accurate considering that we are checking the
             * syntax of each line in the loop bellow, there may be some lines without the
             * right syntax which will differ from the total entries returned, but there's
             * not other EASY way to do this without affect the performance of the code.
             *
             * @var integer
             */
            $total_lines = count($data_lines);
            $last_logins['total'] = $total_lines;

            // Get a list with the latest entries in the first positions.
            $reversed_lines = array_reverse($data_lines);

            /**
             * Only the user accounts with administrative privileges can see the logs of all
             * the users, for the rest of the accounts they will only see their own logins.
             *
             * @var object
             */
            $current_user = wp_get_current_user();
            $is_admin_user = (bool) current_user_can('manage_options');

            for ($i = $offset; $i < $total_lines; $i++) {
                $line = $reversed_lines[$i] ? trim($reversed_lines[$i]) : '';

                // Check if the data is serialized (which we will consider as insecure).
                if (SucuriScan::is_serialized($line)) {
                    $last_login = false; /* Do not unserialize; is unsafe. */
                } else {
                    $last_login = @json_decode($line, true);
                }

                if ($last_login) {
                    $last_login['user_lastlogin_timestamp'] = strtotime($last_login['user_lastlogin']);
                    $last_login['user_registered_timestamp'] = 0;

                    // Only administrators can see all login stats.
                    if (!$is_admin_user && $current_user->user_login != $last_login['user_login']) {
                        continue;
                    }

                    // Filter the user identifiers using the value passed tot his function.
                    if ($user_id > 0 && $last_login['user_id'] != $user_id) {
                        continue;
                    }

                    // Get the WP_User object and add extra information from the last-login data.
                    $last_login['user_exists'] = false;
                    $user_account = get_userdata($last_login['user_id']);

                    if ($user_account) {
                        $last_login['user_exists'] = true;

                        foreach ($user_account->data as $var_name => $var_value) {
                            $last_login[ $var_name ] = $var_value;

                            if ($var_name == 'user_registered') {
                                $last_login['user_registered_timestamp'] = strtotime($var_value);
                            }
                        }
                    }

                    $last_login['line_num'] = $i + 1;
                    $last_logins['entries'][] = (object) $last_login;
                    $parsed_lines += 1;
                } else {
                    $last_logins['total'] -= 1;
                }

                if (@preg_match('/^[0-9]+$/', $limit) && $limit > 0) {
                    if ($parsed_lines >= $limit) {
                        break;
                    }
                }
            }
        }
    }

    return $last_logins;
}

if (!function_exists('sucuri_login_redirect')) {
    /**
     * Hook for the wp-login action to redirect the user to a specific URL after
     * his successfully login to the administrator interface.
     *
     * @param  string  $redirect_to URL where the browser must be originally redirected to, set by WordPress itself.
     * @param  object  $request     Optional parameter set by WordPress itself through the event triggered.
     * @param  boolean $user        WordPress user object with the information of the account involved in the operation.
     * @return string               URL where the browser must be redirected to.
     */
    function sucuriscan_login_redirect($redirect_to = '', $request = null, $user = false)
    {
        $login_url = !empty($redirect_to) ? $redirect_to : SucuriScan::admin_url();

        if ($user instanceof WP_User
            && in_array('administrator', $user->roles)
            && SucuriScanOption::is_enabled(':lastlogin_redirection')
        ) {
            $login_url = add_query_arg('sucuriscan_lastlogin', 1, $login_url);
        }

        return $login_url;
    }

    if (SucuriScanOption::is_enabled(':lastlogin_redirection')) {
        add_filter('login_redirect', 'sucuriscan_login_redirect', 10, 3);
    }
}

if (!function_exists('sucuri_get_user_lastlogin')) {
    /**
     * Display the last user login at the top of the admin interface.
     *
     * @return void
     */
    function sucuriscan_get_user_lastlogin()
    {
        if (current_user_can('manage_options')
            && SucuriScanRequest::get(':lastlogin', '1')
        ) {
            $current_user = wp_get_current_user();

            // Select the penultimate entry, not the last one.
            $last_logins = sucuriscan_get_logins(2, 0, $current_user->ID);

            if (isset($last_logins['entries'][1])) {
                $row = $last_logins['entries'][1];
                $page_url = SucuriScanTemplate::getUrl('lastlogins');
                $message = sprintf(
                    'Last login was at <b>%s</b> from <b>%s</b> <em>(%s)</em>',
                    SucuriScan::datetime($row->user_lastlogin_timestamp),
                    SucuriScan::escape($row->user_remoteaddr),
                    SucuriScan::escape($row->user_hostname)
                );
                $message .= "\x20(<a href='" . $page_url . "'>view all logs</a>)";
                SucuriScanInterface::info($message);
            }
        }
    }

    add_action('admin_notices', 'sucuriscan_get_user_lastlogin');
}

/**
 * Print a list of all the registered users that are currently in session.
 *
 * @return string The HTML code displaying a list of all the users logged in at the moment.
 */
function sucuriscan_loggedin_users_panel()
{
    // Get user logged in list.
    $params = array(
        'LoggedInUsers.List' => '',
        'LoggedInUsers.Total' => 0,
    );

    $logged_in_users = sucuriscan_get_online_users(true);

    if (is_array($logged_in_users) && !empty($logged_in_users)) {
        $params['LoggedInUsers.Total'] = count($logged_in_users);
        $counter = 0;

        foreach ((array) $logged_in_users as $logged_in_user) {
            $counter++;
            $logged_in_user['last_activity_datetime'] = SucuriScan::datetime($logged_in_user['last_activity']);
            $logged_in_user['user_registered_datetime'] = SucuriScan::datetime(strtotime($logged_in_user['user_registered']));

            $params['LoggedInUsers.List'] .= SucuriScanTemplate::getSnippet(
                'lastlogins-loggedin',
                array(
                    'LoggedInUsers.Id' => $logged_in_user['user_id'],
                    'LoggedInUsers.UserURL' => SucuriScan::admin_url('user-edit.php?user_id=' . $logged_in_user['user_id']),
                    'LoggedInUsers.UserLogin' => $logged_in_user['user_login'],
                    'LoggedInUsers.UserEmail' => $logged_in_user['user_email'],
                    'LoggedInUsers.LastActivity' => $logged_in_user['last_activity_datetime'],
                    'LoggedInUsers.Registered' => $logged_in_user['user_registered_datetime'],
                    'LoggedInUsers.RemoveAddr' => $logged_in_user['remote_addr'],
                    'LoggedInUsers.CssClass' => ($counter % 2 === 0) ? '' : 'alternate',
                )
            );
        }
    }

    return SucuriScanTemplate::getSection('lastlogins-loggedin', $params);
}

/**
 * Get a list of all the registered users that are currently in session.
 *
 * @param  boolean $add_current_user Whether the current user should be added to the list or not.
 * @return array                     List of registered users currently in session.
 */
function sucuriscan_get_online_users($add_current_user = false)
{
    $users = array();

    if (SucuriScan::is_multisite()) {
        $users = get_site_transient('online_users');
    } else {
        $users = get_transient('online_users');
    }

    // If not online users but current user is logged in, add it to the list.
    if (empty($users) && $add_current_user) {
        $current_user = wp_get_current_user();

        if ($current_user->ID > 0) {
            sucuriscan_set_online_user($current_user->user_login, $current_user);

            return sucuriscan_get_online_users();
        }
    }

    return $users;
}

/**
 * Update the list of the registered users currently in session.
 *
 * Useful when you are removing users and need the list of the remaining users.
 *
 * @param  array   $logged_in_users List of registered users currently in session.
 * @return boolean                  Either TRUE or FALSE representing the success or fail of the operation.
 */
function sucuriscan_save_online_users($logged_in_users = array())
{
    $expiration = 30 * 60;

    if (SucuriScan::is_multisite()) {
        return set_site_transient('online_users', $logged_in_users, $expiration);
    } else {
        return set_transient('online_users', $logged_in_users, $expiration);
    }
}

if (!function_exists('sucuriscan_unset_online_user_on_logout')) {
    /**
     * Remove a logged in user from the list of registered users in session when
     * the logout page is requested.
     *
     * @return void
     */
    function sucuriscan_unset_online_user_on_logout()
    {
        $remote_addr = SucuriScan::get_remote_addr();
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        sucuriscan_unset_online_user($user_id, $remote_addr);
    }

    add_action('wp_logout', 'sucuriscan_unset_online_user_on_logout');
}

/**
 * Remove a logged in user from the list of registered users in session using
 * the user identifier and the ip address of the last computer used to login.
 *
 * @param  integer $user_id     User identifier of the account that will be logged out.
 * @param  integer $remote_addr IP address of the computer where the user logged in.
 * @return boolean              Either TRUE or FALSE representing the success or fail of the operation.
 */
function sucuriscan_unset_online_user($user_id = 0, $remote_addr = 0)
{
    $logged_in_users = sucuriscan_get_online_users();

    // Remove the specified user identifier from the list.
    if (is_array($logged_in_users) && !empty($logged_in_users)) {
        foreach ($logged_in_users as $i => $user) {
            if ($user['user_id'] == $user_id
                && strcmp($user['remote_addr'], $remote_addr) == 0
            ) {
                unset($logged_in_users[ $i ]);
                break;
            }
        }
    }

    return sucuriscan_save_online_users($logged_in_users);
}

if (!function_exists('sucuriscan_set_online_user')) {
    /**
     * Add an user account to the list of registered users in session.
     *
     * @param  string  $user_login The name of the user account that just logged in the site.
     * @param  boolean $user       The WordPress object containing all the information associated to the user.
     * @return void
     */
    function sucuriscan_set_online_user($user_login = '', $user = false)
    {
        if ($user) {
            // Get logged in user information.
            $current_user = ($user instanceof WP_User) ? $user : wp_get_current_user();
            $current_user_id = $current_user->ID;
            $remote_addr = SucuriScan::get_remote_addr();
            $current_time = current_time('timestamp');
            $logged_in_users = sucuriscan_get_online_users();

            // Build the dataset array that will be stored in the transient variable.
            $current_user_info = array(
                'user_id' => $current_user_id,
                'user_login' => $current_user->user_login,
                'user_email' => $current_user->user_email,
                'user_registered' => $current_user->user_registered,
                'last_activity' => $current_time,
                'remote_addr' => $remote_addr,
            );

            if (!is_array($logged_in_users) || empty($logged_in_users)) {
                $logged_in_users = array( $current_user_info );
                sucuriscan_save_online_users($logged_in_users);
            } else {
                $do_nothing = false;
                $update_existing = false;
                $item_index = 0;

                // Check if the user is already in the logged-in-user list and update it if is necessary.
                foreach ($logged_in_users as $i => $user) {
                    if ($user['user_id'] == $current_user_id
                        && strcmp($user['remote_addr'], $remote_addr) == 0
                    ) {
                        if ($user['last_activity'] < ($current_time - (15 * 60))) {
                            $update_existing = true;
                            $item_index = $i;
                            break;
                        } else {
                            $do_nothing = true;
                            break;
                        }
                    }
                }

                if ($update_existing) {
                    $logged_in_users[ $item_index ] = $current_user_info;
                    sucuriscan_save_online_users($logged_in_users);
                } elseif ($do_nothing) {
                    // Do nothing.
                } else {
                    $logged_in_users[] = $current_user_info;
                    sucuriscan_save_online_users($logged_in_users);
                }
            }
        }
    }

    add_action('wp_login', 'sucuriscan_set_online_user', 10, 2);
}

/**
 * Print a list with the failed logins occurred during the last hour.
 *
 * @return string A list with the failed logins occurred during the last hour.
 */
function sucuriscan_failed_logins_panel()
{
    $template_variables = array(
        'FailedLogins.List' => '',
        'FailedLogins.Total' => '',
        'FailedLogins.MaxFailedLogins' => 0,
        'FailedLogins.NoItemsVisibility' => 'visible',
        'FailedLogins.WarningVisibility' => 'visible',
        'FailedLogins.CollectPasswordsVisibility' => 'visible',
        'FailedLogins.PaginationLinks' => '',
        'FailedLogins.PaginationVisibility' => 'hidden',
    );

    // Define variables for the pagination.
    $page_number = SucuriScanTemplate::pageNumber();
    $max_per_page = SUCURISCAN_MAX_PAGINATION_BUTTONS;
    $page_offset = ($page_number - 1) * $max_per_page;
    $page_limit = ($page_offset + $max_per_page);

    $max_failed_logins = SucuriScanOption::get_option(':maximum_failed_logins');
    $notify_bruteforce_attack = SucuriScanOption::get_option(':notify_bruteforce_attack');
    $failed_logins = sucuriscan_get_failed_logins();
    $old_failed_logins = sucuriscan_get_failed_logins(true);

    // Merge the new and old failed logins.
    if (is_array($old_failed_logins) && !empty($old_failed_logins)) {
        if (is_array($failed_logins) && !empty($failed_logins)) {
            $failed_logins = array_merge($failed_logins, $old_failed_logins);
        } else {
            $failed_logins = $old_failed_logins;
        }
    }

    if ($failed_logins) {
        $counter = 0;

        for ($key = $page_offset; $key < $page_limit; $key++) {
            if (array_key_exists($key, $failed_logins['entries'])) {
                $login_data = $failed_logins['entries'][ $key ];
                $css_class = ( $counter % 2 == 0 ) ? '' : 'alternate';
                $wrong_user_password = 'hidden';
                $wrong_user_password_color = 'default';

                if (sucuriscan_collect_wrong_passwords() === true) {
                    if (isset($login_data['user_password']) && !empty($login_data['user_password'])) {
                        $wrong_user_password = $login_data['user_password'];
                        $wrong_user_password_color = 'none';
                    } else {
                        $wrong_user_password = 'empty';
                        $wrong_user_password_color = 'info';
                    }
                }

                $template_variables['FailedLogins.List'] .= SucuriScanTemplate::getSnippet(
                    'lastlogins-failedlogins',
                    array(
                        'FailedLogins.CssClass' => $css_class,
                        'FailedLogins.Num' => $login_data['attempt_count'],
                        'FailedLogins.Username' => $login_data['user_login'],
                        'FailedLogins.RemoteAddr' => $login_data['remote_addr'],
                        'FailedLogins.UserAgent' => $login_data['user_agent'],
                        'FailedLogins.Password' => $wrong_user_password,
                        'FailedLogins.PasswordColor' => $wrong_user_password_color,
                        'FailedLogins.Datetime' => SucuriScan::datetime($login_data['attempt_time']),
                    )
                );
                $counter++;
            }
        }

        if ($counter > 0) {
            $template_variables['FailedLogins.NoItemsVisibility'] = 'hidden';
        }

        $template_variables['FailedLogins.PaginationLinks'] = SucuriScanTemplate::pagination(
            '%%SUCURI.URL.Lastlogins%%#failed-logins',
            $failed_logins['count'],
            $max_per_page
        );

        if ($failed_logins['count'] > $max_per_page) {
            $template_variables['FailedLogins.PaginationVisibility'] = 'visible';
        }
    }

    $template_variables['FailedLogins.MaxFailedLogins'] = $max_failed_logins;

    if ($notify_bruteforce_attack == 'enabled') {
        $template_variables['FailedLogins.WarningVisibility'] = 'hidden';
    }

    if (sucuriscan_collect_wrong_passwords() !== true) {
        $template_variables['FailedLogins.CollectPasswordsVisibility'] = 'hidden';
    }

    return SucuriScanTemplate::getSection('lastlogins-failedlogins', $template_variables);
}

/**
 * Whether or not to collect the password of failed logins.
 *
 * @return boolean TRUE if the password must be collected, FALSE otherwise.
 */
function sucuriscan_collect_wrong_passwords()
{
    return SucuriScanOption::is_enabled(':collect_wrong_passwords');
}

/**
 * Find the full path of the file where the information of the failed logins
 * will be stored, it will be created automatically if does not exists (and if
 * the destination folder has permissions to write). This function can also be
 * used to reset the content of the datastore file.
 *
 * @see sucuriscan_reset_failed_logins()
 *
 * @param  boolean $get_old_logs Whether the old logs will be retrieved or not.
 * @param  boolean $reset        Whether the file will be resetted or not.
 * @return string                The full (relative) path where the file is located.
 */
function sucuriscan_failed_logins_datastore_path($get_old_logs = false, $reset = false)
{
    $file_name = $get_old_logs ? 'sucuri-oldfailedlogins.php' : 'sucuri-failedlogins.php';
    $datastore_path = SucuriScan::datastore_folder_path($file_name);
    $default_content = sucuriscan_failed_logins_default_content();

    // Create the file if it does not exists.
    if (!file_exists($datastore_path) || $reset) {
        @file_put_contents($datastore_path, $default_content, LOCK_EX);
    }

    // Return the datastore path if the file exists (or was created).
    if (file_exists($datastore_path) && is_readable($datastore_path)) {
        return $datastore_path;
    }

    return false;
}

/**
 * Default content of the datastore file where the failed logins are being kept.
 *
 * @return string Default content of the file.
 */
function sucuriscan_failed_logins_default_content()
{
    $default_content = "<?php exit(0); ?>\n";

    return $default_content;
}

/**
 * Read and parse the content of the datastore file where the failed logins are
 * being kept. This function will also calculate the difference in time between
 * the first and last login attempt registered in the file to later decide if
 * there is a brute-force attack in progress (and send an email notification
 * with the report) or reset the file after considering it a normal behavior of
 * the site.
 *
 * @param  boolean $get_old_logs Whether the old logs will be retrieved or not.
 * @return array                 Information and entries gathered from the failed logins datastore file.
 */
function sucuriscan_get_failed_logins($get_old_logs = false)
{
    $datastore_path = sucuriscan_failed_logins_datastore_path($get_old_logs);
    $default_content = sucuriscan_failed_logins_default_content();
    $default_content_n = substr_count($default_content, "\n");

    if ($datastore_path) {
        $lines = SucuriScanFileInfo::file_lines($datastore_path);

        if ($lines) {
            $failed_logins = array(
                'count' => 0,
                'first_attempt' => 0,
                'last_attempt' => 0,
                'diff_time' => 0,
                'entries' => array(),
            );

            // Read and parse all the entries found in the datastore file.
            $offset = count($lines) - 1;

            for ($key = $offset; $key >= 0; $key--) {
                $line = trim($lines[ $key ]);
                $login_data = @json_decode($line, true);

                if (is_array($login_data)) {
                    $login_data['attempt_date'] = date('r', $login_data['attempt_time']);
                    $login_data['attempt_count'] = ( $key + 1 );

                    if (!$login_data['user_agent']) {
                        $login_data['user_agent'] = 'Unknown';
                    }

                    if (!isset($login_data['user_password'])) {
                        $login_data['user_password'] = '';
                    }

                    $failed_logins['entries'][] = $login_data;
                    $failed_logins['count'] += 1;
                }
            }

            // Calculate the different time between the first and last attempt.
            if ($failed_logins['count'] > 0) {
                $z = abs($failed_logins['count'] - 1);
                $failed_logins['last_attempt'] = $failed_logins['entries'][ $z ]['attempt_time'];
                $failed_logins['first_attempt'] = $failed_logins['entries'][0]['attempt_time'];
                $failed_logins['diff_time'] = abs($failed_logins['last_attempt'] - $failed_logins['first_attempt']);

                return $failed_logins;
            }
        }
    }

    return false;
}


/**
 * Add a new entry in the datastore file where the failed logins are being kept,
 * this entry will contain the username, timestamp of the login attempt, remote
 * address of the computer sending the request, and the user-agent.
 *
 * @param  string  $user_login     Information from the current failed login event.
 * @param  string  $wrong_password Wrong password used during the supposed attack.
 * @return boolean                 Whether the information of the current failed login event was stored or not.
 */
function sucuriscan_log_failed_login($user_login = '', $wrong_password = '')
{
    $datastore_path = sucuriscan_failed_logins_datastore_path();

    // Do not collect wrong passwords if it is not necessary.
    if (sucuriscan_collect_wrong_passwords() !== true) {
        $wrong_password = '';
    }

    if ($datastore_path) {
        $login_data = json_encode(array(
            'user_login' => $user_login,
            'user_password' => $wrong_password,
            'attempt_time' => time(),
            'remote_addr' => SucuriScan::get_remote_addr(),
            'user_agent' => SucuriScan::get_user_agent(),
        ));

        $written = @file_put_contents(
            $datastore_path,
            $login_data . "\n",
            FILE_APPEND
        );

        if ($written > 0) {
            return true;
        }
    }

    return false;
}

/**
 * Read and parse all the entries in the datastore file where the failed logins
 * are being kept, this will loop through all these items and generate a table
 * in HTML code to send as a report via email according to the plugin settings
 * for the email notifications.
 *
 * @param  array   $failed_logins Information and entries gathered from the failed logins datastore file.
 * @return boolean                Whether the report was sent via email or not.
 */
function sucuriscan_report_failed_logins($failed_logins = array())
{
    if ($failed_logins && $failed_logins['count'] > 0) {
        $prettify_mails = SucuriScanMail::prettify_mails();
        $collect_wrong_passwords = sucuriscan_collect_wrong_passwords();
        $mail_content = '';

        if ($prettify_mails) {
            $table_html  = '<table border="1" cellspacing="0" cellpadding="0">';

            // Add the table headers.
            $table_html .= '<thead>';
            $table_html .= '<tr>';
            $table_html .= '<th>Username</th>';

            if ($collect_wrong_passwords === true) {
                $table_html .= '<th>Password</th>';
            }

            $table_html .= '<th>IP Address</th>';
            $table_html .= '<th>Attempt Timestamp</th>';
            $table_html .= '<th>Attempt Date/Time</th>';
            $table_html .= '</tr>';
            $table_html .= '</thead>';

            $table_html .= '<tbody>';
        }

        foreach ($failed_logins['entries'] as $login_data) {
            if ($prettify_mails) {
                $table_html .= '<tr>';
                $table_html .= '<td>' . esc_attr($login_data['user_login']) . '</td>';

                if ($collect_wrong_passwords === true) {
                    $table_html .= '<td>' . esc_attr($login_data['user_password']) . '</td>';
                }

                $table_html .= '<td>' . esc_attr($login_data['remote_addr']) . '</td>';
                $table_html .= '<td>' . $login_data['attempt_time'] . '</td>';
                $table_html .= '<td>' . $login_data['attempt_date'] . '</td>';
                $table_html .= '</tr>';
            } else {
                $mail_content .= "\n";
                $mail_content .= 'Username: ' . $login_data['user_login'] . "\n";

                if ($collect_wrong_passwords === true) {
                    $mail_content .= 'Password: ' . $login_data['user_password'] . "\n";
                }

                $mail_content .= 'IP Address: ' . $login_data['remote_addr'] . "\n";
                $mail_content .= 'Attempt Timestamp: ' . $login_data['attempt_time'] . "\n";
                $mail_content .= 'Attempt Date/Time: ' . $login_data['attempt_date'] . "\n";
            }
        }

        if ($prettify_mails) {
            $table_html .= '</tbody>';
            $table_html .= '</table>';
            $mail_content = $table_html;
        }

        if (SucuriScanEvent::notify_event('bruteforce_attack', $mail_content)) {
            sucuriscan_reset_failed_logins();

            return true;
        }
    }

    return false;
}

/**
 * Remove all the entries in the datastore file where the failed logins are
 * being kept. The execution of this function will not delete the file (which is
 * likely the best move) but rather will clean its content and append the
 * default code defined by another function above.
 *
 * @return boolean Whether the datastore file was resetted or not.
 */
function sucuriscan_reset_failed_logins()
{
    $datastore_path = SucuriScan::datastore_folder_path('sucuri-failedlogins.php');
    $datastore_backup_path = sucuriscan_failed_logins_datastore_path(true, false);
    $default_content = sucuriscan_failed_logins_default_content();
    $current_content = @file_get_contents($datastore_path);
    $current_content = str_replace($default_content, '', $current_content);

    @file_put_contents(
        $datastore_backup_path,
        $current_content,
        FILE_APPEND
    );

    return (bool) sucuriscan_failed_logins_datastore_path(false, true);
}

/**
 * Process the requests sent by the form submissions originated in the settings
 * page, all forms must have a nonce field that will be checked against the one
 * generated in the template render function.
 *
 * @param  boolean $page_nonce True if the nonce is valid, False otherwise.
 * @return void
 */
function sucuriscan_settings_form_submissions($page_nonce = null)
{
    global $sucuriscan_schedule_allowed,
        $sucuriscan_interface_allowed,
        $sucuriscan_notify_options,
        $sucuriscan_email_subjects;

    // Use this conditional to avoid double checking.
    if (is_null($page_nonce)) {
        $page_nonce = SucuriScanInterface::check_nonce();
    }

    if ($page_nonce) {
        // Save API key after it was recovered by the administrator.
        if ($api_key = SucuriScanRequest::post(':manual_api_key')) {
            SucuriScanAPI::setPluginKey($api_key, true);
            SucuriScanEvent::schedule_task();
            SucuriScanEvent::report_info_event('Sucuri API key was added manually.');
        }

        // Remove API key from the local storage.
        if (SucuriScanRequest::post(':remove_api_key') !== false) {
            SucuriScanAPI::setPluginKey('');
            wp_clear_scheduled_hook('sucuriscan_scheduled_scan');
            SucuriScanEvent::report_critical_event('Sucuri API key was deleted.');
            SucuriScanEvent::notify_event('plugin_change', 'Sucuri API key removed');
        }

        // Enable or disable the filesystem scanner.
        if ($fs_scanner = SucuriScanRequest::post(':fs_scanner', '(en|dis)able')) {
            $action_d = $fs_scanner . 'd';
            $message = 'Main file system scanner was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':fs_scanner', $action_d);
            SucuriScanEvent::report_auto_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        // Enable or disable the filesystem scanner for file integrity.
        if ($scan_checksums = SucuriScanRequest::post(':scan_checksums', '(en|dis)able')) {
            $action_d = $scan_checksums . 'd';
            $message = 'File system scanner for file integrity was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':scan_checksums', $action_d);
            SucuriScanEvent::report_auto_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        // Enable or disable the filesystem scanner for error logs.
        if ($ignore_scanning = SucuriScanRequest::post(':ignore_scanning', '(en|dis)able')) {
            $action_d = $ignore_scanning . 'd';
            $message = 'File system scanner rules to ignore directories was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':ignore_scanning', $action_d);
            SucuriScanEvent::report_auto_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        // Enable or disable the filesystem scanner for error logs.
        if ($scan_errorlogs = SucuriScanRequest::post(':scan_errorlogs', '(en|dis)able')) {
            $action_d = $scan_errorlogs . 'd';
            $message = 'File system scanner for error logs was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':scan_errorlogs', $action_d);
            SucuriScanEvent::report_auto_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        // Enable or disable the error logs parsing.
        if ($parse_errorlogs = SucuriScanRequest::post(':parse_errorlogs', '(en|dis)able')) {
            $action_d = $parse_errorlogs . 'd';
            $message = 'Analysis of main error log file was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':parse_errorlogs', $action_d);
            SucuriScanEvent::report_auto_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        // Enable or disable the SiteCheck scanner and the malware scan page.
        if ($sitecheck_scanner = SucuriScanRequest::post(':sitecheck_scanner', '(en|dis)able')) {
            $action_d = $sitecheck_scanner . 'd';
            $message = 'SiteCheck malware and blacklist scanner was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':sitecheck_scanner', $action_d);
            SucuriScanEvent::report_auto_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        // Modify the schedule of the filesystem scanner.
        if ($frequency = SucuriScanRequest::post(':scan_frequency')) {
            if (array_key_exists($frequency, $sucuriscan_schedule_allowed)) {
                SucuriScanOption::update_option(':scan_frequency', $frequency);
                wp_clear_scheduled_hook('sucuriscan_scheduled_scan');

                if ($frequency != '_oneoff') {
                    wp_schedule_event(time() + 10, $frequency, 'sucuriscan_scheduled_scan');
                }

                $frequency_title = strtolower($sucuriscan_schedule_allowed[ $frequency ]);
                $message = 'File system scanning frequency set to <code>' . $frequency_title . '</code>';

                SucuriScanEvent::report_info_event($message);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }

        // Set the method (aka. interface) that will be used to scan the site.
        if ($interface = SucuriScanRequest::post(':scan_interface')) {
            $allowed_values = array_keys($sucuriscan_interface_allowed);

            if (in_array($interface, $allowed_values)) {
                $message = 'File system scanning interface set to <code>' . $interface . '</code>';

                SucuriScanOption::update_option(':scan_interface', $interface);
                SucuriScanEvent::report_info_event($message);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }

        // Update the limit of error log lines to parse.
        if ($errorlogs_limit = SucuriScanRequest::post(':errorlogs_limit', '[0-9]+')) {
            if ($errorlogs_limit > 1000) {
                SucuriScanInterface::error('Analyze more than 1,000 lines will take too much time.');
            } else {
                SucuriScanOption::update_option(':errorlogs_limit', $errorlogs_limit);
                SucuriScanInterface::info('Analyze last <code>' . $errorlogs_limit . '</code> entries encountered in the error logs.');

                if ($errorlogs_limit == 0) {
                    SucuriScanOption::update_option(':parse_errorlogs', 'disabled');
                }
            }
        }

        // Reset the plugin security logs.
        $allowed_log_files = '(integrity|lastlogins|failedlogins|sitecheck)';
        if ($reset_logfile = SucuriScanRequest::post(':reset_logfile', $allowed_log_files)) {
            $files_to_delete = array(
                'sucuri-' . $reset_logfile . '.php',
                'sucuri-old' . $reset_logfile . '.php',
            );

            foreach ($files_to_delete as $log_filename) {
                $log_filepath = SucuriScan::datastore_folder_path($log_filename);

                if (@unlink($log_filepath)) {
                    $log_filename_simple = str_replace('.php', '', $log_filename);
                    $message = 'Deleted security log <code>' . $log_filename_simple . '</code>';

                    SucuriScanEvent::report_debug_event($message);
                    SucuriScanInterface::info($message);
                }
            }
        }

        // Ignore a new event for email notifications.
        if ($action = SucuriScanRequest::post(':ignorerule_action', '(add|remove)')) {
            $ignore_rule = SucuriScanRequest::post(':ignorerule');

            if ($action == 'add') {
                if (SucuriScanOption::add_ignored_event($ignore_rule)) {
                    SucuriScanInterface::info('Post-type ignored successfully.');
                    SucuriScanEvent::report_warning_event('Changes in <code>' . $ignore_rule . '</code> post-type will be ignored');
                } else {
                    SucuriScanInterface::error('The post-type is invalid or it may be already ignored.');
                }
            } elseif ($action == 'remove') {
                SucuriScanOption::remove_ignored_event($ignore_rule);
                SucuriScanInterface::info('Post-type removed from the list successfully.');
                SucuriScanEvent::report_notice_event('Changes in <code>' . $ignore_rule . '</code> post-type will not be ignored');
            }
        }

        // Ignore a new directory path for the file system scans.
        if ($action = SucuriScanRequest::post(':ignorescanning_action', '(ignore|unignore)')) {
            $ignore_directories = SucuriScanRequest::post(':ignorescanning_dirs', '_array');
            $ignore_file = SucuriScanRequest::post(':ignorescanning_file');

            if ($action == 'ignore') {
                // Target a single file path to be ignored.
                if ($ignore_file !== false) {
                    $ignore_directories = array( $ignore_file );
                }

                // Target a list of directories to be ignored.
                if (!empty($ignore_directories)) {
                    $were_ignored = array();

                    foreach ($ignore_directories as $resource_path) {
                        if (file_exists($resource_path)
                            && SucuriScanFSScanner::ignore_directory($resource_path)
                        ) {
                            $were_ignored[] = $resource_path;
                        }
                    }

                    if (!empty($were_ignored)) {
                        SucuriScanInterface::info('Items selected will be ignored in future scans.');
                        SucuriScanEvent::report_warning_event(sprintf(
                            'Resources will not be scanned: (multiple entries): %s',
                            @implode(',', $ignore_directories)
                        ));
                    }
                }
            } elseif ($action == 'unignore') {
                foreach ($ignore_directories as $directory_path) {
                    SucuriScanFSScanner::unignore_directory($directory_path);
                }

                SucuriScanInterface::info('Items selected will not be ignored anymore.');
                SucuriScanEvent::report_notice_event(sprintf(
                    'Resources will be scanned: (multiple entries): %s',
                    @implode(',', $ignore_directories)
                ));
            }
        }

        // Trust and IP address to ignore notifications for a subnet.
        if ($trust_ip = SucuriScanRequest::post(':trust_ip')) {
            if (SucuriScan::is_valid_ip($trust_ip)
                || SucuriScan::is_valid_cidr($trust_ip)
            ) {
                $cache = new SucuriScanCache('trustip');
                $ip_info = SucuriScan::get_ip_info($trust_ip);
                $ip_info['added_at'] = SucuriScan::local_time();
                $cache_key = md5($ip_info['remote_addr']);

                if ($cache->exists($cache_key)) {
                    SucuriScanInterface::error('The IP address specified was already trusted.');
                } elseif ($cache->add($cache_key, $ip_info)) {
                    $message = 'Changes from <code>' . $trust_ip . '</code> will be ignored';

                    SucuriScanEvent::report_warning_event($message);
                    SucuriScanInterface::info($message);
                } else {
                    SucuriScanInterface::error('The new entry was not saved in the datastore file.');
                }
            }
        }

        // Trust and IP address to ignore notifications for a subnet.
        if ($del_trust_ip = SucuriScanRequest::post(':del_trust_ip', '_array')) {
            $cache = new SucuriScanCache('trustip');

            foreach ($del_trust_ip as $cache_key) {
                $cache->delete($cache_key);
            }

            SucuriScanInterface::info('The IP addresses selected were deleted successfully.');
        }

        // Update the settings for the heartbeat API.
        if ($heartbeat_status = SucuriScanRequest::post(':heartbeat_status')) {
            $statuses_allowed = SucuriScanHeartbeat::statuses_allowed();

            if (array_key_exists($heartbeat_status, $statuses_allowed)) {
                $message = 'Heartbeat status set to <code>' . $heartbeat_status . '</code>';

                SucuriScanOption::update_option(':heartbeat', $heartbeat_status);
                SucuriScanEvent::report_info_event($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Heartbeat status not allowed.');
            }
        }

        // Update the value of the heartbeat pulse.
        if ($heartbeat_pulse = SucuriScanRequest::post(':heartbeat_pulse')) {
            $pulses_allowed = SucuriScanHeartbeat::pulses_allowed();

            if (array_key_exists($heartbeat_pulse, $pulses_allowed)) {
                $message = 'Heartbeat pulse set to <code>' . $heartbeat_pulse . '</code> seconds.';

                SucuriScanOption::update_option(':heartbeat_pulse', $heartbeat_pulse);
                SucuriScanEvent::report_info_event($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Heartbeat pulse not allowed.');
            }
        }

        // Update the value of the heartbeat interval.
        if ($heartbeat_interval = SucuriScanRequest::post(':heartbeat_interval')) {
            $intervals_allowed = SucuriScanHeartbeat::intervals_allowed();

            if (array_key_exists($heartbeat_interval, $intervals_allowed)) {
                $message = 'Heartbeat interval set to <code>' . $heartbeat_interval . '</code>';

                SucuriScanOption::update_option(':heartbeat_interval', $heartbeat_interval);
                SucuriScanEvent::report_info_event($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Heartbeat interval not allowed.');
            }
        }

        // Enable or disable the auto-start execution of heartbeat.
        if ($heartbeat_autostart = SucuriScanRequest::post(':heartbeat_autostart', '(en|dis)able')) {
            $action_d = $heartbeat_autostart . 'd';
            $message = 'Heartbeat auto-start was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':heartbeat_autostart', $action_d);
            SucuriScanEvent::report_info_event($message);
            SucuriScanInterface::info($message);
        }
    }
}

/**
 * Read and parse the content of the general settings template.
 *
 * @return string Parsed HTML code for the general settings panel.
 */
function sucuriscan_settings_general($nonce)
{
    // Process all form submissions.
    sucuriscan_settings_form_submissions($nonce);

    $params = array();

    // Keep the reset options panel and form submission processor before anything else.
    $params['SettingsSection.ResetOptions'] = sucuriscan_settings_general_resetoptions($nonce);

    // Build HTML code for the additional general settings panels.
    $params['SettingsSection.ApiKey'] = sucuriscan_settings_general_apikey($nonce);
    $params['SettingsSection.DataStorage'] = sucuriscan_settings_general_datastorage($nonce);
    $params['SettingsSection.ReverseProxy'] = sucuriscan_settings_general_reverseproxy($nonce);
    $params['SettingsSection.PasswordCollector'] = sucuriscan_settings_general_pwdcollector($nonce);
    $params['SettingsSection.IPDiscoverer'] = sucuriscan_settings_general_ipdiscoverer($nonce);
    $params['SettingsSection.CommentMonitor'] = sucuriscan_settings_general_commentmonitor($nonce);
    $params['SettingsSection.XhrMonitor'] = sucuriscan_settings_general_xhrmonitor($nonce);
    $params['SettingsSection.AuditLogStats'] = sucuriscan_settings_general_auditlogstats($nonce);
    $params['SettingsSection.Datetime'] = sucuriscan_settings_general_datetime($nonce);

    sucuriscan_settings_general_adsvisibility($nonce);

    return SucuriScanTemplate::getSection('settings-general', $params);
}

function sucuriscan_settings_general_resetoptions($nonce)
{
    // Reset all the plugin's options.
    if ($nonce && SucuriScanRequest::post(':reset_options') !== false) {
        $process = SucuriScanRequest::post(':process_form');

        if (intval($process) === 1) {
            // Notify the event before the API key is removed.
            $message = 'Sucuri plugin options were reset';
            SucuriScanEvent::report_critical_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);

            // Remove all plugin options from the database.
            SucuriScanOption::delete_plugin_options();

            // Remove the scheduled tasks.
            wp_clear_scheduled_hook('sucuriscan_scheduled_scan');

            // Remove all the local security logs.
            @unlink(SucuriScan::datastore_folder_path('.htaccess'));
            @unlink(SucuriScan::datastore_folder_path('index.html'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-failedlogins.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-integrity.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-lastlogins.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-oldfailedlogins.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-plugindata.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-sitecheck.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-trustip.php'));
            @rmdir(SucuriScan::datastore_folder_path());

            // Revert hardening of core directories (includes, content, uploads).
            SucuriScanHardening::dewhitelist('ms-files.php', 'wp-includes');
            SucuriScanHardening::dewhitelist('wp-tinymce.php', 'wp-includes');
            SucuriScanHardening::unharden_directory(ABSPATH . '/wp-includes');
            SucuriScanHardening::unharden_directory(WP_CONTENT_DIR . '/uploads');
            SucuriScanHardening::unharden_directory(WP_CONTENT_DIR);

            SucuriScanInterface::info('Plugin options, core directory hardening, and security logs were reset');
        } else {
            SucuriScanInterface::error('You need to confirm that you understand the risk of this operation.');
        }
    }

    return SucuriScanTemplate::getSection('settings-general-resetoptions');
}

function sucuriscan_settings_general_apikey($nonce)
{
    $params = array();
    $invalid_domain = false;
    $api_recovery_modal = '';
    $api_registered_modal = '';

    // Whether the form to manually add the API key should be shown or not.
    $display_manual_key_form = (bool) (SucuriScanRequest::post(':recover_key') !== false);

    if ($nonce) {
        if (SucuriScanRequest::post(':plugin_api_key') !== false) {
            $user_id = SucuriScanRequest::post(':setup_user');
            $user_obj = SucuriScan::get_user_by_id($user_id);

            if ($user_obj !== false && user_can($user_obj, 'administrator')) {
                // Send request to generate new API key or display form to set manually.
                if (SucuriScanAPI::registerSite($user_obj->user_email)) {
                    $api_registered_modal = SucuriScanTemplate::getModal(
                        'settings-apiregistered',
                        array(
                            'Title' => 'Site registered successfully',
                            'CssClass' => 'sucuriscan-apikey-registered',
                        )
                    );
                } else {
                    $display_manual_key_form = true;
                }
            }
        }

        // Recover API key through the email registered previously.
        if (SucuriScanRequest::post(':recover_key') !== false) {
            SucuriScanAPI::recoverKey();
            SucuriScanEvent::report_info_event('Recovery of the Sucuri API key was requested.');
            $api_recovery_modal = SucuriScanTemplate::getModal(
                'settings-apirecovery',
                array(
                    'Title' => 'Plugin API Key Recovery',
                    'CssClass' => 'sucuriscan-apirecovery',
                )
            );
        }
    }

    $api_key = SucuriScanAPI::getPluginKey();

    // Check whether the domain name is valid or not.
    if (!$api_key) {
        $clean_domain = SucuriScan::get_top_level_domain();
        $domain_address = @gethostbyname($clean_domain);
        $invalid_domain = (bool) ($domain_address === $clean_domain);
    }

    $params['APIKey'] = (!$api_key ? '(not set)' : $api_key);
    $params['APIKey.RecoverVisibility'] = SucuriScanTemplate::visibility(!$api_key && !$display_manual_key_form);
    $params['APIKey.ManualKeyFormVisibility'] = SucuriScanTemplate::visibility($display_manual_key_form);
    $params['APIKey.RemoveVisibility'] = SucuriScanTemplate::visibility((bool) $api_key);
    $params['InvalidDomainVisibility'] = SucuriScanTemplate::visibility($invalid_domain);
    $params['ModalWhenAPIRegistered'] = $api_registered_modal;
    $params['ModalForApiKeyRecovery'] = $api_recovery_modal;

    return SucuriScanTemplate::getSection('settings-general-apikey', $params);
}

function sucuriscan_settings_general_datastorage($nonce)
{
    $params = array();

    // Update the datastore path (if the new directory exists).
    if ($nonce) {
        $directory = SucuriScanRequest::post(':datastore_path');

        if ($directory) {
            $current = SucuriScanOption::datastore_folder_path();

            // Try to create the new directory (if possible).
            if (!file_exists($directory)) {
                @mkdir($directory, 0755, true);
            }

            // Check if the directory is writable and move all the logs.
            if (file_exists($directory)) {
                if (is_writable($directory)) {
                    $message = 'Datastore path set to <code>' . $directory . '</code>';

                    SucuriScanOption::update_option(':datastore_path', $directory);
                    SucuriScanEvent::report_info_event($message);
                    SucuriScanEvent::notify_event('plugin_change', $message);
                    SucuriScanInterface::info($message);

                    if (file_exists($current)) {
                        $newpath = SucuriScanOption::datastore_folder_path();

                        // Some file systems do not work correctly with trailing separators.
                        $current = rtrim($current, '/');
                        $newpath = rtrim($newpath, '/');
                        @rename($current, $newpath);
                    }
                } else {
                    SucuriScanInterface::error('The new directory path is not writable.');
                }
            } else {
                SucuriScanInterface::error('The directory path specified does not exists.');
            }
        }
    }

    $params['DatastorePath'] = SucuriScanOption::get_option(':datastore_path');

    return SucuriScanTemplate::getSection('settings-general-datastorage', $params);
}

function sucuriscan_settings_general_reverseproxy($nonce)
{
    $params = array(
        'ReverseProxyStatus' => 'Enabled',
        'ReverseProxySwitchText' => 'Disable',
        'ReverseProxySwitchValue' => 'disable',
        'ReverseProxySwitchCssClass' => 'button-danger',
    );

    // Enable or disable the reverse proxy support.
    if ($nonce) {
        $revproxy = SucuriScanRequest::post(':revproxy', '(en|dis)able');

        if ($revproxy) {
            if ($revproxy === 'enable') {
                SucuriScanOption::setRevProxy('enable');
                SucuriScanOption::setAddrHeader('HTTP_X_SUCURI_CLIENTIP');
            } else {
                SucuriScanOption::setRevProxy('disable');
                SucuriScanOption::setAddrHeader('REMOTE_ADDR');
            }
        }
    }

    if (SucuriScanOption::is_disabled(':revproxy')) {
        $params['ReverseProxyStatus'] = 'Disabled';
        $params['ReverseProxySwitchText'] = 'Enable';
        $params['ReverseProxySwitchValue'] = 'enable';
        $params['ReverseProxySwitchCssClass'] = 'button-success';
    }

    return SucuriScanTemplate::getSection('settings-general-reverseproxy', $params);
}

function sucuriscan_settings_general_pwdcollector($nonce)
{
    $params = array(
        'PwdCollectorStatus' => 'Disabled',
        'PwdCollectorSwitchText' => 'Enable',
        'PwdCollectorSwitchValue' => 'enable',
        'PwdCollectorSwitchCssClass' => 'button-success',
    );

    // Update the collection of failed passwords settings.
    if ($nonce) {
        $collector = SucuriScanRequest::post(':collect_wrong_passwords');

        if ($collector) {
            $collector = strtolower($collector);
            $message = 'Collect failed login passwords set to <code>%s</code>';

            if ($collector == 'enable') {
                $collect_action = 'enabled';
                $message = sprintf($message, $collect_action);
                SucuriScanEvent::report_critical_event($message);
            } else {
                $collect_action = 'disabled';
                $message = sprintf($message, $collect_action);
                SucuriScanEvent::report_info_event($message);
            }

            SucuriScanOption::update_option(':collect_wrong_passwords', $collect_action);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }
    }

    if (sucuriscan_collect_wrong_passwords() === true) {
        $params['PwdCollectorStatus'] = 'Enabled';
        $params['PwdCollectorSwitchText'] = 'Disable';
        $params['PwdCollectorSwitchValue'] = 'disable';
        $params['PwdCollectorSwitchCssClass'] = 'button-danger';
    }

    return SucuriScanTemplate::getSection('settings-general-pwdcollector', $params);
}

function sucuriscan_settings_general_ipdiscoverer($nonce)
{
    $params = array(
        'TopLevelDomain' => 'Unknown',
        'WebsiteHostName' => 'Unknown',
        'WebsiteHostAddress' => 'Unknown',
        'IsUsingCloudProxy' => 'Unknown',
        'WebsiteURL' => 'Unknown',
        'RemoteAddress' => '127.0.0.1',
        'RemoteAddressHeader' => 'INVALID',
        'AddrHeaderOptions' => '',
        /* Switch form information. */
        'DnsLookupsStatus' => 'Enabled',
        'DnsLookupsSwitchText' => 'Disable',
        'DnsLookupsSwitchValue' => 'disable',
        'DnsLookupsSwitchCssClass' => 'button-danger',
    );

    // Get main HTTP header for IP retrieval.
    $allowed_headers = SucuriScan::allowedHttpHeaders(true);

    // Configure the DNS lookups option for reverse proxy detection.
    if ($nonce) {
        $dns_lookups = SucuriScanRequest::post(':dns_lookups', '(en|dis)able');
        $addr_header = SucuriScanRequest::post(':addr_header');

        if ($dns_lookups) {
            $action_d = $dns_lookups . 'd';
            $message = 'DNS lookups for reverse proxy detection <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':dns_lookups', $action_d);
            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        if ($addr_header) {
            if ($addr_header === 'REMOTE_ADDR') {
                SucuriScanOption::setAddrHeader('REMOTE_ADDR');
                SucuriScanOption::setRevProxy('disable');
            } else {
                SucuriScanOption::setAddrHeader($addr_header);
                SucuriScanOption::setRevProxy('enable');
            }
        }
    }

    if (SucuriScanOption::is_disabled(':dns_lookups')) {
        $params['DnsLookupsStatus'] = 'Disabled';
        $params['DnsLookupsSwitchText'] = 'Enable';
        $params['DnsLookupsSwitchValue'] = 'enable';
        $params['DnsLookupsSwitchCssClass'] = 'button-success';
    }

    $proxy_info = SucuriScan::is_behind_cloudproxy(true);
    $base_domain = SucuriScan::get_domain(true);

    $params['TopLevelDomain'] = $proxy_info['http_host'];
    $params['WebsiteHostName'] = $proxy_info['host_name'];
    $params['WebsiteHostAddress'] = $proxy_info['host_addr'];
    $params['IsUsingCloudProxy'] = ($proxy_info['status'] ? 'Active' : 'Not Active');
    $params['RemoteAddressHeader'] = SucuriScan::get_remote_addr_header();
    $params['RemoteAddress'] = SucuriScan::get_remote_addr();
    $params['WebsiteURL'] = SucuriScan::get_domain();
    $params['AddrHeaderOptions'] = SucuriScanTemplate::selectOptions(
        $allowed_headers,
        SucuriScanOption::get_option(':addr_header')
    );

    if ($base_domain !== $proxy_info['http_host']) {
        $params['TopLevelDomain'] = sprintf('%s (%s)', $params['TopLevelDomain'], $base_domain);
    }

    return SucuriScanTemplate::getSection('settings-general-ipdiscoverer', $params);
}

function sucuriscan_settings_general_commentmonitor($nonce)
{
    $params = array(
        'CommentMonitorStatus' => 'Enabled',
        'CommentMonitorSwitchText' => 'Disable',
        'CommentMonitorSwitchValue' => 'disable',
        'CommentMonitorSwitchCssClass' => 'button-danger',
    );

    // Configure the comment monitor option.
    if ($nonce) {
        $monitor = SucuriScanRequest::post(':comment_monitor', '(en|dis)able');

        if ($monitor) {
            $action_d = $monitor . 'd';
            $message = 'Comment monitor was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':comment_monitor', $action_d);
            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }
    }

    if (SucuriScanOption::is_disabled(':comment_monitor')) {
        $params['CommentMonitorStatus'] = 'Disabled';
        $params['CommentMonitorSwitchText'] = 'Enable';
        $params['CommentMonitorSwitchValue'] = 'enable';
        $params['CommentMonitorSwitchCssClass'] = 'button-success';
    }

    return SucuriScanTemplate::getSection('settings-general-commentmonitor', $params);
}

function sucuriscan_settings_general_xhrmonitor($nonce)
{
    $params = array(
        'XhrMonitorStatus' => 'Enabled',
        'XhrMonitorSwitchText' => 'Disable',
        'XhrMonitorSwitchValue' => 'disable',
        'XhrMonitorSwitchCssClass' => 'button-danger',
    );

    // Configure the XHR monitor option.
    if ($nonce) {
        $monitor = SucuriScanRequest::post(':xhr_monitor', '(en|dis)able');

        if ($monitor) {
            $action_d = $monitor . 'd';
            $message = 'XHR (XML HTTP Request) monitor was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':xhr_monitor', $action_d);
            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }
    }

    if (SucuriScanOption::is_disabled(':xhr_monitor')) {
        $params['XhrMonitorStatus'] = 'Disabled';
        $params['XhrMonitorSwitchText'] = 'Enable';
        $params['XhrMonitorSwitchValue'] = 'enable';
        $params['XhrMonitorSwitchCssClass'] = 'button-success';
    }

    return SucuriScanTemplate::getSection('settings-general-xhrmonitor', $params);
}

function sucuriscan_settings_general_auditlogstats($nonce)
{
    $params = array();
    $params['AuditLogStats.StatusNum'] = '1';
    $params['AuditLogStats.Status'] = 'Enabled';
    $params['AuditLogStats.SwitchText'] = 'Disable';
    $params['AuditLogStats.SwitchValue'] = 'disable';
    $params['AuditLogStats.SwitchCssClass'] = 'button-danger';
    $params['AuditLogStats.Limit'] = 0;

    if ($nonce) {
        // Update the limit for audit logs report.
        if ($logs4report = SucuriScanRequest::post(':logs4report', '[0-9]{1,4}')) {
            $_POST['sucuriscan_audit_report'] = 'enable';
            $message = 'Audit log statistics limit set to <code>' . $logs4report . '</code>';

            SucuriScanOption::update_option(':logs4report', $logs4report);
            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        // Enable or disable the audit logs report.
        if ($audit_report = SucuriScanRequest::post(':audit_report', '(en|dis)able')) {
            $action_d = $audit_report . 'd';
            $message = 'Audit log statistics were <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':audit_report', $action_d);
            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }
    }

    $logs4report = SucuriScanOption::get_option(':logs4report');
    $audit_report = SucuriScanOption::get_option(':audit_report');
    $params['AuditLogStats.Limit'] = SucuriScan::escape($logs4report);

    if ($audit_report === 'disabled') {
        $params['AuditLogStats.StatusNum'] = '0';
        $params['AuditLogStats.Status'] = 'Disabled';
        $params['AuditLogStats.SwitchText'] = 'Enable';
        $params['AuditLogStats.SwitchValue'] = 'enable';
        $params['AuditLogStats.SwitchCssClass'] = 'button-success';
    }

    return SucuriScanTemplate::getSection('settings-general-auditlogstats', $params);
}

function sucuriscan_settings_general_datetime($nonce)
{
    $params = array();
    $params['Datetime.AdminURL'] = SucuriScan::admin_url('options-general.php');
    $params['Datetime.HumanReadable'] = SucuriScan::current_datetime();
    $params['Datetime.Timestamp'] = SucuriScan::local_time();
    $params['Datetime.Timezone'] = 'Unknown';

    if (function_exists('wp_timezone_choice')) {
        $gmt_offset = SucuriScanOption::get_option('gmt_offset');
        $tzstring = SucuriScanOption::get_option('timezone_string');

        $params['Datetime.Timezone'] = empty($tzstring) ? 'UTC' . $gmt_offset : $tzstring;
    }

    return SucuriScanTemplate::getSection('settings-general-datetime', $params);
}

function sucuriscan_settings_general_adsvisibility($nonce)
{
    // Update the advertisement visibility settings.
    if ($nonce) {
        $ads_visibility = SucuriScanRequest::post(':ads_visibility');

        if ($ads_visibility === 'disable') {
            $option_value = $ads_visibility . 'd';
            $message = sprintf('Plugin advertisement set to <code>%s</code>', $option_value);

            SucuriScanOption::update_option(':ads_visibility', $option_value);
            SucuriScanEvent::report_info_event($message);
            SucuriScanInterface::info($message);
        }
    }
}

/**
 * Read and parse the content of the scanner settings template.
 *
 * @return string Parsed HTML code for the scanner settings panel.
 */
function sucuriscan_settings_scanner()
{
    global $sucuriscan_schedule_allowed,
        $sucuriscan_interface_allowed;

    // Get initial variables to decide some things bellow.
    $fs_scanner = SucuriScanOption::get_option(':fs_scanner');
    $scan_freq = SucuriScanOption::get_option(':scan_frequency');
    $scan_interface = SucuriScanOption::get_option(':scan_interface');
    $scan_checksums = SucuriScanOption::get_option(':scan_checksums');
    $scan_errorlogs = SucuriScanOption::get_option(':scan_errorlogs');
    $parse_errorlogs = SucuriScanOption::get_option(':parse_errorlogs');
    $errorlogs_limit = SucuriScanOption::get_option(':errorlogs_limit');
    $ignore_scanning = SucuriScanOption::get_option(':ignore_scanning');
    $sitecheck_scanner = SucuriScanOption::get_option(':sitecheck_scanner');
    $sitecheck_counter = SucuriScanOption::get_option(':sitecheck_counter');
    $runtime_scan_human = SucuriScanFSScanner::get_filesystem_runtime(true);

    // Get the file path of the security logs.
    $integrity_log_path = SucuriScan::datastore_folder_path('sucuri-integrity.php');
    $lastlogins_log_path = SucuriScan::datastore_folder_path('sucuri-lastlogins.php');
    $failedlogins_log_path = SucuriScan::datastore_folder_path('sucuri-failedlogins.php');
    $sitecheck_log_path = SucuriScan::datastore_folder_path('sucuri-sitecheck.php');

    // Generate the HTML code for the option list in the form select fields.
    $scan_freq_options = SucuriScanTemplate::selectOptions($sucuriscan_schedule_allowed, $scan_freq);
    $scan_interface_options = SucuriScanTemplate::selectOptions($sucuriscan_interface_allowed, $scan_interface);

    $params = array(
        /* Filesystem scanner */
        'FsScannerStatus' => 'Enabled',
        'FsScannerSwitchText' => 'Disable',
        'FsScannerSwitchValue' => 'disable',
        'FsScannerSwitchCssClass' => 'button-danger',
        /* Scan files checksum. */
        'ScanChecksumsStatus' => 'Enabled',
        'ScanChecksumsSwitchText' => 'Disable',
        'ScanChecksumsSwitchValue' => 'disable',
        'ScanChecksumsSwitchCssClass' => 'button-danger',
        /* Ignore scanning. */
        'IgnoreScanningStatus' => 'Enabled',
        'IgnoreScanningSwitchText' => 'Disable',
        'IgnoreScanningSwitchValue' => 'disable',
        'IgnoreScanningSwitchCssClass' => 'button-danger',
        /* Scan error logs. */
        'ScanErrorlogsStatus' => 'Enabled',
        'ScanErrorlogsSwitchText' => 'Disable',
        'ScanErrorlogsSwitchValue' => 'disable',
        'ScanErrorlogsSwitchCssClass' => 'button-danger',
        /* Parse error logs. */
        'ParseErrorLogsStatus' => 'Enabled',
        'ParseErrorLogsSwitchText' => 'Disable',
        'ParseErrorLogsSwitchValue' => 'disable',
        'ParseErrorLogsSwitchCssClass' => 'button-danger',
        /* SiteCheck scanner. */
        'SiteCheckScannerStatus' => 'Enabled',
        'SiteCheckScannerSwitchText' => 'Disable',
        'SiteCheckScannerSwitchValue' => 'disable',
        'SiteCheckScannerSwitchCssClass' => 'button-danger',
        /* Filsystem scanning frequency. */
        'ScanningFrequency' => 'Undefined',
        'ScanningFrequencyOptions' => $scan_freq_options,
        'ScanningInterface' => ( $scan_interface ? $sucuriscan_interface_allowed[ $scan_interface ] : 'Undefined' ),
        'ScanningInterfaceOptions' => $scan_interface_options,
        /* Filesystem scanning runtime. */
        'ScanningRuntimeHuman' => $runtime_scan_human,
        'SiteCheckCounter' => $sitecheck_counter,
        'ErrorLogsLimit' => $errorlogs_limit,
        'IntegrityLogLife' => '0B',
        'LastLoginLogLife' => '0B',
        'FailedLoginLogLife' => '0B',
        'SiteCheckLogLife' => '0B',
    );

    if ($fs_scanner == 'disabled') {
        $params['FsScannerStatus'] = 'Disabled';
        $params['FsScannerSwitchText'] = 'Enable';
        $params['FsScannerSwitchValue'] = 'enable';
        $params['FsScannerSwitchCssClass'] = 'button-success';
    }

    if ($scan_checksums == 'disabled') {
        $params['ScanChecksumsStatus'] = 'Disabled';
        $params['ScanChecksumsSwitchText'] = 'Enable';
        $params['ScanChecksumsSwitchValue'] = 'enable';
        $params['ScanChecksumsSwitchCssClass'] = 'button-success';
    }

    if ($ignore_scanning == 'disabled') {
        $params['IgnoreScanningStatus'] = 'Disabled';
        $params['IgnoreScanningSwitchText'] = 'Enable';
        $params['IgnoreScanningSwitchValue'] = 'enable';
        $params['IgnoreScanningSwitchCssClass'] = 'button-success';
    }

    if ($scan_errorlogs == 'disabled') {
        $params['ScanErrorlogsStatus'] = 'Disabled';
        $params['ScanErrorlogsSwitchText'] = 'Enable';
        $params['ScanErrorlogsSwitchValue'] = 'enable';
        $params['ScanErrorlogsSwitchCssClass'] = 'button-success';
    }

    if ($parse_errorlogs == 'disabled') {
        $params['ParseErrorLogsStatus'] = 'Disabled';
        $params['ParseErrorLogsSwitchText'] = 'Enable';
        $params['ParseErrorLogsSwitchValue'] = 'enable';
        $params['ParseErrorLogsSwitchCssClass'] = 'button-success';
    }

    if ($sitecheck_scanner == 'disabled') {
        $params['SiteCheckScannerStatus'] = 'Disabled';
        $params['SiteCheckScannerSwitchText'] = 'Enable';
        $params['SiteCheckScannerSwitchValue'] = 'enable';
        $params['SiteCheckScannerSwitchCssClass'] = 'button-success';
    }

    if (array_key_exists($scan_freq, $sucuriscan_schedule_allowed)) {
        $params['ScanningFrequency'] = $sucuriscan_schedule_allowed[ $scan_freq ];
    }

    // Determine the age of the security log files.
    $params['IntegrityLogLife'] = SucuriScan::human_filesize(@filesize($integrity_log_path));
    $params['LastLoginLogLife'] = SucuriScan::human_filesize(@filesize($lastlogins_log_path));
    $params['FailedLoginLogLife'] = SucuriScan::human_filesize(@filesize($failedlogins_log_path));
    $params['SiteCheckLogLife'] = SucuriScan::human_filesize(@filesize($sitecheck_log_path));

    return SucuriScanTemplate::getSection('settings-scanner', $params);
}

function sucuriscan_settings_ignorescanning()
{
    $params = array(
        'IgnoreScanning.ResourceList' => '',
        'IgnoreScanning.DisabledVisibility' => 'visible',
        'IgnoreScanning.NoItemsVisibility' => 'visible',
    );

    $ignore_scanning = SucuriScanFSScanner::will_ignore_scanning();

    // Allow disable of this option temporarily.
    if (SucuriScanRequest::get('no_scan') == 1) {
        $ignore_scanning = false;
    }

    // Scan the project and get the ignored paths.
    if ($ignore_scanning === true) {
        $counter = 0;
        $params['IgnoreScanning.DisabledVisibility'] = 'hidden';
        $dir_list_list = SucuriScanFSScanner::get_ignored_directories_live();

        foreach ($dir_list_list as $group => $dir_list) {
            foreach ($dir_list as $dir_data) {
                $valid_entry = false;
                $snippet_data = array(
                    'IgnoreScanning.CssClass' => '',
                    'IgnoreScanning.Directory' => '',
                    'IgnoreScanning.DirectoryPath' => '',
                    'IgnoreScanning.IgnoredAt' => '',
                    'IgnoreScanning.IgnoredAtText' => 'ok',
                    'IgnoreScanning.IgnoredCssClass' => 'success',
                );

                if ($group == 'is_ignored') {
                    $valid_entry = true;
                    $snippet_data['IgnoreScanning.Directory'] = urlencode($dir_data['directory_path']);
                    $snippet_data['IgnoreScanning.DirectoryPath'] = $dir_data['directory_path'];
                    $snippet_data['IgnoreScanning.IgnoredAt'] = SucuriScan::datetime($dir_data['ignored_at']);
                    $snippet_data['IgnoreScanning.IgnoredAtText'] = 'ignored';
                    $snippet_data['IgnoreScanning.IgnoredCssClass'] = 'warning';
                } elseif ($group == 'is_not_ignored') {
                    $valid_entry = true;
                    $snippet_data['IgnoreScanning.Directory'] = urlencode($dir_data);
                    $snippet_data['IgnoreScanning.DirectoryPath'] = $dir_data;
                }

                if ($valid_entry) {
                    $css_class = ( $counter % 2 == 0 ) ? '' : 'alternate';
                    $snippet_data['IgnoreScanning.CssClass'] = $css_class;
                    $params['IgnoreScanning.ResourceList'] .= SucuriScanTemplate::getSnippet(
                        'settings-ignorescanning',
                        $snippet_data
                    );
                    $counter++;
                }
            }
        }

        if ($counter > 0) {
            $params['IgnoreScanning.NoItemsVisibility'] = 'hidden';
        }
    }

    return SucuriScanTemplate::getSection('settings-ignorescanning', $params);
}

/**
 * Read and parse the content of the notification settings template.
 *
 * @return string Parsed HTML code for the notification settings panel.
 */
function sucuriscan_settings_alert($nonce)
{
    $params = array();

    $params['AlertSettings.Recipients'] = sucuriscan_settings_alert_recipients($nonce);
    $params['AlertSettings.Subject'] = sucuriscan_settings_alert_subject($nonce);
    $params['AlertSettings.PerHour'] = sucuriscan_settings_alert_perhour($nonce);
    $params['AlertSettings.BruteForce'] = sucuriscan_settings_alert_bruteforce($nonce);
    $params['AlertSettings.Events'] = sucuriscan_settings_alert_events($nonce);

    return SucuriScanTemplate::getSection('settings-alert', $params);
}

function sucuriscan_settings_alert_recipients($nonce)
{
    $params = array();
    $params['AlertSettings.Recipients'] = '';
    $notify_to = SucuriScanOption::get_option(':notify_to');
    $emails = array();

    // If the recipient list is not empty, explode.
    if (is_string($notify_to)) {
        $emails = explode(',', $notify_to);
    }

    // Process form submission.
    if ($nonce) {
        // Add new email address to the alert recipient list.
        if (SucuriScanRequest::post(':save_recipient') !== false) {
            $new_email = SucuriScanRequest::post(':recipient');

            if (SucuriScan::is_valid_email($new_email)) {
                $emails[] = $new_email;
                $message = 'Sucuri will send email alerts to: <code>' . $new_email . '</code>';

                SucuriScanOption::update_option(':notify_to', implode(',', $emails));
                SucuriScanEvent::report_info_event($message);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Email format not supported.');
            }
        }

        // Delete one or more recipients from the list.
        if (SucuriScanRequest::post(':delete_recipients') !== false) {
            $deleted_emails = array();
            $recipients = SucuriScanRequest::post(':recipients', '_array');

            foreach ($recipients as $address) {
                if (in_array($address, $emails)) {
                    $deleted_emails[] = $address;
                    $index = array_search($address, $emails);
                    unset($emails[$index]);
                }
            }

            if (!empty($deleted_emails)) {
                $deleted_emails_str = implode(",\x20", $deleted_emails);
                $message = 'Sucuri will not send email alerts to: <code>' . $deleted_emails_str . '</code>';

                SucuriScanOption::update_option(':notify_to', implode(',', $emails));
                SucuriScanEvent::report_info_event($message);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }

        // Debug ability of the plugin to send email alerts correctly.
        if (SucuriScanRequest::post(':debug_email')) {
            $recipients = SucuriScanOption::get_option(':notify_to');
            SucuriScanMail::send_mail(
                $recipients,
                'Test Email Alert',
                sprintf('Test email alert sent at %s', date('r')),
                array('Force' => true)
            );
            SucuriScanInterface::info('Test email alert sent, check your inbox.');
        }
    }

    $counter = 0;

    foreach ($emails as $email) {
        if (!empty($email)) {
            $css_class = ($counter % 2 === 0) ? '' : 'alternate';
            $params['AlertSettings.Recipients'] .= SucuriScanTemplate::getSnippet(
                'settings-alert-recipients',
                array(
                    'Recipient.CssClass' => $css_class,
                    'Recipient.Email' => $email,
                )
            );
            $counter++;
        }
    }

    return SucuriScanTemplate::getSection('settings-alert-recipients', $params);
}

function sucuriscan_settings_alert_subject($nonce)
{
    global $sucuriscan_email_subjects;

    $params = array(
        'AlertSettings.Subject' => '',
        'AlertSettings.CustomChecked' => '',
        'AlertSettings.CustomValue' => '',
    );

    // Process form submission to change the alert settings.
    if ($nonce) {
        if ($email_subject = SucuriScanRequest::post(':email_subject')) {
            $current_value = SucuriScanOption::get_option(':email_subject');
            $new_email_subject = false;

            /**
             * Validate the format of the email subject format.
             *
             * If the user chooses the option to build the subject of the email alerts
             * manually we will need to validate the characters. Otherwise we will need to
             * check if the pseudo-tags selected by the user are allowed and supported.
             */
            if ($email_subject === 'custom') {
                $format_pattern = '/^[0-9a-zA-Z:,\s]+$/';
                $custom_subject = SucuriScanRequest::post(':custom_email_subject');

                if ($custom_subject !== false
                    && !empty($custom_subject)
                    && @preg_match($format_pattern, $custom_subject)
                ) {
                    $new_email_subject = trim($custom_subject);
                } else {
                    SucuriScanInterface::error('Invalid characters in the email subject.');
                }
            } elseif (is_array($sucuriscan_email_subjects)
                && in_array($email_subject, $sucuriscan_email_subjects)
            ) {
                $new_email_subject = trim($email_subject);
            }

            // Proceed with the operation saving the new subject.
            if ($new_email_subject !== false
                && $current_value !== $new_email_subject
            ) {
                $message = 'Email subject set to <code>' . $new_email_subject . '</code>';

                SucuriScanOption::update_option(':email_subject', $new_email_subject);
                SucuriScanEvent::report_info_event($message);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }
    }

    // Build the HTML code for the interface.
    if (is_array($sucuriscan_email_subjects)) {
        $email_subject = SucuriScanOption::get_option(':email_subject');
        $is_official_subject = false;

        foreach ($sucuriscan_email_subjects as $subject_format) {
            if ($email_subject === $subject_format) {
                $is_official_subject = true;
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }

            $params['AlertSettings.Subject'] .= SucuriScanTemplate::getSnippet(
                'settings-alert-subject',
                array(
                    'EmailSubject.Name' => $subject_format,
                    'EmailSubject.Value' => $subject_format,
                    'EmailSubject.Checked' => $checked,
                )
            );
        }

        if ($is_official_subject === false) {
            $params['AlertSettings.CustomChecked'] = 'checked="checked"';
            $params['AlertSettings.CustomValue'] = $email_subject;
        }
    }

    return SucuriScanTemplate::getSection('settings-alert-subject', $params);
}

function sucuriscan_settings_alert_perhour($nonce)
{
    global $sucuriscan_emails_per_hour;

    $params = array();
    $params['AlertSettings.PerHour'] = '';

    if ($nonce) {
        // Update the value for the maximum emails per hour.
        if ($per_hour = SucuriScanRequest::post(':emails_per_hour')) {
            if (array_key_exists($per_hour, $sucuriscan_emails_per_hour)) {
                $per_hour_label = strtolower($sucuriscan_emails_per_hour[$per_hour]);
                $message = 'Maximum alerts per hour set to <code>' . $per_hour_label . '</code>';

                SucuriScanOption::update_option(':emails_per_hour', $per_hour);
                SucuriScanEvent::report_info_event($message);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Invalid value for the maximum emails per hour.');
            }
        }
    }

    $per_hour = SucuriScanOption::get_option(':emails_per_hour');
    $per_hour_options = SucuriScanTemplate::selectOptions($sucuriscan_emails_per_hour, $per_hour);
    $params['AlertSettings.PerHour'] = $per_hour_options;

    return SucuriScanTemplate::getSection('settings-alert-perhour', $params);
}

function sucuriscan_settings_alert_bruteforce($nonce)
{
    global $sucuriscan_maximum_failed_logins;

    $params = array();
    $params['AlertSettings.BruteForce'] = '';

    if ($nonce) {
        // Update the maximum failed logins per hour before consider it a brute-force attack.
        if ($maximum = SucuriScanRequest::post(':maximum_failed_logins')) {
            if (array_key_exists($maximum, $sucuriscan_maximum_failed_logins)) {
                $message = 'Consider brute-force attack after <code>' . $maximum . '</code> failed logins per hour';

                SucuriScanOption::update_option(':maximum_failed_logins', $maximum);
                SucuriScanEvent::report_info_event($message);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Invalid value for the brute-force alerts.');
            }
        }
    }

    $maximum = SucuriScanOption::get_option(':maximum_failed_logins');
    $maximum_options = SucuriScanTemplate::selectOptions($sucuriscan_maximum_failed_logins, $maximum);
    $params['AlertSettings.BruteForce'] = $maximum_options;

    return SucuriScanTemplate::getSection('settings-alert-bruteforce', $params);
}

function sucuriscan_settings_alert_events($nonce)
{
    global $sucuriscan_notify_options;

    $params = array();
    $params['AlertSettings.Events'] = '';

    // Process form submission to change the alert settings.
    if ($nonce) {
        // Update the notification settings.
        if (SucuriScanRequest::post(':save_alert_events') !== false) {
            $ucounter = 0;

            if (SucuriScanRequest::post(':notify_scan_checksums') == 1) {
                $_POST['sucuriscan_prettify_mails'] = '1';
            }

            foreach ($sucuriscan_notify_options as $alert_type => $alert_label) {
                $option_value = SucuriScanRequest::post($alert_type, '(1|0)');

                if ($option_value !== false) {
                    $current_value = SucuriScanOption::get_option($alert_type);
                    $option_value = ($option_value == 1) ? 'enabled' : 'disabled';

                    // Check that the option value was actually changed.
                    if ($current_value !== $option_value) {
                        SucuriScanOption::update_option($alert_type, $option_value);
                        $ucounter += 1;
                    }
                }
            }

            if ($ucounter > 0) {
                $message = 'A total of ' . $ucounter . ' alert events were changed';

                SucuriScanEvent::report_info_event($message);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }
    }

    // Build the HTML code for the interface.
    if (is_array($sucuriscan_notify_options)) {
        $pattern = '/^([a-z]+:)?(.+)/';
        $counter = 0;

        foreach ($sucuriscan_notify_options as $alert_type => $alert_label) {
            $alert_value = SucuriScanOption::get_option($alert_type);
            $checked = ($alert_value == 'enabled') ? 'checked="checked"' : '';
            $css_class = ($counter % 2 === 0) ? 'alternate' : '';
            $alert_icon = '';

            if (@preg_match($pattern, $alert_label, $match)) {
                $alert_group = str_replace(':', '', $match[1]);
                $alert_label = $match[2];

                switch ($alert_group) {
                    case 'user':
                        $alert_icon = 'dashicons-before dashicons-admin-users';
                        break;
                    case 'plugin':
                        $alert_icon = 'dashicons-before dashicons-admin-plugins';
                        break;
                    case 'theme':
                        $alert_icon = 'dashicons-before dashicons-admin-appearance';
                        break;
                }
            }

            $params['AlertSettings.Events'] .= SucuriScanTemplate::getSnippet(
                'settings-alert-events',
                array(
                    'Event.CssClass' => $css_class,
                    'Event.Name' => $alert_type,
                    'Event.Checked' => $checked,
                    'Event.Label' => $alert_label,
                    'Event.LabelIcon' => $alert_icon,
                )
            );
            $counter++;
        }
    }

    return SucuriScanTemplate::getSection('settings-alert-events', $params);
}

function sucuriscan_settings_ignore_rules()
{
    $notify_new_site_content = SucuriScanOption::get_option(':notify_post_publication');

    $template_variables = array(
        'IgnoreRules.MessageVisibility' => 'visible',
        'IgnoreRules.TableVisibility' => 'hidden',
        'IgnoreRules.PostTypes' => '',
    );

    if ($notify_new_site_content == 'enabled') {
        $post_types = get_post_types();
        $ignored_events = SucuriScanOption::get_ignored_events();

        $template_variables['IgnoreRules.MessageVisibility'] = 'hidden';
        $template_variables['IgnoreRules.TableVisibility'] = 'visible';
        $counter = 0;

        foreach ($post_types as $post_type => $post_type_object) {
            $counter++;
            $css_class = ($counter % 2 === 0) ? 'alternate' : '';
            $post_type_title = ucwords(str_replace('_', chr(32), $post_type));

            if (array_key_exists($post_type, $ignored_events)) {
                $is_ignored_text = 'YES';
                $was_ignored_at = SucuriScan::datetime($ignored_events[ $post_type ]);
                $is_ignored_class = 'danger';
                $button_action = 'remove';
                $button_class = 'button-primary';
                $button_text = 'Allow';
            } else {
                $is_ignored_text = 'NO';
                $was_ignored_at = 'Not ignored';
                $is_ignored_class = 'success';
                $button_action = 'add';
                $button_class = 'button-primary button-danger';
                $button_text = 'Ignore';
            }

            $template_variables['IgnoreRules.PostTypes'] .= SucuriScanTemplate::getSnippet(
                'settings-ignorerules',
                array(
                    'IgnoreRules.CssClass' => $css_class,
                    'IgnoreRules.Num' => $counter,
                    'IgnoreRules.PostTypeTitle' => $post_type_title,
                    'IgnoreRules.IsIgnored' => $is_ignored_text,
                    'IgnoreRules.WasIgnoredAt' => $was_ignored_at,
                    'IgnoreRules.IsIgnoredClass' => $is_ignored_class,
                    'IgnoreRules.PostType' => $post_type,
                    'IgnoreRules.Action' => $button_action,
                    'IgnoreRules.ButtonClass' => 'button ' . $button_class,
                    'IgnoreRules.ButtonText' => $button_text,
                )
            );
        }
    }

    return SucuriScanTemplate::getSection('settings-ignorerules', $template_variables);
}

/**
 * Read and parse the content of the API service settings template.
 *
 * @return string Parsed HTML code for the API service settings panel.
 */
function sucuriscan_settings_apiservice($nonce)
{
    $params = array();

    $params['SettingsSection.ApiStatus'] = sucuriscan_settings_apiservice_status($nonce);
    $params['SettingsSection.ApiProxy'] = sucuriscan_settings_apiservice_proxy($nonce);
    $params['SettingsSection.ApiSSL'] = sucuriscan_settings_apiservice_ssl($nonce);
    $params['SettingsSection.ApiTimeout'] = sucuriscan_settings_apiservice_timeout($nonce);

    return SucuriScanTemplate::getSection('settings-apiservice', $params);
}

function sucuriscan_settings_apiservice_status($nonce)
{
    $params = array();
    $params['ApiStatus.StatusNum'] = '1';
    $params['ApiStatus.Status'] = 'Enabled';
    $params['ApiStatus.SwitchText'] = 'Disable';
    $params['ApiStatus.SwitchValue'] = 'disable';
    $params['ApiStatus.SwitchCssClass'] = 'button-danger';
    $params['ApiStatus.WarningVisibility'] = 'visible';
    $params['ApiStatus.ErrorVisibility'] = 'hidden';

    if ($nonce) {
        // Enable or disable the API service communication.
        if ($api_service = SucuriScanRequest::post(':api_service', '(en|dis)able')) {
            $action_d = $api_service . 'd';
            $message = 'API service communication was <code>' . $action_d . '</code>';

            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanOption::update_option(':api_service', $action_d);
            SucuriScanInterface::info($message);
        }
    }

    $api_service = SucuriScanOption::get_option(':api_service');

    if ($api_service === 'disabled') {
        $params['ApiStatus.StatusNum'] = '0';
        $params['ApiStatus.Status'] = 'Disabled';
        $params['ApiStatus.SwitchText'] = 'Enable';
        $params['ApiStatus.SwitchValue'] = 'enable';
        $params['ApiStatus.SwitchCssClass'] = 'button-success';
        $params['ApiStatus.WarningVisibility'] = 'hidden';
        $params['ApiStatus.ErrorVisibility'] = 'visible';
    }

    return SucuriScanTemplate::getSection('settings-apiservice-status', $params);
}

function sucuriscan_settings_apiservice_proxy($nonce)
{
    $params = array(
        'APIProxy.Host' => 'no_proxy_host',
        'APIProxy.Port' => 'no_proxy_port',
        'APIProxy.Username' => 'no_proxy_username',
        'APIProxy.Password' => 'no_proxy_password',
        'APIProxy.PasswordType' => 'default',
        'APIProxy.PasswordText' => 'empty',
    );

    if (class_exists('WP_HTTP_Proxy')) {
        $wp_http_proxy = new WP_HTTP_Proxy();

        if ($wp_http_proxy->is_enabled()) {
            $proxy_host = SucuriScan::escape($wp_http_proxy->host());
            $proxy_port = SucuriScan::escape($wp_http_proxy->port());
            $proxy_username = SucuriScan::escape($wp_http_proxy->username());
            $proxy_password = SucuriScan::escape($wp_http_proxy->password());

            $params['APIProxy.Host'] = $proxy_host;
            $params['APIProxy.Port'] = $proxy_port;
            $params['APIProxy.Username'] = $proxy_username;
            $params['APIProxy.Password'] = $proxy_password;
            $params['APIProxy.PasswordType'] = 'info';
            $params['APIProxy.PasswordText'] = 'hidden';

        }
    }

    return SucuriScanTemplate::getSection('settings-apiservice-proxy', $params);
}

function sucuriscan_settings_apiservice_ssl($nonce)
{
    global $sucuriscan_verify_ssl_cert;

    $params = array(
        'VerifySSLCert' => 'Undefined',
        'VerifySSLCertCssClass' => 0,
        'VerifySSLCertOptions' => '',
    );

    // Update the configuration for the SSL certificate verification.
    if ($nonce) {
        $verify_ssl_cert = SucuriScanRequest::post(':verify_ssl_cert');

        if ($verify_ssl_cert) {
            if (array_key_exists($verify_ssl_cert, $sucuriscan_verify_ssl_cert)) {
                $message = 'SSL certificate verification for API calls set to <code>' . $verify_ssl_cert . '</code>';

                SucuriScanOption::update_option(':verify_ssl_cert', $verify_ssl_cert);
                SucuriScanEvent::report_warning_event($message);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Invalid value for the SSL certificate verification.');
            }
        }
    }

    $verify_ssl_cert = SucuriScanOption::get_option(':verify_ssl_cert');
    $params['VerifySSLCertOptions'] = SucuriScanTemplate::selectOptions(
        $sucuriscan_verify_ssl_cert,
        $verify_ssl_cert
    );

    if (array_key_exists($verify_ssl_cert, $sucuriscan_verify_ssl_cert)) {
        $params['VerifySSLCert'] = $sucuriscan_verify_ssl_cert[$verify_ssl_cert];

        if ($verify_ssl_cert === 'true') {
            $params['VerifySSLCertCssClass'] = 1;
        }
    }

    return SucuriScanTemplate::getSection('settings-apiservice-ssl', $params);
}

function sucuriscan_settings_apiservice_timeout($nonce)
{
    $params = array();

    // Update the API request timeout.
    if ($nonce) {
        $timeout = (int) SucuriScanRequest::post(':request_timeout', '[0-9]+');

        if ($timeout > 0) {
            if ($timeout <= SUCURISCAN_MAX_REQUEST_TIMEOUT) {
                $message = 'API request timeout set to <code>' . $timeout . '</code> seconds.';

                SucuriScanOption::update_option(':request_timeout', $timeout);
                SucuriScanEvent::report_info_event($message);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('API request timeout in seconds is too high.');
            }
        }
    }

    $params['MaxRequestTimeout'] = SUCURISCAN_MAX_REQUEST_TIMEOUT;
    $params['RequestTimeout'] = SucuriScanOption::get_option(':request_timeout') . ' seconds';

    return SucuriScanTemplate::getSection('settings-apiservice-timeout', $params);
}

/**
 * Read and parse the content of the self-hosting settings template.
 *
 * @return string Parsed HTML code for the self-hosting settings panel.
 */
function sucuriscan_settings_selfhosting($nonce)
{
    $params = array();

    $params['SelfHosting.Monitor'] = sucuriscan_settings_selfhosting_monitor($nonce);

    return SucuriScanTemplate::getSection('settings-selfhosting', $params);
}

function sucuriscan_selfhosting_fpath()
{
    $monitor = SucuriScanOption::get_option(':selfhosting_monitor');
    $monitor_fpath = SucuriScanOption::get_option(':selfhosting_fpath');

    if ($monitor === 'enabled'
        && !empty($monitor_fpath)
        && file_exists($monitor_fpath)
        && is_writable($monitor_fpath)
    ) {
        return $monitor_fpath;
    }

    return false;
}

function sucuriscan_settings_selfhosting_monitor($nonce)
{
    $params = array();

    $params['SelfHostingMonitor.DisabledVisibility'] = 'visible';
    $params['SelfHostingMonitor.Status'] = 'Enabled';
    $params['SelfHostingMonitor.SwitchText'] = 'Disable';
    $params['SelfHostingMonitor.SwitchValue'] = 'disable';
    $params['SelfHostingMonitor.SwitchCssClass'] = 'button-danger';
    $params['SelfHostingMonitor.FpathVisibility'] = 'hidden';
    $params['SelfHostingMonitor.Fpath'] = '';

    if ($nonce) {
        // Set a file path for the self-hosted event monitor.
        $monitor_fpath = SucuriScanRequest::post(':selfhosting_fpath');

        if ($monitor_fpath !== false) {
            if (empty($monitor_fpath)) {
                $message = 'Log exporter was disabled.';

                SucuriScanEvent::report_info_event($message);
                SucuriScanOption::delete_option(':selfhosting_fpath');
                SucuriScanOption::update_option(':selfhosting_monitor', 'disabled');
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            } elseif (strpos($monitor_fpath, $_SERVER['DOCUMENT_ROOT']) !== false) {
                SucuriScanInterface::error('File should not be publicly accessible.');
            } elseif (!file_exists($monitor_fpath)) {
                SucuriScanInterface::error('File path does not exists.');
            } elseif (!is_writable($monitor_fpath)) {
                SucuriScanInterface::error('File path is not writable.');
            } else {
                $message = 'Log exporter file path was set correctly.';

                SucuriScanEvent::report_info_event($message);
                SucuriScanOption::update_option(':selfhosting_monitor', 'enabled');
                SucuriScanOption::update_option(':selfhosting_fpath', $monitor_fpath);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }
    }

    $monitor = SucuriScanOption::get_option(':selfhosting_monitor');
    $monitor_fpath = SucuriScanOption::get_option(':selfhosting_fpath');

    if ($monitor === 'disabled') {
        $params['SelfHostingMonitor.Status'] = 'Disabled';
        $params['SelfHostingMonitor.SwitchText'] = 'Enable';
        $params['SelfHostingMonitor.SwitchValue'] = 'enable';
        $params['SelfHostingMonitor.SwitchCssClass'] = 'button-success';
    }

    if ($monitor === 'enabled' && $monitor_fpath) {
        $params['SelfHostingMonitor.DisabledVisibility'] = 'hidden';
        $params['SelfHostingMonitor.FpathVisibility'] = 'visible';
        $params['SelfHostingMonitor.Fpath'] = SucuriScan::escape($monitor_fpath);
    }

    return SucuriScanTemplate::getSection('settings-selfhosting-monitor', $params);
}

/**
 * Read and parse the content of the trust-ip settings template.
 *
 * @return string Parsed HTML code for the trust-ip settings panel.
 */
function sucuriscan_settings_trust_ip()
{
    $params = array();
    $params['TrustedIPs.List'] = '';
    $params['TrustedIPs.NoItems.Visibility'] = 'visible';

    $cache = new SucuriScanCache('trustip');
    $trusted_ips = $cache->getAll();

    if ($trusted_ips) {
        $counter = 0;

        foreach ($trusted_ips as $cache_key => $ip_info) {
            $css_class = ($counter % 2 === 0) ? '' : 'alternate';

            if ($ip_info->cidr_range == 32) {
                $ip_info->cidr_format = 'n/a';
            }

            $params['TrustedIPs.List'] .= SucuriScanTemplate::getSnippet(
                'settings-trustip',
                array(
                    'TrustIP.CssClass' => $css_class,
                    'TrustIP.CacheKey' => $cache_key,
                    'TrustIP.RemoteAddr' => SucuriScan::escape($ip_info->remote_addr),
                    'TrustIP.CIDRFormat' => SucuriScan::escape($ip_info->cidr_format),
                    'TrustIP.AddedAt' => SucuriScan::datetime($ip_info->added_at),
                )
            );

            $counter++;
        }

        if ($counter > 0) {
            $params['TrustedIPs.NoItems.Visibility'] = 'hidden';
        }
    }

    return SucuriScanTemplate::getSection('settings-trustip', $params);
}


/**
 * Read and parse the content of the heartbeat settings template.
 *
 * @return string Parsed HTML code for the heartbeat settings panel.
 */
function sucuriscan_settings_heartbeat()
{
    // Current values set in the options table.
    $heartbeat_status = SucuriScanOption::get_option(':heartbeat');
    $heartbeat_pulse = SucuriScanOption::get_option(':heartbeat_pulse');
    $heartbeat_interval = SucuriScanOption::get_option(':heartbeat_interval');
    $heartbeat_autostart = SucuriScanOption::get_option(':heartbeat_autostart');

    // Allowed values for each setting.
    $statuses_allowed = SucuriScanHeartbeat::statuses_allowed();
    $pulses_allowed = SucuriScanHeartbeat::pulses_allowed();
    $intervals_allowed = SucuriScanHeartbeat::intervals_allowed();

    // HTML select form fields.
    $heartbeat_options = SucuriScanTemplate::selectOptions($statuses_allowed, $heartbeat_status);
    $heartbeat_pulse_options = SucuriScanTemplate::selectOptions($pulses_allowed, $heartbeat_pulse);
    $heartbeat_interval_options = SucuriScanTemplate::selectOptions($intervals_allowed, $heartbeat_interval);

    $params = array(
        'HeartbeatStatus' => 'Undefined',
        'HeartbeatPulse' => 'Undefined',
        'HeartbeatInterval' => 'Undefined',
        /* Heartbeat Options. */
        'HeartbeatStatusOptions' => $heartbeat_options,
        'HeartbeatPulseOptions' => $heartbeat_pulse_options,
        'HeartbeatIntervalOptions' => $heartbeat_interval_options,
        /* Heartbeat Auto-Start. */
        'HeartbeatAutostart' => 'Enabled',
        'HeartbeatAutostartSwitchText' => 'Disable',
        'HeartbeatAutostartSwitchValue' => 'disable',
        'HeartbeatAutostartSwitchCssClass' => 'button-danger',
    );

    if (array_key_exists($heartbeat_status, $statuses_allowed)) {
        $params['HeartbeatStatus'] = $statuses_allowed[ $heartbeat_status ];
    }

    if (array_key_exists($heartbeat_pulse, $pulses_allowed)) {
        $params['HeartbeatPulse'] = $pulses_allowed[ $heartbeat_pulse ];
    }

    if (array_key_exists($heartbeat_interval, $intervals_allowed)) {
        $params['HeartbeatInterval'] = $intervals_allowed[ $heartbeat_interval ];
    }

    if ($heartbeat_autostart == 'disabled') {
        $params['HeartbeatAutostart'] = 'Disabled';
        $params['HeartbeatAutostartSwitchText'] = 'Enable';
        $params['HeartbeatAutostartSwitchValue'] = 'enable';
        $params['HeartbeatAutostartSwitchCssClass'] = 'button-success';
    }

    return SucuriScanTemplate::getSection('settings-heartbeat', $params);
}

/**
 * Print a HTML code with the settings of the plugin.
 *
 * @return void
 */
function sucuriscan_settings_page()
{
    SucuriScanInterface::check_permissions();

    $params = array();
    $nonce = SucuriScanInterface::check_nonce();

    $params['PageTitle'] = 'Settings';
    $params['Settings.General'] = sucuriscan_settings_general($nonce);
    $params['Settings.Scanner'] = sucuriscan_settings_scanner();
    $params['Settings.Alerts'] = sucuriscan_settings_alert($nonce);
    $params['Settings.ApiService'] = sucuriscan_settings_apiservice($nonce);
    $params['Settings.SelfHosting'] = sucuriscan_settings_selfhosting($nonce);
    $params['Settings.IgnoreScanning'] = sucuriscan_settings_ignorescanning();
    $params['Settings.IgnoreRules'] = sucuriscan_settings_ignore_rules();
    $params['Settings.TrustIP'] = sucuriscan_settings_trust_ip();
    $params['Settings.Heartbeat'] = sucuriscan_settings_heartbeat();

    echo SucuriScanTemplate::getTemplate('settings', $params);
}

/**
 * Generate and print the HTML code for the InfoSys page.
 *
 * This page will contains information of the system where the site is hosted,
 * also information about users in session, htaccess rules and configuration
 * options.
 *
 * @return void
 */
function sucuriscan_infosys_page()
{
    SucuriScanInterface::check_permissions();

    // Process all form submissions.
    sucuriscan_infosys_form_submissions();

    // Page pseudo-variables initialization.
    $params = array(
        'PageTitle' => 'Site Info',
        'ServerInfo' => sucuriscan_server_info(),
        'Cronjobs' => sucuriscan_show_cronjobs(),
        'HTAccessIntegrity' => sucuriscan_infosys_htaccess(),
        'WordpressConfig' => sucuriscan_infosys_wpconfig(),
        'ErrorLogs' => sucuriscan_infosys_errorlogs(),
    );

    echo SucuriScanTemplate::getTemplate('infosys', $params);
}

/**
 * Find the main htaccess file for the site and check whether the rules of the
 * main htaccess file of the site are the default rules generated by WordPress.
 *
 * @return string The HTML code displaying the information about the HTAccess rules.
 */
function sucuriscan_infosys_htaccess()
{
    $htaccess_path = SucuriScan::get_htaccess_path();
    $params = array(
        'HTAccess.Content' => '',
        'HTAccess.TextareaVisible' => 'hidden',
        'HTAccess.StandardVisible' => 'hidden',
        'HTAccess.NotFoundVisible' => 'hidden',
        'HTAccess.FoundVisible' => 'hidden',
        'HTAccess.Fpath' => 'unknown',
    );

    if ($htaccess_path) {
        $htaccess_rules = @file_get_contents($htaccess_path);

        $params['HTAccess.TextareaVisible'] = 'visible';
        $params['HTAccess.Content'] = $htaccess_rules;
        $params['HTAccess.Fpath'] = $htaccess_path;
        $params['HTAccess.FoundVisible'] = 'visible';

        if (sucuriscan_htaccess_is_standard($htaccess_rules)) {
            $params['HTAccess.StandardVisible'] = 'visible';
        }
    } else {
        $params['HTAccess.NotFoundVisible'] = 'visible';
    }

    return SucuriScanTemplate::getSection('infosys-htaccess', $params);
}

/**
 * Check if the standard rules for a normal WordPress installation (not network
 * based) are inside the main htaccess file. This only applies to websites that
 * have permalinks enabled, or 3rd-party plugins that require custom rules
 * (generally based on mod_deflate) to compress and/or generate static files for
 * cache.
 *
 * @param  string  $rules Content of the main htaccess file.
 * @return boolean        True if the htaccess has the standard rules, false otherwise.
 */
function sucuriscan_htaccess_is_standard($rules = false)
{
    if ($rules === false) {
        $rules = '';
        $htaccess_path = SucuriScan::get_htaccess_path();

        if ($htaccess_path) {
            $rules = @file_get_contents($htaccess_path);
        }
    }

    if (is_string($rules) && !empty($rules)) {
        $rewrite = new WP_Rewrite();
        $standard = $rewrite->mod_rewrite_rules();

        return (bool) (strpos($rules, $standard) !== false);
    }

    return false;
}

/**
 * Retrieve all the constants and variables with their respective values defined
 * in the WordPress configuration file, only the database password constant is
 * omitted for security reasons.
 *
 * @return string The HTML code displaying the constants and variables found in the wp-config file.
 */
function sucuriscan_infosys_wpconfig()
{
    $params = array(
        'WordpressConfig.Rules' => '',
        'WordpressConfig.Total' => 0,
    );

    $ignore_wp_rules = array('DB_PASSWORD');
    $wp_config_path = SucuriScan::get_wpconfig_path();

    if ($wp_config_path) {
        $wp_config_rules = array();
        $wp_config_content = SucuriScanFileInfo::file_lines($wp_config_path);

        // Parse the main configuration file and look for constants and global variables.
        foreach ((array) $wp_config_content as $line) {
            if (@preg_match('/^\s?(#|\/\/)/', $line)) {
                continue; /* Ignore commented lines. */
            } elseif (@preg_match('/define\(/', $line)) {
                // Detect PHP constants even if the line if indented.
                $line = preg_replace('/.*define\((.+)\);.*/', '$1', $line);
                $line_parts = explode(',', $line, 2);
            } elseif (@preg_match('/^\$[a-zA-Z_]+/', $line)) {
                // Detect global variables like the database table prefix.
                $line = @preg_replace('/;\s\/\/.*/', ';', $line);
                $line_parts = explode('=', $line, 2);
            } else {
                continue; /* Ignore other lines. */
            }

            // Clean and append the rule to the wp_config_rules variable.
            if (isset($line_parts) && count($line_parts) === 2) {
                $key_name = '';
                $key_value = '';

                // TODO: A foreach loop is not really necessary, find a better way.
                foreach ($line_parts as $i => $line_part) {
                    $line_part = trim($line_part);
                    $line_part = ltrim($line_part, '$');
                    $line_part = rtrim($line_part, ';');

                    // Remove single/double quotes at the beginning and end of the string.
                    $line_part = ltrim($line_part, "'");
                    $line_part = rtrim($line_part, "'");
                    $line_part = ltrim($line_part, '"');
                    $line_part = rtrim($line_part, '"');

                    // Assign the clean strings to specific variables.
                    if ($i == 0) {
                        $key_name = $line_part;
                    }

                    if ($i == 1) {
                        if (defined($key_name)) {
                            $key_value = constant($key_name);

                            if (is_bool($key_value)) {
                                $key_value = ($key_value === true) ? 'True' : 'False';
                            }
                        } else {
                            $key_value = $line_part;
                        }
                    }
                }

                // Remove the value of sensitive variables like the database password.
                if (in_array($key_name, $ignore_wp_rules)) {
                    $key_value = 'hidden';
                }

                // Append the value to the configuration rules.
                $wp_config_rules[$key_name] = $key_value;
            }
        }

        // Pass the WordPress configuration rules to the template and show them.
        $counter = 0;
        foreach ($wp_config_rules as $var_name => $var_value) {
            $css_class = ($counter % 2 === 0) ? '' : 'alternate';
            $label_css = 'sucuriscan-monospace';

            if (empty($var_value)) {
                $var_value = 'empty';
                $label_css = 'sucuriscan-label-default';
            } elseif ($var_value == 'hidden') {
                $label_css = 'sucuriscan-label-info';
            }

            $params['WordpressConfig.Total'] += 1;
            $params['WordpressConfig.Rules'] .= SucuriScanTemplate::getSnippet(
                'infosys-wpconfig',
                array(
                    'WordpressConfig.VariableName' => $var_name,
                    'WordpressConfig.VariableValue' => $var_value,
                    'WordpressConfig.VariableCssClass' => $label_css,
                    'WordpressConfig.CssClass' => $css_class,
                )
            );
            $counter++;
        }
    }

    return SucuriScanTemplate::getSection('infosys-wpconfig', $params);
}

/**
 * Retrieve a list with the scheduled tasks configured for the site.
 *
 * @return array A list of pseudo-variables and values that will replace them in the HTML template.
 */
function sucuriscan_show_cronjobs()
{
    $params = array(
        'Cronjobs.List' => '',
        'Cronjobs.Total' => 0,
    );

    $cronjobs = _get_cron_array();
    $schedules = wp_get_schedules();
    $counter = 0;

    foreach ($cronjobs as $timestamp => $cronhooks) {
        foreach ((array) $cronhooks as $hook => $events) {
            foreach ((array) $events as $key => $event) {
                if (empty($event['args'])) {
                    $event['args'] = array('[]');
                }

                $params['Cronjobs.Total'] += 1;
                $params['Cronjobs.List'] .= SucuriScanTemplate::getSnippet(
                    'infosys-cronjobs',
                    array(
                        'Cronjob.Hook' => $hook,
                        'Cronjob.Schedule' => $event['schedule'],
                        'Cronjob.NextTime' => SucuriScan::datetime($timestamp),
                        'Cronjob.Arguments' => SucuriScan::implode(', ', $event['args']),
                        'Cronjob.CssClass' => ($counter % 2 === 0) ? '' : 'alternate',
                    )
                );
                $counter++;
            }
        }
    }

    return SucuriScanTemplate::getSection('infosys-cronjobs', $params);
}

/**
 * Process the requests sent by the form submissions originated in the infosys
 * page, all forms must have a nonce field that will be checked against the one
 * generated in the template render function.
 *
 * @param  boolean $page_nonce True if the nonce is valid, False otherwise.
 * @return void
 */
function sucuriscan_infosys_form_submissions()
{
    if (SucuriScanInterface::check_nonce()) {
        // Modify the scheduled tasks (run now, remove, re-schedule).
        $allowed_actions = '(runnow|hourly|twicedaily|daily|remove)';

        if ($cronjob_action = SucuriScanRequest::post(':cronjob_action', $allowed_actions)) {
            $cronjobs = SucuriScanRequest::post(':cronjobs', '_array');

            if (!empty($cronjobs)) {
                $total_tasks = count($cronjobs);

                // Force execution of the selected scheduled tasks.
                if ($cronjob_action == 'runnow') {
                    SucuriScanInterface::info($total_tasks . ' tasks were scheduled to run in the next ten seconds.');
                    SucuriScanEvent::report_notice_event(sprintf(
                        'Force execution of scheduled tasks: (multiple entries): %s',
                        @implode(',', $cronjobs)
                    ));

                    foreach ($cronjobs as $task_name) {
                        wp_schedule_single_event(time() + 10, $task_name);
                    }
                } // Force deletion of the selected scheduled tasks.
                elseif ($cronjob_action == 'remove') {
                    SucuriScanInterface::info($total_tasks . ' scheduled tasks were removed.');
                    SucuriScanEvent::report_notice_event(sprintf(
                        'Delete scheduled tasks: (multiple entries): %s',
                        @implode(',', $cronjobs)
                    ));

                    foreach ($cronjobs as $task_name) {
                        wp_clear_scheduled_hook($task_name);
                    }
                } // Re-schedule the selected scheduled tasks.
                elseif ($cronjob_action == 'hourly'
                    || $cronjob_action == 'twicedaily'
                    || $cronjob_action == 'daily'
                ) {
                    SucuriScanInterface::info($total_tasks . ' tasks were re-scheduled to run <code>' . $cronjob_action . '</code>.');
                    SucuriScanEvent::report_notice_event(sprintf(
                        'Re-configure scheduled tasks %s: (multiple entries): %s',
                        $cronjob_action,
                        @implode(',', $cronjobs)
                    ));

                    foreach ($cronjobs as $task_name) {
                        wp_clear_scheduled_hook($task_name);
                        $next_due = wp_next_scheduled($task_name);
                        wp_schedule_event($next_due, $cronjob_action, $task_name);
                    }
                }
            } else {
                SucuriScanInterface::error('No scheduled tasks were selected from the list.');
            }
        }
    }
}

/**
 * Locate, parse and display the latest error logged in the main error_log file.
 *
 * @return array A list of pseudo-variables and values that will replace them in the HTML template.
 */
function sucuriscan_infosys_errorlogs()
{
    $params = array(
        'ErrorLog.Path' => '',
        'ErrorLog.Exists' => 'No',
        'ErrorLog.NoItemsVisibility' => 'hidden',
        'ErrorLog.DisabledVisibility' => 'hidden',
        'ErrorLog.InvalidFormatVisibility' => 'hidden',
        'ErrorLog.LogsLimit' => '0',
        'ErrorLog.FileSize' => '0B',
        'ErrorLog.List' => '',
    );

    $error_log_path = false;
    $log_filename = SucuriScan::ini_get('error_log');
    $errorlogs_limit = SucuriScanOption::get_option(':errorlogs_limit');
    $params['ErrorLog.LogsLimit'] = $errorlogs_limit;
    $counter = 0;

    if ($log_filename) {
        $error_log_path = @realpath(ABSPATH . '/' . $log_filename);
    }

    if (SucuriScanOption::is_disabled(':parse_errorlogs')) {
        $params['ErrorLog.DisabledVisibility'] = 'visible';
    }

    if ($error_log_path) {
        $params['ErrorLog.Exists'] = 'Yes';
        $params['ErrorLog.Path'] = $error_log_path;
        $params['ErrorLog.FileSize'] = SucuriScan::human_filesize(filesize($error_log_path));

        $last_lines = SucuriScanFileInfo::tail_file($error_log_path, $errorlogs_limit);
        $error_logs = SucuriScanFSScanner::parse_error_logs($last_lines);
        $error_logs = array_reverse($error_logs);
        $counter = 0;

        foreach ($error_logs as $error_log) {
            $css_class = ( $counter % 2 === 0 ) ? '' : 'alternate';
            $params['ErrorLog.List'] .= SucuriScanTemplate::getSnippet(
                'infosys-errorlogs',
                array(
                    'ErrorLog.CssClass' => $css_class,
                    'ErrorLog.DateTime' => SucuriScan::datetime($error_log->timestamp),
                    'ErrorLog.ErrorType' => $error_log->error_type,
                    'ErrorLog.ErrorCode' => $error_log->error_code,
                    'ErrorLog.ErrorAbbr' => strtoupper(substr($error_log->error_code, 0, 1)),
                    'ErrorLog.ErrorMessage' => $error_log->error_message,
                    'ErrorLog.FilePath' => $error_log->file_path,
                    'ErrorLog.LineNumber' => $error_log->line_number,
                )
            );
            $counter += 1;
        }

        if ($counter <= 0) {
            $params['ErrorLog.InvalidFormatVisibility'] = 'visible';
        }
    } else {
        $params['ErrorLog.NoItemsVisibility'] = 'visible';
    }

    return SucuriScanTemplate::getSection('infosys-errorlogs', $params);
}

/**
 * Gather information from the server, database engine, and PHP interpreter.
 *
 * @return array A list of pseudo-variables and values that will replace them in the HTML template.
 */
function sucuriscan_server_info()
{
    global $wpdb;

    $params = array(
        'ServerInfo.Variables' => '',
    );

    $info_vars = array(
        'Plugin_version' => SUCURISCAN_VERSION,
        'Plugin_checksum' => SUCURISCAN_PLUGIN_CHECKSUM,
        'Last_filesystem_scan' => SucuriScanFSScanner::get_filesystem_runtime(true),
        'Datetime_and_Timezone' => '',
        'Operating_system' => sprintf('%s (%d Bit)', PHP_OS, PHP_INT_SIZE * 8),
        'Server' => 'Unknown',
        'Developer_mode' => 'OFF',
        'Memory_usage' => 'N/A',
        'MySQL_version' => '0.0',
        'SQL_mode' => 'Not set',
        'PHP_version' => PHP_VERSION,
    );

    $info_vars['Datetime_and_Timezone'] = sprintf(
        '%s (GMT %s)',
        SucuriScan::current_datetime(),
        get_option('gmt_offset')
    );

    if (defined('WP_DEBUG') && WP_DEBUG) {
        $info_vars['Developer_mode'] = 'ON';
    }

    if (function_exists('memory_get_usage')) {
        $info_vars['Memory_usage'] = round(memory_get_usage() / 1024 / 1024, 2).' MB';
    }

    if (isset($_SERVER['SERVER_SOFTWARE'])) {
        $info_vars['Server'] = $_SERVER['SERVER_SOFTWARE'];
    }

    if ($wpdb) {
        $info_vars['MySQL_version'] = $wpdb->get_var('SELECT VERSION() AS version');

        $mysql_info = $wpdb->get_results('SHOW VARIABLES LIKE "sql_mode"');
        if (is_array($mysql_info) && !empty($mysql_info[0]->Value)) {
            $info_vars['SQL_mode'] = $mysql_info[0]->Value;
        }
    }

    $field_names = array(
        'safe_mode',
        'expose_php',
        'allow_url_fopen',
        'memory_limit',
        'upload_max_filesize',
        'post_max_size',
        'max_execution_time',
        'max_input_time',
    );

    foreach ($field_names as $php_flag) {
        $php_flag_value = SucuriScan::ini_get($php_flag);
        $php_flag_name = 'PHP_' . $php_flag;
        $info_vars[$php_flag_name] = $php_flag_value ? $php_flag_value : 'N/A';
    }

    $counter = 0;

    foreach ($info_vars as $var_name => $var_value) {
        $css_class = ($counter % 2 === 0) ? '' : 'alternate';
        $var_name = str_replace('_', "\x20", $var_name);

        $params['ServerInfo.Variables'] .= SucuriScanTemplate::getSnippet(
            'infosys-serverinfo',
            array(
                'ServerInfo.CssClass' => $css_class,
                'ServerInfo.Title' => $var_name,
                'ServerInfo.Value' => $var_value,
            )
        );
        $counter += 1;
    }

    return SucuriScanTemplate::getSection('infosys-serverinfo', $params);
}

