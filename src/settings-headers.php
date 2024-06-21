<?php

/**
 * Code related to the settings-alerts.php interface.
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

define('SUCURISCAN_CACHEOPTIONS_DATASTORE_FILE', 'sucuri-cacheoptions.php');

/**
 * Get the filepath where the information of the cache options is stored.
 *
 * @return string Absolute filepath where the ache options is stored.
 */
function sucuriscan_cacheoptions_datastore_filepath()
{
	return SucuriScan::dataStorePath('sucuri-cacheoptions.php');
}

/**
 * Check whether the cache options datastore file exists or not, if not then
 * we try to create the file and check again the success of the operation.
 *
 * @return string|bool Path to the storage file if exists, false otherwise.
 */
function sucuriscan_cacheoptions_datastore_exists()
{
	$fpath = sucuriscan_cacheoptions_datastore_filepath();

	if (!file_exists($fpath)) {
		@file_put_contents($fpath, "<?php exit(0); ?>\n", LOCK_EX);
	}

	return file_exists($fpath) ? $fpath : false;
}

/**
 * Check whether cache options datastore file is writable or not, if not
 * we try to set the right permissions and check again the success of the operation.
 *
 * @return string|bool Path to the storage file if writable, false otherwise.
 */
function sucuriscan_cacheoptions_datastore_is_writable()
{
	$datastore_filepath = sucuriscan_cacheoptions_datastore_filepath();

	if ($datastore_filepath) {
		if (!is_writable($datastore_filepath)) {
			@chmod($datastore_filepath, 0644);
		}

		if (is_writable($datastore_filepath)) {
			return $datastore_filepath;
		}
	}

	return false;
}

/**
 * Check whether the cache options datastore file is readable or not, if not
 * we try to set the right permissions and check again the success of the operation.
 *
 * @return string|bool Path to the storage file if readable, false otherwise.
 */
function sucuriscan_cacheoptions_datastore_is_readable()
{
	$datastore_filepath = sucuriscan_cacheoptions_datastore_exists();

	if ($datastore_filepath && is_readable($datastore_filepath)) {
		return $datastore_filepath;
	}

	return false;
}

/**
 * Returns the HTML to configure cache options.
 * TODO: Update this comment.
 * By default the plugin sends the email notifications about the security events
 * to the first email address used during the installation of the website. This
 * is usually the email of the website owner. The plugin allows to add more
 * emails to the list so the alerts are sent to other people.
 *
 * @param  bool $nonce True if the CSRF protection worked, false otherwise.
 * @return string      HTML for the email alert recipients.
 */
function sucuriscan_settings_cache_options($nonce)
{
    $params = array(
        'CacheOptions.Options' => '',
        'CacheOptions.Modes' => '',
    );

    $availableSettings = array('disabled', 'static', 'occasional', 'frequent', 'busy', 'custom');

    foreach ($availableSettings as $mode) {
        $params['CacheOptions.Modes'] .= sprintf('<option value="%s">%s</option>', $mode, ucfirst($mode));
    }


    if (SucuriScanInterface::checkNonce() && SucuriScanRequest::post(':update_cache_options')) {
        $headerCacheControl = SucuriScanRequest::post(':cache_options_mode');


        // stop progress if the options are not valid
//        if (empty($headerCacheControl) || !in_array($headerCacheControl, $availableSettings)) {
//            SucuriScanInterface::error(__('No frequency selected for the automatic secret key updater.',
//                'sucuri-scanner'));
//        }

        SucuriScanOption::updateOption(':headers_cache_control', $headerCacheControl);

        if (SucuriScanOption::getOption(':headers_cache_control') === 'disabled') {
            SucuriScanInterface::info(__('Cache-Control header was deactivated.', 'sucuri-scanner'));
        } else {
            SucuriScanInterface::info(__('Cache-Control header was activated.', 'sucuri-scanner'));
        }
    }

    $options = SucuriScanOption::getOption(':cache_options');

	foreach ($options as $option) {
		$params['CacheOptions.Options'] .= SucuriScanTemplate::getSnippet(
			'settings-headers-cache-option',
			array(
				'name' => $option['title'],
				'maxAge' => $option['max_age'],
				'sMaxAge' => $option['s_maxage'],
				'staleIferror' => $option['stale_if_error'],
				'staleWhileRevalidate' => $option['stale_while_revalidate'],
				'paginationFactor' => $option['pagination_factor'],
				'oldAgeMultiplier' => $option['old_age_multiplier'],
			)
		);
	}

    $headersCacheControlMode = SucuriScanOption::getOption(':headers_cache_control');
    $isCacheControlHeaderDisabled = $headersCacheControlMode === 'disabled';
    $params['CacheOptions.NoItemsVisibility'] = 'hidden';
    $params['CacheOptions.CacheControl'] = $isCacheControlHeaderDisabled ? 0 : 1;
    $params['CacheOptions.Status'] = $isCacheControlHeaderDisabled ? 'Disabled' : 'Enabled';
    $params['CacheOptions.Modes'] = str_replace('option value="'.$headersCacheControlMode.'"', 'option value="'.$headersCacheControlMode.'" selected', $params['CacheOptions.Modes']);

	return SucuriScanTemplate::getSection('settings-headers-cache', $params);
}