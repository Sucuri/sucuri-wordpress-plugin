<?php

/**
 * Code related to the settings-apiservice.php interface.
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

/**
 * Returns the HTML to configure the API service status.
 *
 * @param bool $nonce True if the CSRF protection worked, false otherwise.
 * @return string HTML for the API service status option.
 */
function sucuriscan_settings_apiservice_status($nonce)
{
    $params = array();

    $params['ApiStatus.StatusNum'] = '1';
    $params['ApiStatus.Status'] = 'Enabled';
    $params['ApiStatus.SwitchText'] = 'Disable';
    $params['ApiStatus.SwitchValue'] = 'disable';
    $params['ApiStatus.WarningVisibility'] = 'visible';
    $params['ApiStatus.ErrorVisibility'] = 'hidden';
    $params['ApiStatus.ServiceURL'] = SUCURISCAN_API_URL;
    $params['ApiStatus.ApiKey'] = '';

    if ($nonce) {
        // Enable or disable the API service communication.
        $api_service = SucuriScanRequest::post(':api_service', '(en|dis)able');

        if ($api_service) {
            $action_d = $api_service . 'd';
            $message = 'API service communication was <code>' . $action_d . '</code>';

            SucuriScanEvent::reportInfoEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanOption::updateOption(':api_service', $action_d);
            SucuriScanInterface::info('The status of the API service has been changed');
        }
    }

    $api_service = SucuriScanOption::getOption(':api_service');

    if ($api_service === 'disabled') {
        $params['ApiStatus.StatusNum'] = '0';
        $params['ApiStatus.Status'] = 'Disabled';
        $params['ApiStatus.SwitchText'] = 'Enable';
        $params['ApiStatus.SwitchValue'] = 'enable';
        $params['ApiStatus.WarningVisibility'] = 'hidden';
        $params['ApiStatus.ErrorVisibility'] = 'visible';
    }

    $api_key = SucuriScanAPI::getPluginKey();
    $params['ApiStatus.ApiKey'] = $api_key ? $api_key : 'NONE';

    return SucuriScanTemplate::getSection('settings-apiservice-status', $params);
}

/**
 * Returns the HTML to configure the API service proxy.
 *
 * @return string HTML for the API service proxy option.
 */
function sucuriscan_settings_apiservice_proxy()
{
    $params = array(
        'APIProxy.Host' => 'no_proxy_host',
        'APIProxy.Port' => 'no_proxy_port',
        'APIProxy.Username' => 'no_proxy_username',
        'APIProxy.Password' => 'no_proxy_password',
        'APIProxy.PasswordType' => 'default',
        'APIProxy.PasswordText' => 'empty',
    );

    if (class_exists('WP_HTTP_Proxy')) {
        $wp_http_proxy = new WP_HTTP_Proxy();

        if ($wp_http_proxy->is_enabled()) {
            $proxy_host = SucuriScan::escape($wp_http_proxy->host());
            $proxy_port = SucuriScan::escape($wp_http_proxy->port());
            $proxy_username = SucuriScan::escape($wp_http_proxy->username());
            $proxy_password = SucuriScan::escape($wp_http_proxy->password());

            $params['APIProxy.Host'] = $proxy_host;
            $params['APIProxy.Port'] = $proxy_port;
            $params['APIProxy.Username'] = $proxy_username;
            $params['APIProxy.Password'] = $proxy_password;
            $params['APIProxy.PasswordType'] = 'info';
            $params['APIProxy.PasswordText'] = 'hidden';
        }
    }

    return SucuriScanTemplate::getSection('settings-apiservice-proxy', $params);
}

/**
 * Returns the HTML to configure the URL for the checkums API.
 *
 * @param bool $nonce True if the CSRF protection worked, false otherwise.
 * @return string HTML for the URL for the checksums API service.
 */
function sucuriscan_settings_apiservice_checksums($nonce)
{
    $params = array();
    $url = SucuriScanRequest::post(':checksum_api');

    if ($nonce && $url !== false) {
        /* https://github.com/WordPress/WordPress - OR - WordPress/WordPress */
        $pattern = '/^(https:\/\/github\.com\/)?([0-9a-zA-Z_]+\/[0-9a-zA-Z_]+)/';

        if (@preg_match($pattern, $url, $match)) {
            SucuriScanOption::updateOption(':checksum_api', $match[2]);

            $message = 'Core integrity API changed: ' . SucuriScanAPI::checksumAPI();
            SucuriScanEvent::reportInfoEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanInterface::info('The URL to retrieve the WordPress checksums has been changed');
        } else {
            SucuriScanOption::deleteOption(':checksum_api');

            $message = 'Core integrity API changed: ' . SucuriScanAPI::checksumAPI();
            SucuriScanEvent::reportInfoEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanInterface::info('The URL to retrieve the WordPress checksums has been changed');
        }
    }

    $params['ChecksumsAPI'] = SucuriScanAPI::checksumAPI();

    return SucuriScanTemplate::getSection('settings-apiservice-checksums', $params);
}
