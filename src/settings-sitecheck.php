<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

if (!class_exists('SucuriScanSiteCheck')) {
class SucuriScanSiteCheck extends SucuriScanSettings
{
    public static function isEnabled()
    {
        return (bool) !self::isDisabled();
    }

    public static function isDisabled()
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

        if (SucuriScanSiteCheck::isDisabled()) {
            $params['SiteCheck.StatusNum'] = '0';
            $params['SiteCheck.Status'] = 'Disabled';
            $params['SiteCheck.IfEnabled'] = 'hidden';
            $params['SiteCheck.IfDisabled'] = 'visible';
        }

        $params['SiteCheck.Counter'] = SucuriScanOption::get_option(':sitecheck_counter');

        return SucuriScanTemplate::getSection('settings-sitecheck-status', $params);
    }

    public static function cachePage($nonce)
    {
        $params = array();
        $fpath = SucuriScan::datastore_folder_path('sucuri-sitecheck.php');

        if ($nonce) {
            // Reset SiteCheck results cache.
            if (SucuriScanRequest::post(':sitecheck_cache')) {
                if (file_exists($fpath)) {
                    if (@unlink($fpath)) {
                        $message = 'Malware scanner cache was successfully reset.';

                        SucuriScanEvent::report_debug_event($message);
                        SucuriScanInterface::info($message);
                    } else {
                        SucuriScanInterface::error('Count not reset the cache, delete manually.');
                    }
                } else {
                    SucuriScanInterface::error('The cache file does not exists.');
                }
            }
        }

        $params['SiteCheck.CacheSize'] = SucuriScan::human_filesize(@filesize($fpath));
        $params['SiteCheck.CacheLifeTime'] = SUCURISCAN_SITECHECK_LIFETIME;

        return SucuriScanTemplate::getSection('settings-sitecheck-cache', $params);
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

                    SucuriScanOption::update_option(':sitecheck_timeout', $timeout);
                    SucuriScanEvent::report_info_event($message);
                    SucuriScanEvent::notify_event('plugin_change', $message);
                    SucuriScanInterface::info($message);
                } else {
                    SucuriScanInterface::error('SiteCheck timeout in seconds is too high.');
                }
            }
        }

        $params['MaxRequestTimeout'] = SUCURISCAN_MAX_SITECHECK_TIMEOUT;
        $params['RequestTimeout'] = SucuriScanOption::get_option(':sitecheck_timeout') . ' seconds';

        return SucuriScanTemplate::getSection('settings-sitecheck-timeout', $params);
    }
}
}
