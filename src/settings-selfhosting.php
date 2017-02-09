<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Read and parse the content of the self-hosting settings template.
 *
 * @return string Parsed HTML code for the self-hosting settings panel.
 */
function sucuriscan_settings_selfhosting($nonce)
{
    $params = array();

    $params['SelfHosting.Monitor'] = sucuriscan_settings_selfhosting_monitor($nonce);

    return SucuriScanTemplate::getSection('settings-selfhosting', $params);
}

function sucuriscan_selfhosting_fpath()
{
    $monitor = SucuriScanOption::getOption(':selfhosting_monitor');
    $monitor_fpath = SucuriScanOption::getOption(':selfhosting_fpath');

    if ($monitor === 'enabled'
        && !empty($monitor_fpath)
        && file_exists($monitor_fpath)
        && is_writable($monitor_fpath)
    ) {
        return $monitor_fpath;
    }

    return false;
}

function sucuriscan_settings_selfhosting_monitor($nonce)
{
    $params = array();

    $params['SelfHostingMonitor.DisabledVisibility'] = 'visible';
    $params['SelfHostingMonitor.Status'] = 'Enabled';
    $params['SelfHostingMonitor.SwitchText'] = 'Disable';
    $params['SelfHostingMonitor.SwitchValue'] = 'disable';
    $params['SelfHostingMonitor.SwitchCssClass'] = 'button-danger';
    $params['SelfHostingMonitor.FpathVisibility'] = 'hidden';
    $params['SelfHostingMonitor.Fpath'] = '';

    if ($nonce) {
        // Set a file path for the self-hosted event monitor.
        $monitor_fpath = SucuriScanRequest::post(':selfhosting_fpath');

        if ($monitor_fpath !== false) {
            if (empty($monitor_fpath)) {
                $message = 'Log exporter was disabled.';

                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanOption::deleteOption(':selfhosting_fpath');
                SucuriScanOption::updateOption(':selfhosting_monitor', 'disabled');
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            } elseif (strpos($monitor_fpath, $_SERVER['DOCUMENT_ROOT']) !== false) {
                SucuriScanInterface::error('File should not be publicly accessible.');
            } elseif (file_exists($monitor_fpath)) {
                SucuriScanInterface::error('File already exists and will not be overwritten.');
            } elseif (!is_writable(dirname($monitor_fpath))) {
                SucuriScanInterface::error('File parent directory is not writable.');
            } else {
                @file_put_contents($monitor_fpath, '', LOCK_EX);
                $message = 'Log exporter file path was set correctly.';

                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanOption::updateOption(':selfhosting_monitor', 'enabled');
                SucuriScanOption::updateOption(':selfhosting_fpath', $monitor_fpath);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }
    }

    $monitor = SucuriScanOption::getOption(':selfhosting_monitor');
    $monitor_fpath = SucuriScanOption::getOption(':selfhosting_fpath');

    if ($monitor === 'disabled') {
        $params['SelfHostingMonitor.Status'] = 'Disabled';
        $params['SelfHostingMonitor.SwitchText'] = 'Enable';
        $params['SelfHostingMonitor.SwitchValue'] = 'enable';
        $params['SelfHostingMonitor.SwitchCssClass'] = 'button-success';
    }

    if ($monitor === 'enabled' && $monitor_fpath) {
        $params['SelfHostingMonitor.DisabledVisibility'] = 'hidden';
        $params['SelfHostingMonitor.FpathVisibility'] = 'visible';
        $params['SelfHostingMonitor.Fpath'] = SucuriScan::escape($monitor_fpath);
    }

    return SucuriScanTemplate::getSection('settings-selfhosting-monitor', $params);
}
