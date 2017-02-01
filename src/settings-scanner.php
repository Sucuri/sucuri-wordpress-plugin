<?php

/**
 * Read and parse the content of the scanner settings template.
 *
 * @return string Parsed HTML code for the scanner settings panel.
 */
function sucuriscan_settings_scanner($nonce)
{
    global $sucuriscan_schedule_allowed,
        $sucuriscan_interface_allowed;

    // Get initial variables to decide some things bellow.
    $fs_scanner = SucuriScanOption::get_option(':fs_scanner');
    $scan_freq = SucuriScanOption::get_option(':scan_frequency');
    $scan_interface = SucuriScanOption::get_option(':scan_interface');
    $scan_errorlogs = SucuriScanOption::get_option(':scan_errorlogs');
    $runtime_scan_human = SucuriScanFSScanner::get_filesystem_runtime(true);

    // Get the file path of the security logs.
    $basedir = SucuriScan::datastore_folder_path();
    $integrity_log_path = $basedir . '/sucuri-integrity.php';
    $lastlogins_log_path = $basedir . '/sucuri-lastlogins.php';
    $failedlogins_log_path = $basedir . '/sucuri-failedlogins.php';

    // Generate the HTML code for the option list in the form select fields.
    $scan_freq_options = SucuriScanTemplate::selectOptions($sucuriscan_schedule_allowed, $scan_freq);
    $scan_interface_options = SucuriScanTemplate::selectOptions($sucuriscan_interface_allowed, $scan_interface);

    $params = array(
        /* Filesystem scanner */
        'FsScannerStatus' => 'Enabled',
        'FsScannerSwitchText' => 'Disable',
        'FsScannerSwitchValue' => 'disable',
        'FsScannerSwitchCssClass' => 'button-danger',
        /* Scan error logs. */
        'ScanErrorlogsStatus' => 'Enabled',
        'ScanErrorlogsSwitchText' => 'Disable',
        'ScanErrorlogsSwitchValue' => 'disable',
        'ScanErrorlogsSwitchCssClass' => 'button-danger',
        /* Filsystem scanning frequency. */
        'ScanningFrequency' => 'Undefined',
        'ScanningFrequencyOptions' => $scan_freq_options,
        'ScanningInterface' => ( $scan_interface ? $sucuriscan_interface_allowed[ $scan_interface ] : 'Undefined' ),
        'ScanningInterfaceOptions' => $scan_interface_options,
        /* Filesystem scanning runtime. */
        'ScanningRuntimeHuman' => $runtime_scan_human,
        'IntegrityLogLife' => '0B',
        'LastLoginLogLife' => '0B',
        'FailedLoginLogLife' => '0B',
    );

    if ($fs_scanner == 'disabled') {
        $params['FsScannerStatus'] = 'Disabled';
        $params['FsScannerSwitchText'] = 'Enable';
        $params['FsScannerSwitchValue'] = 'enable';
        $params['FsScannerSwitchCssClass'] = 'button-success';
    }

    if ($scan_errorlogs == 'disabled') {
        $params['ScanErrorlogsStatus'] = 'Disabled';
        $params['ScanErrorlogsSwitchText'] = 'Enable';
        $params['ScanErrorlogsSwitchValue'] = 'enable';
        $params['ScanErrorlogsSwitchCssClass'] = 'button-success';
    }

    if (array_key_exists($scan_freq, $sucuriscan_schedule_allowed)) {
        $params['ScanningFrequency'] = $sucuriscan_schedule_allowed[ $scan_freq ];
    }

    // Determine the age of the security log files.
    $params['IntegrityLogLife'] = SucuriScan::human_filesize(@filesize($integrity_log_path));
    $params['LastLoginLogLife'] = SucuriScan::human_filesize(@filesize($lastlogins_log_path));
    $params['FailedLoginLogLife'] = SucuriScan::human_filesize(@filesize($failedlogins_log_path));

    $params['Settings.CoreFilesStatus'] = sucuriscan_settings_corefiles_status($nonce);
    $params['Settings.CoreFilesLanguage'] = sucuriscan_settings_corefiles_language($nonce);
    $params['Settings.CoreFilesCache'] = sucuriscan_settings_corefiles_cache($nonce);
    $params['Settings.SiteCheckStatus'] = SucuriScanSiteCheck::statusPage();
    $params['Settings.SiteCheckCache'] = SucuriScanSiteCheck::cachePage($nonce);
    $params['Settings.SiteCheckTimeout'] = SucuriScanSiteCheck::timeoutPage($nonce);

    return SucuriScanTemplate::getSection('settings-scanner', $params);
}
