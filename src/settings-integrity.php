<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

class SucuriScanSettingsIntegrity extends SucuriScanSettings
{
    public static function language($nonce)
    {
        $params = array();
        $languages = SucuriScan::languages();

        if ($nonce) {
            // Configure the language for the core integrity checks.
            if ($language = SucuriScanRequest::post(':set_language')) {
                if (array_key_exists($language, $languages)) {
                    $message = 'Language for the core integrity checks set to <code>' . $language . '</code>';

                    SucuriScanOption::updateOption(':language', $language);
                    SucuriScanEvent::reportAutoEvent($message);
                    SucuriScanEvent::notifyEvent('plugin_change', $message);
                    SucuriScanInterface::info($message);
                } else {
                    SucuriScanInterface::error('Selected language is not supported.');
                }
            }
        }

        $language = SucuriScanOption::getOption(':language');
        $params['Integrity.LanguageDropdown'] = SucuriScanTemplate::selectOptions($languages, $language);
        $params['Integrity.WordPressLocale'] = get_locale();

        return SucuriScanTemplate::getSection('settings-scanner-integrity-language', $params);
    }

    public static function cache($nonce)
    {
        $params = array();
        $cache = new SucuriScanCache('integrity');
        $fpath = SucuriScan::dataStorePath('sucuri-integrity.php');

        if ($nonce && SucuriScanRequest::post(':reset_integrity_cache')) {
            $deletedFiles = array();
            $files = SucuriScanRequest::post(':corefile_path', '_array');

            foreach ($files as $path) {
                if ($cache->delete(md5($path))) {
                    $deletedFiles[] = $path;
                }
            }

            if (!empty($deletedFiles)) {
                $message = 'Core files that will not be ignored anymore: '
                . '(multiple entries): ' . implode(',', $deletedFiles);
                SucuriScanInterface::info('Selected files will not be ignored anymore.');
                SucuriScanEvent::reportDebugEvent($message);
            }
        }

        $params['IgnoredFiles'] = '';
        $params['CacheSize'] = SucuriScan::humanFileSize(@filesize($fpath));
        $params['CacheLifeTime'] = SUCURISCAN_SITECHECK_LIFETIME;
        $params['NoFilesVisibility'] = 'visible';

        if ($ignored_files = $cache->getAll()) {
            $params['NoFilesVisibility'] = 'hidden';

            foreach ($ignored_files as $hash => $data) {
                $params['IgnoredFiles'] .= SucuriScanTemplate::getSnippet('settings-scanner-integrity-cache', array(
                    'UniqueId' => substr($hash, 0, 8),
                    'FilePath' => $data->file_path,
                    'StatusType' => $data->file_status,
                    'IgnoredAt' => SucuriScan::datetime($data->ignored_at),
                ));
            }
        }

        return SucuriScanTemplate::getSection('settings-scanner-integrity-cache', $params);
    }
}
