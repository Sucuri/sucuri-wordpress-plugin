<?php

/**
 * Plugin Name: Sucuri Security - Auditing, Malware Scanner and Hardening
 * Description: The <a href="https://sucuri.net/" target="_blank">Sucuri</a> plugin provides the website owner the best Activity Auditing, SiteCheck Remote Malware Scanning, Effective Security Hardening and Post-Hack features. SiteCheck will check for malware, spam, blocklisting and other security issues like .htaccess redirects, hidden eval code, etc. The best thing about it is it's completely free.
 * Plugin URI: https://wordpress.sucuri.net/
 * Author URI: https://sucuri.net/
 * Author: Sucuri Inc.
 * Text Domain: sucuri-scanner
 * Domain Path: /lang
 * Version: 1.8.44
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * PHP version 7
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2024 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */

/**
 * Main file to control the plugin.
 *
 * The constant will be used in the additional PHP files to determine if the
 * code is being called from a legitimate interface or not. It is expected that
 * during the direct access of any of the extra PHP files the interpreter will
 * return a 403/Forbidden response and immediately exit the execution, this will
 * prevent unwanted access to code with unmet dependencies.
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

/* terminate execution if dependencies are not met */
foreach ($sucuriscan_dependencies as $dependency) {
    if (!function_exists($dependency)) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
        exit(0);
    }
}

/* check if installation path is available */
if (!defined('ABSPATH') || !defined('WP_CONTENT_DIR')) {
    /* Report invalid access if possible. */
    header('HTTP/1.1 403 Forbidden');
    exit(0);
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
define('SUCURISCAN_VERSION', '1.8.44');

/**
 * Defines the human readable name of the plugin.
 */
define('SUCURISCAN_PLUGIN_NAME', 'Sucuri Security - Auditing, Malware Scanner and Hardening');

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
 * The local URL where the plugin's files and assets are served.
 */
define('SUCURISCAN_URL', rtrim(plugin_dir_url(__FILE__), '/'));

/**
 * Latest version of the public Sucuri API.
 */
define('SUCURISCAN_API_VERSION', 'v1');

/**
 * Remote URL where the firewall API service is running.
 */
define('SUCURISCAN_CLOUDPROXY_API', 'https://waf.sucuri.net/api');

/**
 * Latest version of the firewall API.
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
define('SUCURISCAN_MAX_PAGINATION_BUTTONS', 16);

/**
 * Frequency of the file system scans in seconds.
 */
define('SUCURISCAN_SCANNER_FREQUENCY', 10800);

/**
 * The life time of the cache for the results of the SiteCheck scans.
 */
define('SUCURISCAN_SITECHECK_LIFETIME', 21600);

/**
 * The life time of the cache for the results of the get_plugins function.
 */
define('SUCURISCAN_GET_PLUGINS_LIFETIME', 1800);

/**
 * The maximum execution time of a HTTP request before timeout.
 */
define('SUCURISCAN_MAX_REQUEST_TIMEOUT', 5);

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

/* Load plugin translations */
function sucuriscan_load_plugin_textdomain()
{
    load_plugin_textdomain('sucuri-scanner', false, basename(dirname(__FILE__)) . '/lang/');
}
add_action('plugins_loaded', 'sucuriscan_load_plugin_textdomain');

/* Load all classes before anything else. */
require_once 'src/base.lib.php';
require_once 'src/request.lib.php';
require_once 'src/fileinfo.lib.php';
require_once 'src/cache.lib.php';
require_once 'src/option.lib.php';
require_once 'src/cron.lib.php';
require_once 'src/event.lib.php';
require_once 'src/hook.lib.php';
require_once 'src/api.lib.php';
require_once 'src/mail.lib.php';
require_once 'src/command.lib.php';
require_once 'src/template.lib.php';
require_once 'src/fsscanner.lib.php';
require_once 'src/hardening.lib.php';
require_once 'src/interface.lib.php';
require_once 'src/auditlogs.lib.php';
require_once 'src/sitecheck.lib.php';
require_once 'src/wordpress-recommendations.lib.php';
require_once 'src/integrity.lib.php';
require_once 'src/firewall.lib.php';
require_once 'src/installer-skin.lib.php';

/* Load page and ajax handlers */
require_once 'src/pagehandler.php';

/* Load handlers for main pages (lastlogins). */
require_once 'src/lastlogins.php';
require_once 'src/lastlogins-loggedin.php';
require_once 'src/lastlogins-failed.php';

/* Load handlers for main pages (settings). */
require_once 'src/settings.php';
require_once 'src/settings-general.php';
require_once 'src/settings-scanner.php';
require_once 'src/settings-integrity.php';
require_once 'src/settings-hardening.php';
require_once 'src/settings-posthack.php';
require_once 'src/settings-alerts.php';
require_once 'src/settings-apiservice.php';
require_once 'src/settings-webinfo.php';

/* Load global variables and triggers */
require_once 'src/globals.php';

/* Load WP-CLI command */
if (defined('WP_CLI') && WP_CLI) {
    include_once 'src/cli.lib.php';
}

/**
 * Deactivated the plugin
 *
 * Remove the scheduled task, but don't clear other things yet until the plugin is uninstalled.
 *
 * @return void
 */
function sucuriscanResetAndDeactivate()
{
    /* Delete scheduled task from the system */
    wp_clear_scheduled_hook('sucuriscan_scheduled_scan');
    SucuriScanEvent::reportDebugEvent('Sucuri plugin has been deactivated');
}

/**
 * Uninstalled the plugin
 *
 * When the user decides to uninstall the plugin it will call this method to
 * delete all traces of data inserted into the database by older versions of the
 * code, will delete the options inserted into the sub-database associated to a
 * multi-site installation, will revert the hardening applied to the core
 * directories, and will delete all the logs, cache and additional data stored
 * in the storage directory.
 *
 * @return void
 */
function sucuriscanUninstall()
{
    if (array_key_exists('wpdb', $GLOBALS)) {
        /* Delete all plugin related options from the database */
        $options = $GLOBALS['wpdb']->get_results(
            'SELECT option_id, option_name FROM ' . $GLOBALS['wpdb']->options
                . ' WHERE option_name LIKE "' . SUCURISCAN . '%"'
        );

        foreach ($options as $option) {
            delete_site_option($option->option_name);
            delete_option($option->option_name);
        }
    }

    /* Delete settings from the database if they exist */
    $options = SucuriScanOption::getDefaultOptionNames();
    foreach ($options as $option_name) {
        delete_site_option($option_name);
        delete_option($option_name);
    }

    /* Delete hardening in standard directories */
    SucuriScanHardening::removeFromAllowlist('ms-files.php', 'wp-includes');
    SucuriScanHardening::removeFromAllowlist('wp-tinymce.php', 'wp-includes');
    SucuriScanHardening::unhardenDirectory(WP_CONTENT_DIR);
    SucuriScanHardening::unhardenDirectory(WP_CONTENT_DIR . '/uploads');
    SucuriScanHardening::unhardenDirectory(ABSPATH . '/wp-includes');
    SucuriScanHardening::unhardenDirectory(ABSPATH . '/wp-admin');

    /* Delete cache files from disk */
    $fifo = new SucuriScanFileInfo();
    $fifo->ignore_files = false;
    $fifo->ignore_directories = false;
    $fifo->run_recursively = false;
    $directory = SucuriScan::dataStorePath();
    $fifo->removeDirectoryTree($directory);

    SucuriScanEvent::reportDebugEvent(__('Sucuri plugin has been uninstalled', 'sucuri-scanner'));
}

register_deactivation_hook(__FILE__, 'sucuriscanResetAndDeactivate');

register_uninstall_hook(__FILE__, 'sucuriscanUninstall');
