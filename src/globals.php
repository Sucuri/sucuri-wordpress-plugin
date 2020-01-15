<?php

/**
 * Code related to the globals.php interface.
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
 * Plugin's global variables.
 *
 * These variables will be defined globally to allow the inclusion in multiple
 * methods and classes defined in the libraries loaded by this plugin. The
 * conditional will act as a container helping in the readability of the code
 * considering the total number of lines that this file will have.
 */
if (defined('SUCURISCAN')) {
    /**
     * Define the prefix for some actions and filters that rely in the differen-
     * tiation of the type of site where the extension is being used. There are
     * a few differences between a single site installation that must be
     * correctly defined when the extension is in a different environment, for
     * example, in a multisite installation.
     *
     * @var string
     */
    $sucuriscan_action_prefix = SucuriScan::isMultiSite() ? 'network_' : '';

    /**
     * Remove the WordPress generator meta-tag from the source code.
     */
    remove_action('wp_head', 'wp_generator');

    /**
     * Run a specific method defined in the plugin's code to locate every
     * directory and file, collect their checksum and file size, and send this
     * information to the Sucuri API service where a security and integrity scan
     * will be performed against the hashes provided and the official versions.
     */
    add_action('sucuriscan_scheduled_scan', 'SucuriScan::runScheduledTask');

    /**
     * Initialize the execute of the main plugin's functions.
     *
     * This will load the menu options in the WordPress administrator panel, and
     * execute the bootstrap method of the plugin.
     */
    add_action('init', 'SucuriScanInterface::initialize', 1);
    add_action('admin_enqueue_scripts', 'SucuriScanInterface::enqueueScripts', 1);

    if (SucuriScan::runAdminInit()) {
        add_action('admin_init', 'SucuriScanInterface::handleOldPlugins');
        add_action('admin_init', 'SucuriScanInterface::createStorageFolder');
    }

    /**
     * Add cronjob weekly, monthly and quarterly frequencies.
     */
    add_filter('cron_schedules', 'SucuriScanEvent::additionalSchedulesFrequencies');

    /**
     * Add cronjob hooks methods.
     *
     * This is necessary because using add_action inside the feature class/method
     * will not be persistent. The hooks must be declared on every page load.
     */
    foreach (SucuriScanEvent::activeSchedules() as $hook => $details) {
        if (substr($hook, 0, strlen('sucuriscan_')) === 'sucuriscan_') {
            if (!has_action($hook)) {
                $methodLocation = array('SucuriScanCrons', $hook);
                if (method_exists($methodLocation[0], $methodLocation[1])) {
                    add_action($hook, $methodLocation);
                }
            }
        }
    }

    /**
     * List an associative array with the sub-pages of this plugin.
     *
     * @return array List of sub-pages of this plugin.
     */
    function sucuriscanMainPages()
    {
        return array(
            'sucuriscan' => __('Dashboard', 'sucuri-scanner'),
            'sucuriscan_firewall' => __('Firewall (WAF)', 'sucuri-scanner'),
            'sucuriscan_lastlogins' => __('Last Logins', 'sucuri-scanner'),
            'sucuriscan_settings' => __('Settings', 'sucuri-scanner'),
        );
    }

    if (function_exists('add_action')) {
        /**
         * Display extension menu and submenu items in the correct interface.
         * For single site installations the menu items can be displayed
         * normally as always but for multisite installations the menu items
         * must be available only in the network panel and hidden in the
         * administration panel of the subsites.
         *
         * @codeCoverageIgnore
         *
         * @return void
         */
        function sucuriscanAddMenuPage()
        {
            $pages = sucuriscanMainPages();

            add_menu_page(
                __('Sucuri Security', 'sucuri-scanner'),
                __('Sucuri Security', 'sucuri-scanner'),
                'manage_options',
                'sucuriscan',
                'sucuriscan_page',
                'data:image/svg+xml;base64,' . base64_encode('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid meet" viewBox="0 0 16 16" width="16" height="16"><defs><path d="M8.98 0.87L9.6 0.94L10.29 1.03L11.04 1.16L11.86 1.31L12.74 1.49L13.69 1.7L13.93 2.24L14.12 2.85L14.27 3.53L14.37 4.27L14.41 5.08L14.41 5.95L14.36 6.89L14.27 7.9L14.06 8.93L13.71 9.93L13.19 10.91L12.51 11.86L11.68 12.78L10.69 13.68L9.54 14.55L8.23 15.4L6.91 14.56L5.75 13.69L4.74 12.8L3.9 11.87L3.21 10.92L2.68 9.94L2.31 8.93L2.09 7.9L1.98 6.89L1.92 5.94L1.91 5.06L1.95 4.25L2.04 3.51L2.17 2.84L2.36 2.23L2.59 1.7L3.44 1.5L4.23 1.32L4.97 1.17L5.66 1.05L6.3 0.95L6.89 0.88L7.43 0.84L7.92 0.82L8.42 0.83L8.98 0.87ZM10.8 10.72L10.53 11.03L10.31 11.24L10.06 11.46L9.69 11.79L9.2 12.21L8.59 12.73L9.14 12.4L9.64 12.07L10.09 11.77L10.5 11.48L10.85 11.17L11.13 10.8L11.34 10.35L11.48 9.84L11.12 10.32L10.8 10.72ZM7.68 3.96L7.38 4.07L7.07 4.23L6.79 4.48L6.57 4.85L6.43 5.35L6.42 5.88L6.52 6.31L6.7 6.65L6.94 6.92L7.21 7.13L7.56 7.32L7.98 7.54L8.4 7.76L8.72 8L8.84 8.24L8.81 8.43L8.71 8.56L8.56 8.62L8.38 8.66L8.17 8.68L7.98 8.66L7.71 8.6L7.34 8.5L6.89 8.34L6.36 8.14L6.33 9.63L6.72 9.77L7.1 9.89L7.48 9.96L7.84 10L8.2 10.01L8.76 9.94L9.35 9.76L9.86 9.43L10.24 8.92L10.38 8.2L10.23 7.46L9.84 6.93L9.37 6.56L8.96 6.35L8.76 6.27L8.62 6.21L8.36 6.08L8.09 5.9L7.93 5.67L7.96 5.41L8.13 5.27L8.4 5.21L8.78 5.24L9.26 5.35L9.85 5.55L10.38 4.33L10.22 4.25L10.07 4.19L9.91 4.13L9.77 4.07L9.62 4.02L9.33 3.96L8.92 3.91L8.46 3.89L8.03 3.9L7.68 3.96Z" id="d1ogaCizF3"></path></defs><g><g><g><use xlink:href="#d1ogaCizF3" opacity="1" fill="black" fill-opacity="1"></use></g></g></g></svg>')
            );

            foreach ($pages as $sub_page_func => $sub_page_title) {
                add_submenu_page(
                    'sucuriscan',
                    $sub_page_title,
                    $sub_page_title,
                    'manage_options',
                    $sub_page_func,
                    $sub_page_func . '_page'
                );
            }
        }

        /* Attach HTTP request handlers for the internal plugin pages */
        add_action($sucuriscan_action_prefix . 'admin_menu', 'sucuriscanAddMenuPage');

        /* Attach HTTP request handlers for the AJAX requests */
        add_action('wp_ajax_sucuriscan_ajax', 'sucuriscan_ajax');
    }

    /**
     * Function call interceptors.
     *
     * Define the names for the hooks that will intercept specific method calls in
     * the admin interface and parts of the external site, an event report will be
     * sent to the API service and an email notification to the administrator of the
     * site.
     *
     * @see Class SucuriScanHook
     */
    if (class_exists('SucuriScanHook')) {
        add_action('activated_plugin', 'SucuriScanHook::hookPluginActivate', 50, 2);
        add_action('add_attachment', 'SucuriScanHook::hookAttachmentAdd', 50, 5);
        add_action('add_link', 'SucuriScanHook::hookLinkAdd', 50, 5);
        add_action('add_user_to_blog', 'SucuriScanHook::hookAddUserToBlog', 50, 4);
        add_action('before_delete_post', 'SucuriScanHook::hookPostBeforeDelete', 50, 5);
        add_action('create_category', 'SucuriScanHook::hookCategoryCreate', 50, 5);
        add_action('deactivated_plugin', 'SucuriScanHook::hookPluginDeactivate', 50, 2);
        add_action('delete_post', 'SucuriScanHook::hookPostDelete', 50, 5);
        add_action('delete_user', 'SucuriScanHook::hookUserDelete', 50, 5);
        add_action('edit_link', 'SucuriScanHook::hookLinkEdit', 50, 5);
        add_action('login_form_resetpass', 'SucuriScanHook::hookLoginFormResetpass', 50, 5);
        add_action('profile_update', 'SucuriScanHook::hookProfileUpdate', 50, 5);
        add_action('publish_page', 'SucuriScanHook::hookPublishPage', 50, 5);
        add_action('publish_phone', 'SucuriScanHook::hookPublishPhone', 50, 5);
        add_action('publish_post', 'SucuriScanHook::hookPublishPost', 50, 5);
        add_action('remove_user_from_blog', 'SucuriScanHook::hookRemoveUserFromBlog', 50, 2);
        add_action('retrieve_password', 'SucuriScanHook::hookRetrievePassword', 50, 5);
        add_action('switch_theme', 'SucuriScanHook::hookThemeSwitch', 50, 5);
        add_action('transition_post_status', 'SucuriScanHook::hookPostStatus', 50, 3);
        add_action('user_register', 'SucuriScanHook::hookUserRegister', 50, 5);
        add_action('wp_login', 'SucuriScanHook::hookLoginSuccess', 50, 5);
        add_action('wp_login_failed', 'SucuriScanHook::hookLoginFailure', 50, 5);
        add_action('wp_trash_post', 'SucuriScanHook::hookPostTrash', 50, 5);
        add_action('xmlrpc_publish_post', 'SucuriScanHook::hookPublishPostXMLRPC', 50, 5);

        if (SucuriScan::runAdminInit()) {
            add_action('admin_init', 'SucuriScanHook::hookCoreUpdate');
            add_action('admin_init', 'SucuriScanHook::hookOptionsManagement');
            add_action('admin_init', 'SucuriScanHook::hookPluginDelete');
            add_action('admin_init', 'SucuriScanHook::hookPluginEditor');
            add_action('admin_init', 'SucuriScanHook::hookPluginInstall');
            add_action('admin_init', 'SucuriScanHook::hookPluginUpdate');
            add_action('admin_init', 'SucuriScanHook::hookThemeDelete');
            add_action('admin_init', 'SucuriScanHook::hookThemeEditor');
            add_action('admin_init', 'SucuriScanHook::hookThemeInstall');
            add_action('admin_init', 'SucuriScanHook::hookThemeUpdate');
            add_action('admin_init', 'SucuriScanHook::hookWidgetAdd');
            add_action('admin_init', 'SucuriScanHook::hookWidgetDelete');
        }
    }
}
