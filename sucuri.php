<?php
/*
Plugin Name: Sucuri Security - Auditing, Malware Scanner and Hardening
Plugin URI: https://wordpress.sucuri.net/
Description: The <a href="https://sucuri.net/" target="_blank">Sucuri</a> plugin provides the website owner the best Activity Auditing, SiteCheck Remote Malware Scanning, Effective Security Hardening and Post-Hack features. SiteCheck will check for malware, spam, blacklisting and other security issues like .htaccess redirects, hidden eval code, etc. The best thing about it is it's completely free.
Author: Sucuri, INC
Version: 1.8.3
Author URI: https://sucuri.net
*/


/**
 * Main file to control the plugin.
 *
 * The constant will be used in the additional PHP files to determine if the
 * code is being called from a legitimate interface or not. It is expected that
 * during the direct access of any of the extra PHP files the interpreter will
 * return a 403/Forbidden response and immediately exit the execution, this will
 * prevent unwanted access to code with unmet dependencies.
 *
 * @package   Sucuri Security
 * @author    Daniel Cid   <dcid@sucuri.net>
 * @copyright Since 2010-2015 Sucuri Inc.
 * @license   Released under the GPL - see LICENSE file for details.
 * @link      https://wordpress.sucuri.net/
 * @since     File available since Release 0.1
 */
define('SUCURISCAN_INIT', true);

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
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
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
define('SUCURISCAN_VERSION', '1.8.3');

/**
 * The name of the Sucuri plugin main file.
 */
define('SUCURISCAN_PLUGIN_FILE', 'sucuri.php');

/**
 * The name of the folder where the plugin's files will be located.
 *
 * Note that we are using the constant FILE instead of DIR because some
 * installations of PHP are either outdated or are not supporting the access to
 * that definition, to keep things simple we will select the name of the
 * directory name of the current file, then select the base name of that
 * directory.
 */
define('SUCURISCAN_PLUGIN_FOLDER', basename(dirname(__FILE__)));

/**
 * The fullpath where the plugin's files will be located.
 */
define('SUCURISCAN_PLUGIN_PATH', WP_PLUGIN_DIR . '/' . SUCURISCAN_PLUGIN_FOLDER);

/**
 * The fullpath of the main plugin file.
 */
define('SUCURISCAN_PLUGIN_FILEPATH', SUCURISCAN_PLUGIN_PATH . '/' . SUCURISCAN_PLUGIN_FILE);

/**
 * The local URL where the plugin's files and assets are served.
 */
define('SUCURISCAN_URL', site_url(dirname(str_replace(ABSPATH, '', SUCURISCAN_PLUGIN_FILEPATH))));

/**
 * Remote URL where the public Sucuri API service is running.
 *
 * We will check if the constant was already set to allow developers to use
 * their own API service. This is useful both for the execution of the tests
 * as well as for website owners who do not want to send data to the Sucuri
 * servers.
 */
if (!defined('SUCURISCAN_API_URL')) {
    define('SUCURISCAN_API_URL', 'https://wordpress.sucuri.net/api/');
}

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
 * The life time of the cache for the audit logs to help API perforamnce.
 */
define('SUCURISCAN_AUDITLOGS_LIFETIME', 600);

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
 * The maximum execution time for SiteCheck requests before timeout.
 */
define('SUCURISCAN_MAX_SITECHECK_TIMEOUT', 60);

/**
 * Sets the text that will preceed the admin notices.
 *
 * If you have defined SUCURISCAN_THROW_EXCEPTIONS to throw a generic exception
 * when an info or error alert is triggered, this text will be replaced by the
 * type of alert that was fired (either Info or Error respectively) which is
 * useful when you are executing code in a testing environment.
 */
define('SUCURISCAN_ADMIN_NOTICE_PREFIX', '<b>SUCURI:</b>');

/* Fix missing server name in non-webview context */
if (!array_key_exists('SERVER_NAME', $_SERVER)) {
    $_SERVER['SERVER_NAME'] = 'localhost';
}

/* Load all classes before anything else. */
require_once('src/sucuriscan.lib.php');
require_once('src/request.lib.php');
require_once('src/fileinfo.lib.php');
require_once('src/cache.lib.php');
require_once('src/option.lib.php');
require_once('src/event.lib.php');
require_once('src/hook.lib.php');
require_once('src/api.lib.php');
require_once('src/mail.lib.php');
require_once('src/command.lib.php');
require_once('src/template.lib.php');
require_once('src/fsscanner.lib.php');
require_once('src/hardening.lib.php');
require_once('src/interface.lib.php');
require_once('src/auditlogs.lib.php');
require_once('src/sitecheck.lib.php');
require_once('src/integrity.lib.php');
require_once('src/installer-skin.lib.php');

/* Load page and ajax handlers */
require_once('src/pagehandler.php');

/* Load handlers for main pages. */
require_once('src/firewall.php');

/* Load handlers for main pages (lastlogins). */
require_once('src/lastlogins.php');
require_once('src/lastlogins-loggedin.php');
require_once('src/lastlogins-failed.php');
require_once('src/lastlogins-blocked.php');

/* Load handlers for main pages (settings). */
require_once('src/settings.php');
require_once('src/settings-general.php');
require_once('src/settings-scanner.php');
require_once('src/settings-integrity.php');
require_once('src/settings-sitecheck.php');
require_once('src/settings-hardening.php');
require_once('src/settings-posthack.php');
require_once('src/settings-alerts.php');
require_once('src/settings-apiservice.php');
require_once('src/settings-webinfo.php');

/* Load global variables and triggers */
require_once('src/globals.php');

function sucuriscan_deactivate()
{
    /* Remove scheduled task from the system */
    wp_clear_scheduled_hook('sucuriscan_scheduled_scan');

    /* Remove settings from the database if they exist */
    $options = SucuriScanOption::getDefaultOptionNames();
    foreach ($options as $option_name) {
        delete_site_option($option_name);
        delete_option($option_name);
    }

    /* Remove hardening in standard directories */
    SucuriScanHardening::dewhitelist('ms-files.php', 'wp-includes');
    SucuriScanHardening::dewhitelist('wp-tinymce.php', 'wp-includes');
    SucuriScanHardening::unhardenDirectory(WP_CONTENT_DIR);
    SucuriScanHardening::unhardenDirectory(WP_CONTENT_DIR . '/uploads');
    SucuriScanHardening::unhardenDirectory(ABSPATH . '/wp-includes');
    SucuriScanHardening::unhardenDirectory(ABSPATH . '/wp-admin');

    /* Remove cache files from disk */
    $fifo = new SucuriScanFileInfo();
    $fifo->ignore_files = false;
    $fifo->ignore_directories = false;
    $fifo->run_recursively = false;
    $directory = SucuriScanOption::getOption(':datastore_path');
    $fifo->scan_interface = SucuriScanOption::getOption(':scan_interface');
    $fifo->removeDirectoryTree($directory);
}

register_deactivation_hook(__FILE__, 'sucuriscan_deactivate');
