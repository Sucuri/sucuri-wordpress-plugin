<?php

/**
 * Code related to the installer-skin.lib.php interface.
 *
 * @package Sucuri Security
 * @subpackage installer-skin.lib.php
 * @copyright Since 2010 Sucuri Inc.
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
        include_once(ABSPATH . '/wp-admin/includes/class-wp-upgrader.php');
        include_once(ABSPATH . '/wp-admin/includes/plugin-install.php');

        /**
         * Plugin Installer Skin for WordPress Plugin Installer.
         *
         * This is used by the post-hack utility to disregard the installation
         * process when the website owner decides to reset one or more plugins.
         * Without this WordPress will flush the buffer of the re-installation
         * process immediately and we will not be able to disregard these logs
         * after the operation has finished.
         *
         * @see WP_Upgrader_Skin
         *
         * @codeCoverageIgnore
         */
        class SucuriScanPluginInstallerSkin extends Plugin_Installer_Skin
        {
            /**
             * Reports the progress of the plugin installation.
             *
             * @param string $string Message to send to the buffer.
             */
            public function feedback($string = '')
            {
                /* do not do anything */
            }
        }
    }
}
