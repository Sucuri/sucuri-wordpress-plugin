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
     * Hook the sucuriscan_autoseckeyupdater cron job.
     */
    add_action('sucuriscan_autoseckeyupdater', 'SucuriScanCrons::sucuriscan_autoseckeyupdater');

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
                'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="17.321" height="20" viewBox="0 0 17.321 20"><path id="Path_1098" data-name="Path 1098" d="M810.581,111.016v-8.14l8.66-5,7.049,4.07-3.524,2.035-3.525-2.035-5.136,2.965,3.525,2.035Zm10.271-2.209,3.525,2.035-5.136,2.965-3.525-2.035-3.525,2.035,7.049,4.07,8.661-5h0v-8.14Z" transform="translate(-810.581 -97.877)" fill="#f0f0f1"/></svg>')
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
            add_action('_core_updated_successfully', 'SucuriScanHook::hookCoreUpdate');
            add_action('admin_init', 'SucuriScanHook::hookOptionsManagement');
            add_action('deleted_plugin', 'SucuriScanHook::hookPluginDelete');
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
