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
    SucuriScanInterface::checkPageVisibility();

    $params = array();
    $nonce = SucuriScanInterface::checkNonce();

    $params['PageTitle'] = 'Settings';
    $params['Settings.General'] = sucuriscan_settings_general($nonce);
    $params['Settings.Scanner'] = sucuriscan_settings_scanner($nonce);
    $params['Settings.Alerts'] = sucuriscan_settings_alert($nonce);
    $params['Settings.ApiService'] = sucuriscan_settings_apiservice($nonce);
    $params['Settings.SelfHosting'] = sucuriscan_settings_selfhosting($nonce);
    $params['Settings.IgnoreScanning'] = sucuriscan_settings_ignorescan($nonce);
    $params['Settings.IgnoreRules'] = sucuriscan_settings_ignore_rules();
    $params['Settings.TrustIP'] = sucuriscan_settings_trust_ip();

    echo SucuriScanTemplate::getTemplate('settings', $params);
}

/**
 * Handle an Ajax request for this specific page.
 *
 * @return mixed.
 */
function sucuriscan_settings_ajax()
{
    SucuriScanInterface::checkPageVisibility();

    if (SucuriScanInterface::checkNonce()) {
        sucuriscan_settings_ignorescan_ajax();
        sucuriscan_settings_apiservice_https_ajax();
    }

    wp_die();
}
