<?php
/*
Plugin Name: Sucuri Security - Auditing, Malware Scanner and Hardening
Plugin URI: https://wordpress.sucuri.net/
Description: The <a href="https://sucuri.net/" target="_blank">Sucuri</a> plugin provides the website owner the best Activity Auditing, SiteCheck Remote Malware Scanning, Effective Security Hardening and Post-Hack features. SiteCheck will check for malware, spam, blacklisting and other security issues like .htaccess redirects, hidden eval code, etc. The best thing about it is it's completely free.
Author: Sucuri, INC
Version: 1.8.2
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
        exit(0);
    }
}

require_once('src/constants.php');
require_once('src/globals.php');
require_once('src/sucuriscan.lib.php');
require_once('src/request.lib.php');
require_once('src/fileinfo.lib.php');
require_once('src/cache.lib.php');
require_once('src/option.lib.php');
require_once('src/event.lib.php');
require_once('src/hook.lib.php');
require_once('src/api.lib.php');
require_once('src/mail.lib.php');
require_once('src/template.lib.php');
require_once('src/fsscanner.lib.php');
require_once('src/heartbeat.lib.php');
require_once('src/interface.lib.php');
require_once('src/modfiles.php');
require_once('src/sitecheck.php');
require_once('src/firewall.php');
require_once('src/hardening.lib.php');
require_once('src/hardening.php');
require_once('src/homepage.php');
require_once('src/auditlogs.php');
require_once('src/outdated.php');
require_once('src/corefiles.php');
require_once('src/posthack.php');
require_once('src/lastlogins.php');
require_once('src/lastlogins-loggedin.php');
require_once('src/lastlogins-failed.php');
require_once('src/lastlogins-blocked.php');
require_once('src/settings-handler.php');
require_once('src/settings-general.php');
require_once('src/settings-scanner.php');
require_once('src/settings-corefiles.php');
require_once('src/settings-sitecheck.php');
require_once('src/settings-ignorescan.php');
require_once('src/settings-alerts.php');
require_once('src/settings-ignorealerts.php');
require_once('src/settings-apiservice.php');
require_once('src/settings-selfhosting.php');
require_once('src/settings-trustip.php');
require_once('src/settings-heartbeat.php');
require_once('src/settings.php');
require_once('src/infosys.php');
