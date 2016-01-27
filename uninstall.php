<?php
/**
 * Uninstallation instructions.
 *
 * @package   Sucuri Security
 * @author    Yorman Arias <yorman.arias@sucuri.net>
 * @author    Daniel Cid   <dcid@sucuri.net>
 * @copyright Since 2010-2015 Sucuri Inc.
 * @license   Released under the GPL - see LICENSE file for details.
 * @link      https://wordpress.sucuri.net/
 * @since     File available since Release 0.1
 */

if (!defined('WP_UNINSTALL_PLUGIN')
    || WP_UNINSTALL_PLUGIN != 'sucuri-scanner/sucuri.php'
) {
    exit(0);
}

$sucuriscan_option_names = array(
    'account',
    'addr_header',
    'ads_visibility',
    'api_key',
    'api_service',
    'audit_report',
    'cloudproxy_apikey',
    'collect_wrong_passwords',
    'comment_monitor',
    'datastore_path',
    'dismiss_setup',
    'dns_lookups',
    'email_subject',
    'emails_per_hour',
    'emails_sent',
    'errorlogs_limit',
    'fs_scanner',
    'heartbeat',
    'heartbeat_autostart',
    'heartbeat_interval',
    'heartbeat_pulse',
    'ignore_scanning',
    'ignored_events',
    'last_email_at',
    'lastlogin_redirection',
    'logs4report',
    'maximum_failed_logins',
    'notify_bruteforce_attack',
    'notify_failed_login',
    'notify_plugin_activated',
    'notify_plugin_change',
    'notify_plugin_deactivated',
    'notify_plugin_deleted',
    'notify_plugin_installed',
    'notify_plugin_updated',
    'notify_post_publication',
    'notify_scan_checksums',
    'notify_settings_updated',
    'notify_success_login',
    'notify_theme_activated',
    'notify_theme_deleted',
    'notify_theme_editor',
    'notify_theme_installed',
    'notify_theme_updated',
    'notify_to',
    'notify_user_registration',
    'notify_website_updated',
    'notify_widget_added',
    'notify_widget_deleted',
    'parse_errorlogs',
    'prettify_mails',
    'request_timeout',
    'revproxy',
    'runtime',
    'scan_checksums',
    'scan_errorlogs',
    'scan_frequency',
    'scan_interface',
    'scan_modfiles',
    'selfhosting_fpath',
    'selfhosting_monitor',
    'site_version',
    'sitecheck_counter',
    'sitecheck_scanner',
    'use_wpmail',
    'verify_ssl_cert',
    'xhr_monitor',
);

$sucuriscan_storage_path = get_option('sucuriscan_datastore_path');

if ($sucuriscan_storage_path !== false
    && file_exists($sucuriscan_storage_path)
    && is_writable($sucuriscan_storage_path)
    && is_dir($sucuriscan_storage_path)
) {
    @unlink($sucuriscan_storage_path . '/.htaccess');
    @unlink($sucuriscan_storage_path . '/index.html');
    @unlink($sucuriscan_storage_path . '/sucuri-failedlogins.php');
    @unlink($sucuriscan_storage_path . '/sucuri-ignorescanning.php');
    @unlink($sucuriscan_storage_path . '/sucuri-integrity.php');
    @unlink($sucuriscan_storage_path . '/sucuri-lastlogins.php');
    @unlink($sucuriscan_storage_path . '/sucuri-oldfailedlogins.php');
    @unlink($sucuriscan_storage_path . '/sucuri-plugindata.php');
    @unlink($sucuriscan_storage_path . '/sucuri-sitecheck.php');
    @unlink($sucuriscan_storage_path . '/sucuri-trustip.php');

    @rmdir($sucuriscan_storage_path);
}

foreach ($sucuriscan_option_names as $option_name) {
    delete_option('sucuriscan_' . $option_name);
    delete_site_option('sucuriscan_' . $option_name);
}

// EOF
