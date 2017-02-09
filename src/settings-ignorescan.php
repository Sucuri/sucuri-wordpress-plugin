<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Read and parse the content of the SiteCheck settings template.
 *
 * @return string Parsed HTML code for the SiteCheck settings panel.
 */
function sucuriscan_settings_ignorescan($nonce)
{
    $params = array();

    $params['SettingsSection.IgnoreScanStatus'] = sucuriscan_settings_ignore_scan_status($nonce);
    $params['SettingsSection.IgnoreScanFiles'] = sucuriscan_settings_ignore_scan_files();
    $params['SettingsSection.IgnoreScanFolders'] = sucuriscan_settings_ignore_scan_folders($nonce);

    return SucuriScanTemplate::getSection('settings-ignorescan', $params);
}

function sucuriscan_settings_ignorescan_ajax()
{
    if (SucuriScanRequest::post('form_action') == 'get_ignored_files') {
        $response = '';

        // Scan the project and get the ignored paths.
        if (SucuriScanOption::isEnabled(':ignore_scanning')) {
            $counter = 0;
            $ignored_dirs = SucuriScanFSScanner::getIgnoredDirectoriesLive();

            foreach ($ignored_dirs as $group => $dir_list) {
                foreach ($dir_list as $dir_data) {
                    $valid_entry = false;
                    $snippet = array(
                        'IgnoreScan.CssClass' => '',
                        'IgnoreScan.Directory' => '',
                        'IgnoreScan.DirectoryPath' => '',
                        'IgnoreScan.IgnoredAt' => '',
                        'IgnoreScan.IgnoredAtText' => 'ok',
                        'IgnoreScan.IgnoredCssClass' => 'success',
                    );

                    if ($group == 'is_ignored') {
                        $valid_entry = true;
                        $snippet['IgnoreScan.Directory'] = urlencode($dir_data['directory_path']);
                        $snippet['IgnoreScan.DirectoryPath'] = $dir_data['directory_path'];
                        $snippet['IgnoreScan.IgnoredAt'] = SucuriScan::datetime($dir_data['ignored_at']);
                        $snippet['IgnoreScan.IgnoredAtText'] = 'ignored';
                        $snippet['IgnoreScan.IgnoredCssClass'] = 'warning';
                    } elseif ($group == 'is_not_ignored') {
                        $valid_entry = true;
                        $snippet['IgnoreScan.Directory'] = urlencode($dir_data);
                        $snippet['IgnoreScan.DirectoryPath'] = $dir_data;
                    }

                    if ($valid_entry) {
                        $snippet['IgnoreScan.CssClass'] = ($counter % 2 === 0) ? '' : 'alternate';
                        $response .= SucuriScanTemplate::getSnippet('settings-ignorescan', $snippet);
                        $counter++;
                    }
                }
            }
        } else {
            $response = '<tr><td colspan="3">Enable the ignore scanning option first.</td></tr>';
        }

        print($response);
        exit(0);
    }
}

function sucuriscan_settings_ignore_scan_status($nonce)
{
    $params = array();
    $params['IgnoreScan.Status'] = 'Disabled';
    $params['IgnoreScan.SwitchText'] = 'Enable';
    $params['IgnoreScan.SwitchValue'] = 'enable';
    $params['IgnoreScan.SwitchCssClass'] = 'button-success';

    if ($nonce) {
        // Enable or disable the filesystem scanner for error logs.
        if ($ignore = SucuriScanRequest::post(':ignore_scanning', '(en|dis)able')) {
            $action_d = $ignore . 'd';
            $message = 'File system scanner rules to ignore directories was <code>' . $action_d . '</code>';

            SucuriScanOption::updateOption(':ignore_scanning', $action_d);
            SucuriScanEvent::reportAutoEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanInterface::info($message);
        }
    }

    if (SucuriScanOption::isEnabled(':ignore_scanning')) {
        $params['IgnoreScan.Status'] = 'Enabled';
        $params['IgnoreScan.SwitchText'] = 'Disable';
        $params['IgnoreScan.SwitchValue'] = 'disable';
        $params['IgnoreScan.SwitchCssClass'] = 'button-danger';
    }

    return SucuriScanTemplate::getSection('settings-ignorescan-status', $params);
}

function sucuriscan_settings_ignore_scan_files()
{
    $params = array();

    return SucuriScanTemplate::getSection('settings-ignorescan-files', $params);
}

function sucuriscan_settings_ignore_scan_folders($nonce)
{
    $params = array();

    if ($nonce) {
        // Ignore a new directory path for the file system scans.
        if ($action = SucuriScanRequest::post(':ignorescanning_action', '(ignore|unignore)')) {
            $ign_dirs = SucuriScanRequest::post(':ignorescanning_dirs', '_array');
            $ign_file = SucuriScanRequest::post(':ignorescanning_file');

            if ($action == 'ignore') {
                // Target a single file path to be ignored.
                if ($ign_file !== false) {
                    $ign_dirs = array($ign_file);
                    unset($_POST['sucuriscan_ignorescanning_file']);
                }

                // Target a list of directories to be ignored.
                if (is_array($ign_dirs) && !empty($ign_dirs)) {
                    $were_ignored = array();

                    foreach ($ign_dirs as $resource_path) {
                        if (file_exists($resource_path)
                            && SucuriScanFSScanner::ignoreDirectory($resource_path)
                        ) {
                            $were_ignored[] = $resource_path;
                        }
                    }

                    if (!empty($were_ignored)) {
                        SucuriScanInterface::info('Items selected will be ignored in future scans.');
                        SucuriScanEvent::reportWarningEvent(sprintf(
                            'Resources will not be scanned: (multiple entries): %s',
                            @implode(',', $ign_dirs)
                        ));
                    }
                }
            } elseif ($action == 'unignore'
                && is_array($ign_dirs)
                && !empty($ign_dirs)
            ) {
                foreach ($ign_dirs as $directory_path) {
                    SucuriScanFSScanner::unignoreDirectory($directory_path);
                }

                SucuriScanInterface::info('Items selected will not be ignored anymore.');
                SucuriScanEvent::reportNoticeEvent(sprintf(
                    'Resources will be scanned: (multiple entries): %s',
                    @implode(',', $ign_dirs)
                ));
            }
        }
    }

    return SucuriScanTemplate::getSection('settings-ignorescan-folders', $params);
}
