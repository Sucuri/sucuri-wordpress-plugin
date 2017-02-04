<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

class SucuriScanSettings extends SucuriScanOption
{
}

/**
 * Print a HTML code with the settings of the plugin.
 *
 * @return void
 */
function sucuriscan_settings_page()
{
    SucuriScanInterface::check_permissions();

    $params = array();
    $nonce = SucuriScanInterface::check_nonce();

    $params['PageTitle'] = 'Settings';
    $params['Settings.General'] = sucuriscan_settings_general($nonce);
    $params['Settings.Scanner'] = sucuriscan_settings_scanner($nonce);
    $params['Settings.Alerts'] = sucuriscan_settings_alert($nonce);
    $params['Settings.ApiService'] = sucuriscan_settings_apiservice($nonce);
    $params['Settings.SelfHosting'] = sucuriscan_settings_selfhosting($nonce);
    $params['Settings.IgnoreScanning'] = sucuriscan_settings_ignorescan($nonce);
    $params['Settings.IgnoreRules'] = sucuriscan_settings_ignore_rules();
    $params['Settings.TrustIP'] = sucuriscan_settings_trust_ip();
    $params['Settings.Heartbeat'] = sucuriscan_settings_heartbeat();

    echo SucuriScanTemplate::getTemplate('settings', $params);
}

/**
 * Handle an Ajax request for this specific page.
 *
 * @return mixed.
 */
function sucuriscan_settings_ajax()
{
    SucuriScanInterface::check_permissions();

    if (SucuriScanInterface::check_nonce()) {
        sucuriscan_settings_ignorescan_ajax();
        sucuriscan_settings_apiservice_https_ajax();
    }

    wp_die();
}
