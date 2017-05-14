<?php

/**
 * Code related to the globals.php interface.
 *
 * @package Sucuri Security
 * @subpackage globals.php
 * @copyright Since 2010 Sucuri Inc.
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
     * Define the prefix for some actions and filters that rely in the
     * differentiation of the type of site where the extension is being used. There
     * are a few differences between a single site installation that must be
     * correctly defined when the extension is in a different environment, for
     * example, in a multisite installation.
     *
     * @var string
     */
    $sucuriscan_action_prefix = SucuriScan::isMultiSite() ? 'network_' : '';

    /**
     * List an associative array with the sub-pages of this plugin.
     *
     * @return array
     */
    $sucuriscan_pages = array(
        'sucuriscan' => 'Dashboard',
        'sucuriscan_firewall' => 'Firewall (WAF)',
        'sucuriscan_lastlogins' => 'Last Logins',
        'sucuriscan_settings' => 'Settings',
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

    $sucuriscan_schedule_allowed = SucuriScanEvent::availableSchedules();

    $sucuriscan_notify_options = array(
        'sucuriscan_notify_plugin_change' => 'Receive email alerts for <b>Sucuri</b> plugin changes',
        'sucuriscan_prettify_mails' => 'Receive email alerts in HTML <em>(there may be issues with some mail services)</em>',
        'sucuriscan_use_wpmail' => 'Use WordPress functions to send mails <em>(uncheck to use native PHP functions)</em>',
        'sucuriscan_lastlogin_redirection' => 'Allow redirection after login to report the last-login information',
        'sucuriscan_notify_scan_checksums' => 'Receive email alerts for core integrity checks',
        'sucuriscan_notify_available_updates' => 'Receive email alerts for available updates',
        'sucuriscan_notify_user_registration' => 'user:Receive email alerts for new user registration',
        'sucuriscan_notify_success_login' => 'user:Receive email alerts for successful login attempts',
        'sucuriscan_notify_failed_login' => 'user:Receive email alerts for failed login attempts <em>(you may receive tons of emails)</em>',
        'sucuriscan_notify_bruteforce_attack' => 'user:Receive email alerts for password guessing attacks <em>(summary of failed logins per hour)</em>',
        'sucuriscan_notify_post_publication' => 'Receive email alerts for changes in the post status <em>(configure from Ignore Posts Changes)</em>',
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

    $sucuriscan_date_format = get_option('date_format');
    $sucuriscan_time_format = get_option('time_format');

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
    add_action('init', 'SucuriScanBlockedUsers::blockUserLogin', 1);
    add_action('admin_enqueue_scripts', 'SucuriScanInterface::enqueueScripts', 1);

    if (SucuriScan::runAdminInit()) {
        add_action('admin_init', 'SucuriScanInterface::handleOldPlugins');
        add_action('admin_init', 'SucuriScanInterface::createStorageFolder');
        add_action('admin_init', 'SucuriScanInterface::noticeAfterUpdate');
    }

    if (function_exists('add_action')) {
        /**
         * Display extension menu and submenu items in the correct interface.
         * For single site installations the menu items can be displayed
         * normally as always but for multisite installations the menu items
         * must be available only in the network panel and hidden in the
         * administration panel of the subsites.
         */
        function sucuriscan_add_menu_page()
        {
            global $sucuriscan_pages;

            add_menu_page(
                'Sucuri Security',
                'Sucuri Security',
                'manage_options',
                'sucuriscan',
                'sucuriscan_page',
                SUCURISCAN_URL . '/inc/images/menuicon.png'
            );

            /* exit if no pages were defined */
            if (!is_array($sucuriscan_pages)) {
                return;
            }

            foreach ($sucuriscan_pages as $sub_page_func => $sub_page_title) {
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
        add_action($sucuriscan_action_prefix . 'admin_menu', 'sucuriscan_add_menu_page');

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
        add_action('before_delete_post', 'SucuriScanHook::hookPostBeforeDelete', 50, 5);
        add_action('create_category', 'SucuriScanHook::hookCategoryCreate', 50, 5);
        add_action('deactivated_plugin', 'SucuriScanHook::hookPluginDeactivate', 50, 2);
        add_action('delete_post', 'SucuriScanHook::hookPostDelete', 50, 5);
        add_action('delete_user', 'SucuriScanHook::hookUserDelete', 50, 5);
        add_action('edit_link', 'SucuriScanHook::hookLinkEdit', 50, 5);
        add_action('login_form_resetpass', 'SucuriScanHook::hookLoginFormResetpass', 50, 5);
        add_action('publish_page', 'SucuriScanHook::hookPublishPage', 50, 5);
        add_action('publish_phone', 'SucuriScanHook::hookPublishPhone', 50, 5);
        add_action('publish_post', 'SucuriScanHook::hookPublishPost', 50, 5);
        add_action('retrieve_password', 'SucuriScanHook::hookRetrievePassword', 50, 5);
        add_action('switch_theme', 'SucuriScanHook::hookThemeSwitch', 50, 5);
        add_action('transition_post_status', 'SucuriScanHook::hookPostStatus', 50, 3);
        add_action('user_register', 'SucuriScanHook::hookUserRegister', 50, 5);
        add_action('wp_insert_comment', 'SucuriScanHook::hookCommentInsert', 50, 5);
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
    } else {
        SucuriScanInterface::error('Function call interceptors are not working properly.');
    }

    /**
     * Clear the firewall cache if necessary.
     *
     * Every time a page or post is modified and saved into the database the
     * plugin will send a HTTP request to the firewall API service and except
     * that, if the API key is valid, the cache is reset. Notice that the cache
     * of certain files is going to stay as it is due to the configuration on the
     * edge of the servers.
     */
    add_action('save_post', 'SucuriScanFirewall::clearCacheHook');
}
