<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Read and parse the content of the scanner settings template.
 *
 * @return string Parsed HTML code for the scanner settings panel.
 */
function sucuriscan_settings_scanner($nonce)
{
    $params = array();

    $params['Settings.Options'] = sucuriscan_settings_scanner_options();
    $params['Settings.CoreFilesLanguage'] = sucuriscan_settings_corefiles_language($nonce);
    $params['Settings.CoreFilesCache'] = sucuriscan_settings_corefiles_cache($nonce);
    $params['Settings.SiteCheckStatus'] = SucuriScanSiteCheck::statusPage();
    $params['Settings.SiteCheckTimeout'] = SucuriScanSiteCheck::timeoutPage($nonce);

    return SucuriScanTemplate::getSection('settings-scanner', $params);
}

function sucuriscan_settings_scanner_options()
{
    global $sucuriscan_schedule_allowed,
        $sucuriscan_interface_allowed;

    $params = array();

    if (SucuriScanInterface::checkNonce()) {
        // Modify the schedule of the filesystem scanner.
        if ($frequency = SucuriScanRequest::post(':scan_frequency')) {
            if (array_key_exists($frequency, $sucuriscan_schedule_allowed)) {
                SucuriScanOption::updateOption(':scan_frequency', $frequency);

                // Remove all the scheduled tasks associated to the plugin.
                wp_clear_scheduled_hook('sucuriscan_scheduled_scan');

                // Install new cronjob unless the user has selected "Never".
                if ($frequency !== '_oneoff') {
                    wp_schedule_event(time() + 10, $frequency, 'sucuriscan_scheduled_scan');
                }

                $frequency_title = strtolower($sucuriscan_schedule_allowed[ $frequency ]);
                $message = 'File system scanning frequency set to <code>' . $frequency_title . '</code>';

                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }

        // Set the method (aka. interface) that will be used to scan the site.
        if ($interface = SucuriScanRequest::post(':scan_interface')) {
            $allowed_values = array_keys($sucuriscan_interface_allowed);

            if (in_array($interface, $allowed_values)) {
                $message = 'File system scanning interface set to <code>' . $interface . '</code>';

                SucuriScanOption::updateOption(':scan_interface', $interface);
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }
    }

    $scan_freq = SucuriScanOption::getOption(':scan_frequency');
    $scan_algo = SucuriScanOption::getOption(':scan_interface');

    // Generate the HTML code for the option list in the form select fields.
    $freq_options = SucuriScanTemplate::selectOptions($sucuriscan_schedule_allowed, $scan_freq);
    $algo_options = SucuriScanTemplate::selectOptions($sucuriscan_interface_allowed, $scan_algo);

    $params['ScanningFrequency'] = 'Undefined';
    $params['ScanningInterface'] = 'Undefined';
    $params['ScanningFrequencyOptions'] = $freq_options;
    $params['ScanningInterfaceOptions'] = $algo_options;

    if ($scan_algo && array_key_exists($scan_algo, $sucuriscan_interface_allowed)) {
        $params['ScanningInterface'] = $sucuriscan_interface_allowed[$scan_algo];
    }

    if (array_key_exists($scan_freq, $sucuriscan_schedule_allowed)) {
        $params['ScanningFrequency'] = $sucuriscan_schedule_allowed[ $scan_freq ];
    }

    return SucuriScanTemplate::getSection('settings-scanner-options', $params);
}
