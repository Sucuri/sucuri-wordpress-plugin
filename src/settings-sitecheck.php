<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

class SucuriScanSettingsSiteCheck extends SucuriScanSettings
{
    public static function timeoutPage($nonce)
    {
        $params = array();

        // Update the SiteCheck timeout.
        if ($nonce) {
            $timeout = (int) SucuriScanRequest::post(':sitecheck_timeout', '[0-9]+');

            if ($timeout > 0) {
                if ($timeout <= SUCURISCAN_MAX_SITECHECK_TIMEOUT) {
                    $message = 'SiteCheck timeout set to <code>' . $timeout . '</code> seconds.';

                    SucuriScanOption::updateOption(':sitecheck_timeout', $timeout);
                    SucuriScanEvent::reportInfoEvent($message);
                    SucuriScanEvent::notifyEvent('plugin_change', $message);
                    SucuriScanInterface::info($message);
                } else {
                    SucuriScanInterface::error('SiteCheck timeout in seconds is too high.');
                }
            }
        }

        $params['MaxRequestTimeout'] = SUCURISCAN_MAX_SITECHECK_TIMEOUT;
        $params['RequestTimeout'] = SucuriScanOption::getOption(':sitecheck_timeout') . ' seconds';

        return SucuriScanTemplate::getSection('settings-scanner-sitecheck-timeout', $params);
    }
}
