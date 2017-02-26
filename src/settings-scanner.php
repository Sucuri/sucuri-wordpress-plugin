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
    global $sucuriscan_schedule_allowed,
        $sucuriscan_interface_allowed;

    // Get initial variables to decide some things bellow.
    $fs_scanner = SucuriScanOption::getOption(':fs_scanner');
    $scan_freq = SucuriScanOption::getOption(':scan_frequency');
    $scan_interface = SucuriScanOption::getOption(':scan_interface');
    $runtime_scan_human = SucuriScanFSScanner::getFilesystemRuntime(true);

    // Get the file path of the security logs.
    $basedir = SucuriScan::dataStorePath();
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

    if (array_key_exists($scan_freq, $sucuriscan_schedule_allowed)) {
        $params['ScanningFrequency'] = $sucuriscan_schedule_allowed[ $scan_freq ];
    }

    // Determine the age of the security log files.
    $params['IntegrityLogLife'] = SucuriScan::humanFileSize(@filesize($integrity_log_path));
    $params['LastLoginLogLife'] = SucuriScan::humanFileSize(@filesize($lastlogins_log_path));
    $params['FailedLoginLogLife'] = SucuriScan::humanFileSize(@filesize($failedlogins_log_path));

    $params['Settings.CoreFilesStatus'] = sucuriscan_settings_corefiles_status($nonce);
    $params['Settings.CoreFilesLanguage'] = sucuriscan_settings_corefiles_language($nonce);
    $params['Settings.CoreFilesCache'] = sucuriscan_settings_corefiles_cache($nonce);
    $params['Settings.SiteCheckStatus'] = SucuriScanSiteCheck::statusPage();
    $params['Settings.SiteCheckCache'] = SucuriScanSiteCheck::cachePage($nonce);
    $params['Settings.SiteCheckTimeout'] = SucuriScanSiteCheck::timeoutPage($nonce);

    return SucuriScanTemplate::getSection('settings-scanner', $params);
}
