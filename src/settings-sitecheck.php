<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

class SucuriScanSiteCheck extends SucuriScanSettings
{
    public static function hasBeenDisabled()
    {
        return (bool) (
            defined('SUCURISCAN_NO_SITECHECK')
            && SUCURISCAN_NO_SITECHECK === true
        );
    }

    public static function statusPage()
    {
        $params = array();
        $params['SiteCheck.StatusNum'] = '1';
        $params['SiteCheck.Status'] = 'Enabled';
        $params['SiteCheck.IfEnabled'] = 'visible';
        $params['SiteCheck.IfDisabled'] = 'hidden';

        if (self::hasBeenDisabled()) {
            $params['SiteCheck.StatusNum'] = '0';
            $params['SiteCheck.Status'] = 'Disabled';
            $params['SiteCheck.IfEnabled'] = 'hidden';
            $params['SiteCheck.IfDisabled'] = 'visible';
        }

        $params['SiteCheck.Counter'] = SucuriScanOption::getOption(':sitecheck_counter');

        return SucuriScanTemplate::getSection('settings-sitecheck-status', $params);
    }

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

        return SucuriScanTemplate::getSection('settings-sitecheck-timeout', $params);
    }
}
