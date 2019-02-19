<?php

/**
 * Code related to the settings-integrity.php interface.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
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
 * options to configure the website installation language and the false positive
 * cache file.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanSettingsIntegrity extends SucuriScanSettings
{
    /**
     * Configures the diffUtility for the integrity scanner.
     *
     * @param  bool $nonce True if the CSRF protection worked, false otherwise.
     * @return string      HTML code to render the configuration panel.
     */
    public static function diffUtility($nonce)
    {
        $params = array();

        $params['DiffUtility.StatusNum'] = 0;
        $params['DiffUtility.Status'] = 'Disabled';
        $params['DiffUtility.SwitchText'] = 'Enable';
        $params['DiffUtility.SwitchValue'] = 'enable';

        if ($nonce) {
            // Enable or disable the Unix diff utility.
            $status = SucuriScanRequest::post(':diff_utility', '(en|dis)able');

            if ($status) {
                if (!SucuriScanCommand::exists('diff')) {
                    SucuriScanInterface::error(__('Your hosting provider has blocked the execution of external commands.', 'sucuri-scanner'));
                } else {
                    $status = $status . 'd'; /* add past tense */
                    $message = sprintf(__('Integrity diff utility has been <code>%s</code>', 'sucuri-scanner'), $status);

                    SucuriScanOption::updateOption(':diff_utility', $status);
                    SucuriScanEvent::reportInfoEvent($message);
                    SucuriScanEvent::notifyEvent('plugin_change', $message);
                    SucuriScanInterface::info(__('The status of the integrity diff utility has been changed', 'sucuri-scanner'));
                }
            }
        }

        if (SucuriScanOption::isEnabled(':diff_utility')) {
            $params['DiffUtility.StatusNum'] = 1;
            $params['DiffUtility.Status'] = __('Enabled', 'sucuri-scanner');
            $params['DiffUtility.SwitchText'] = __('Disable', 'sucuri-scanner');
            $params['DiffUtility.SwitchValue'] = 'disable';
        }

        return SucuriScanTemplate::getSection('settings-scanner-integrity-diff-utility', $params);
    }

    /**
     * Configures the cache for the integrity scanner.
     *
     * @param  bool $nonce True if the CSRF protection worked, false otherwise.
     * @return string      HTML code to render the configuration panel.
     */
    public static function cache($nonce)
    {
        $params = array();
        $cache = new SucuriScanCache('integrity');
        $fpath = SucuriScan::dataStorePath('sucuri-integrity.php');

        if ($nonce && SucuriScanRequest::post(':reset_integrity_cache')) {
            $deletedFiles = array();
            $files = SucuriScanRequest::post(':corefile_path', '_array');

            foreach ((array) $files as $path) {
                if ($cache->delete(md5($path))) {
                    $deletedFiles[] = $path;
                }
            }

            if (!empty($deletedFiles)) {
                SucuriScanEvent::reportDebugEvent(
                    sprintf(__('Core files that will not be ignored anymore: (multiple entries): %s', 'sucuri-scanner'), implode(',', $deletedFiles))
                );
                SucuriScanInterface::info(__('The selected files have been successfully processed.', 'sucuri-scanner'));
            }
        }

        $params['IgnoredFiles'] = '';
        $params['CacheSize'] = SucuriScan::humanFileSize(@filesize($fpath));
        $params['CacheLifeTime'] = SUCURISCAN_SITECHECK_LIFETIME;
        $params['NoFilesVisibility'] = 'visible';

        $ignored_files = $cache->getAll();

        if (is_array($ignored_files) && !empty($ignored_files)) {
            $params['NoFilesVisibility'] = 'hidden';

            foreach ($ignored_files as $hash => $data) {
                $params['IgnoredFiles'] .= SucuriScanTemplate::getSnippet(
                    'settings-scanner-integrity-cache',
                    array(
                        'UniqueId' => substr($hash, 0, 8),
                        'FilePath' => $data->file_path,
                        'StatusType' => $data->file_status,
                        'IgnoredAt' => SucuriScan::datetime($data->ignored_at),
                    )
                );
            }
        }

        return SucuriScanTemplate::getSection('settings-scanner-integrity-cache', $params);
    }
}
