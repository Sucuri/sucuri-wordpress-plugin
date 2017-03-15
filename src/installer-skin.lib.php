<?php

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

        class SucuriScanPluginInstallerSkin extends Plugin_Installer_Skin
        {
            public function feedback($string = '')
            {
                /* do not do anything */
            }
        }
    }
}
