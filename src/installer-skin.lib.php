<?php

/**
 * Code related to the installer-skin.lib.php interface.
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

if (class_exists('SucuriScanInterface') && class_exists('SucuriScanRequest')) {
    if (SucuriScanRequest::post('form_action') == 'reset_plugin') {
        include_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . '/wp-admin/includes/plugin-install.php';

        if (version_compare(SucuriScan::siteVersion(), '5.4', '<')) {
            include_once 'installer-skin-legacy.lib.php';
            return;
        }

        /**
         * Plugin Installer Skin for WordPress Plugin Installer.
         *
         * This is used by the post-hack utility to disregard the installation
         * process when the website owner decides to reset one or more plugins.
         * Without this WordPress will flush the buffer of the re-installation
         * process immediately and we will not be able to disregard these logs
         * after the operation has finished.
         *
         * @codeCoverageIgnore
         *
         * @category   Library
         * @package    Sucuri
         * @subpackage SucuriScanner
         * @author     Daniel Cid <dcid@sucuri.net>
         * @copyright  2010-2018 Sucuri Inc.
         * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
         * @link       https://wordpress.org/plugins/sucuri-scanner
         */
        class SucuriScanPluginInstallerSkin extends Plugin_Installer_Skin
        {
            /**
             * Reports the progress of the plugin installation.
             *
             * @param string $string  Message to display in administration message.
             * @param mixed  ...$args Optional text replacements.
             *
             * @return void
             */
            public function feedback($string = '', ...$args)
            {
                /* do not do anything */
            }
        }
    }
}
