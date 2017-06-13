<?php

/**
 * Code related to the settings-integrity.php interface.
 *
 * @package Sucuri Security
 * @subpackage settings-integrity.php
 * @copyright Since 2010 Sucuri Inc.
 */

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Settings for the WordPress integrity scanner.
 *
 * Generates the HTML code to display a list of options in the settings page to
 * allow the website owner to configure the functionality of the WordPress core
 * integrity scanner and the optional Unix diff utility. This also includes some
 * options to configure the website installation language and the false/positive
 * cache file.
 */
class SucuriScanSettingsIntegrity extends SucuriScanSettings
{
    /**
     * Configures the diffUtility for the integrity scanner.
     *
     * @param bool $nonce True if the CSRF protection worked, false otherwise.
     * @return string HTML code to render the configuration panel.
     */
    public static function diffUtility($nonce)
    {
        $params = array();

        $params['DiffUtility.StatusNum'] = 0;
        $params['DiffUtility.Status'] = __('Disabled', SUCURISCAN_TEXTDOMAIN);
        $params['DiffUtility.SwitchText'] = __('Enable', SUCURISCAN_TEXTDOMAIN);
        $params['DiffUtility.SwitchValue'] = 'enable';

        if ($nonce) {
            // Enable or disable the Unix diff utility.
            if ($status = SucuriScanRequest::post(':diff_utility', '(en|dis)able')) {
                if (!SucuriScanCommand::exists('diff')) {
                    SucuriScanInterface::error(__('DiffUtilityMissing', SUCURISCAN_TEXTDOMAIN));
                } else {
                    $status = $status . 'd'; /* add past tense */
                    $message = 'Integrity diff utility has been <code>' . $status . '</code>';

                    SucuriScanOption::updateOption(':diff_utility', $status);
                    SucuriScanEvent::reportInfoEvent($message);
                    SucuriScanEvent::notifyEvent('plugin_change', $message);
                    SucuriScanInterface::info(__('DiffUtilityStatus', SUCURISCAN_TEXTDOMAIN));
                }
            }
        }

        if (SucuriScanOption::isEnabled(':diff_utility')) {
            $params['DiffUtility.StatusNum'] = 1;
            $params['DiffUtility.Status'] = __('Enabled', SUCURISCAN_TEXTDOMAIN);
            $params['DiffUtility.SwitchText'] = __('Disable', SUCURISCAN_TEXTDOMAIN);
            $params['DiffUtility.SwitchValue'] = 'disable';
        }

        return SucuriScanTemplate::getSection('settings-scanner-integrity-diff-utility', $params);
    }

    /**
     * Configures the language for the integrity scanner.
     *
     * @param bool $nonce True if the CSRF protection worked, false otherwise.
     * @return string HTML code to render the configuration panel.
     */
    public static function language($nonce)
    {
        $params = array();
        $languages = SucuriScan::languages();

        if ($nonce) {
            // Configure the language for the core integrity checks.
            if ($language = SucuriScanRequest::post(':set_language')) {
                if (array_key_exists($language, $languages)) {
                    $message = 'Core integrity language set to <code>' . $language . '</code>';

                    SucuriScanOption::updateOption(':language', $language);
                    SucuriScanEvent::reportAutoEvent($message);
                    SucuriScanEvent::notifyEvent('plugin_change', $message);
                    SucuriScanInterface::info(__('IntegrityLanguage', SUCURISCAN_TEXTDOMAIN));
                } else {
                    SucuriScanInterface::error(__('NonSupportedLanguage', SUCURISCAN_TEXTDOMAIN));
                }
            }
        }

        $language = SucuriScanOption::getOption(':language');
        $params['Integrity.LanguageDropdown'] = SucuriScanTemplate::selectOptions($languages, $language);
        $params['Integrity.WordPressLocale'] = get_locale();

        return SucuriScanTemplate::getSection('settings-scanner-integrity-language', $params);
    }

    /**
     * Configures the cache for the integrity scanner.
     *
     * @param bool $nonce True if the CSRF protection worked, false otherwise.
     * @return string HTML code to render the configuration panel.
     */
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
                SucuriScanEvent::reportDebugEvent('Core files that will not be '
                . 'ignored anymore: (multiple entries): ' . implode(',', $deletedFiles));
                SucuriScanInterface::info(__('ItemsProcessed', SUCURISCAN_TEXTDOMAIN));
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
