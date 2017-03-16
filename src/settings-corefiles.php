<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

function sucuriscan_settings_corefiles_status($nonce)
{
    $params = array();
    $params['Integrity.StatusNum'] = '0';
    $params['Integrity.Status'] = 'Disabled';
    $params['Integrity.SwitchText'] = 'Enable';
    $params['Integrity.SwitchValue'] = 'enable';
    $params['Integrity.SwitchCssClass'] = 'button-success';

    if ($nonce) {
        // Enable or disable the filesystem scanner for file integrity.
        if ($scan_checksums = SucuriScanRequest::post(':scan_checksums', '(en|dis)able')) {
            $action_d = $scan_checksums . 'd';
            $message = 'File system scanner for file integrity was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':scan_checksums', $action_d);
            SucuriScanEvent::report_auto_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }
    }

    if (SucuriScanOption::is_enabled(':scan_checksums')) {
        $params['Integrity.StatusNum'] = '1';
        $params['Integrity.Status'] = 'Enabled';
        $params['Integrity.SwitchText'] = 'Disable';
        $params['Integrity.SwitchValue'] = 'disable';
        $params['Integrity.SwitchCssClass'] = 'button-danger';
    }

    return SucuriScanTemplate::getSection('settings-corefiles-status', $params);
}

function sucuriscan_settings_corefiles_language($nonce)
{
    $params = array();
    $languages = SucuriScan::languages();

    if ($nonce) {
        // Configure the language for the core integrity checks.
        if ($language = SucuriScanRequest::post(':set_language')) {
            if (array_key_exists($language, $languages)) {
                $message = 'Language for the core integrity checks set to <code>' . $language . '</code>';

                SucuriScanOption::update_option(':language', $language);
                SucuriScanEvent::report_auto_event($message);
                SucuriScanEvent::notify_event('plugin_change', $message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Selected language is not supported.');
            }
        }
    }

    $language = SucuriScanOption::get_option(':language');
    $params['Integrity.LanguageDropdown'] = SucuriScanTemplate::selectOptions($languages, $language);
    $params['Integrity.WordPressLocale'] = get_locale();

    return SucuriScanTemplate::getSection('settings-corefiles-language', $params);
}

function sucuriscan_settings_corefiles_cache($nonce)
{
    $params = array();
    $fpath = SucuriScan::datastore_folder_path('sucuri-integrity.php');

    if ($nonce) {
        // Reset core integrity files marked as fixed
        if (SucuriScanRequest::post(':corefiles_cache')) {
            if (file_exists($fpath)) {
                if (@unlink($fpath)) {
                    $message = 'Core integrity files marked as fixed were successfully reset.';

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

    $params['CoreFiles.CacheSize'] = SucuriScan::human_filesize(@filesize($fpath));
    $params['CoreFiles.CacheLifeTime'] = SUCURISCAN_SITECHECK_LIFETIME;
    $params['CoreFiles.TableVisibility'] = 'hidden';
    $params['CoreFiles.IgnoredFiles'] = '';
    $cache = new SucuriScanCache('integrity');
    $ignored_files = $cache->getAll();
    $counter = 0;

    if ($ignored_files) {
        $params['CoreFiles.TableVisibility'] = 'visible';

        foreach ($ignored_files as $hash => $data) {
            $params['CoreFiles.IgnoredFiles'] .= SucuriScanTemplate::getSnippet(
                'settings-corefiles-cache',
                array(
                    'IgnoredFile.CssClass' => ($counter % 2 === 0) ? '' : 'alternate',
                    'IgnoredFile.UniqueId' => substr($hash, 0, 8),
                    'IgnoredFile.FilePath' => $data->file_path,
                    'IgnoredFile.StatusType' => $data->file_status,
                    'IgnoredFile.IgnoredAt' => SucuriScan::datetime($data->ignored_at),
                )
            );
            $counter++;
        }
    }

    return SucuriScanTemplate::getSection('settings-corefiles-cache', $params);
}
