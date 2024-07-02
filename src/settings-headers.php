<?php

/**
 * Code related to the cache control headers settings.
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
 * Returns the HTML to configure the header's cache options.
 *
 * WordPress by default does not come with cache control headers,
 * used by WAFs and CDNs and that are useful to both improve performance
 * and reduce bandwidth and other resources demand on the hosting server.
 *
 * @param bool $nonce True if the CSRF protection worked, false otherwise.
 * @return  string          HTML for the email alert recipients.
 */
function sucuriscan_settings_cache_options($nonce)
{
    $isWooCommerceActive = in_array('woocommerce/woocommerce.php',
        apply_filters('active_plugins', get_option('active_plugins')));

    $params = array(
        'CacheOptions.Options' => '',
        'CacheOptions.Modes' => '',
    );

    $availableSettings = array(
        __('disabled', 'sucuri-scanner'),
        __('static', 'sucuri-scanner'),
        __('occasional', 'sucuri-scanner'),
        __('frequent', 'sucuri-scanner'),
        __('busy', 'sucuri-scanner'),
        __('custom', 'sucuri-scanner'),
    );
    $headersCacheControlOptions = SucuriScanOption::getOption(':headers_cache_control_options');

    foreach ($availableSettings as $mode) {
        $params['CacheOptions.Modes'] .= sprintf('<option value="%s">%s</option>', $mode, ucfirst($mode));
    }

    if (SucuriScanInterface::checkNonce() && SucuriScanRequest::post(':update_cache_options')) {
        $headerCacheControlMode = sanitize_text_field(SucuriScanRequest::post(':cache_options_mode'));
        $newOptions = array();

        foreach ($headersCacheControlOptions as $pageType => $options) {
            $newOptions[$pageType] = array();

            foreach ($options as $optionName => $defaultValue) {
                $postKey = 'sucuriscan_' . $pageType . '_' . $optionName;
                $postValue = sanitize_text_field(SucuriScanRequest::post($postKey));

                if (isset($_POST[$postKey])) {
                    if ($postValue === 'unavailable' || $postValue === '') {
                        $newOptions[$pageType][$optionName] = 'unavailable';
                    } else {
                        $newOptions[$pageType][$optionName] = intval($postValue);
                    }
                } else {
                    $newOptions[$pageType][$optionName] = $defaultValue;
                }
            }
        }

        if (in_array($headerCacheControlMode, $availableSettings)) {
            SucuriScanOption::updateOption(':headers_cache_control', $headerCacheControlMode);
            SucuriScanOption::updateOption(':headers_cache_control_options', $newOptions);

            if ($headerCacheControlMode === 'disabled') {
                SucuriScanInterface::info(__('Cache-Control header was deactivated.', 'sucuri-scanner'));
            } else {
                SucuriScanInterface::info(__('Cache-Control header was activated.', 'sucuri-scanner'));
            }
        } else {
            SucuriScanInterface::error(__('Invalid cache control mode selected.', 'sucuri-scanner'));
        }
    }

    $latestHeadersCacheControlOptions = SucuriScanOption::getOption(':headers_cache_control_options');

    foreach ($latestHeadersCacheControlOptions as $option) {
        if (!$isWooCommerceActive && in_array($option['id'],
                array('woocommerce_products', 'woocommerce_categories'))) {
            continue;
        }

        $params['CacheOptions.Options'] .= SucuriScanTemplate::getSnippet(
            'settings-headers-cache-option',
            array(
                'id' => $option['id'],
                'name' => $option['title'],
                'maxAge' => $option['max_age'],
                'sMaxAge' => $option['s_maxage'],
                'staleIfError' => $option['stale_if_error'],
                'staleWhileRevalidate' => $option['stale_while_revalidate'],
                'paginationFactor' => $option['pagination_factor'],
                'paginationFactorVisibility' => $option['pagination_factor'] !== 'unavailable' ? 'visible' : 'hidden',
                'oldAgeMultiplier' => $option['old_age_multiplier'],
                'oldAgeMultiplierVisibility' => $option['old_age_multiplier'] !== 'unavailable' ? 'visible' : 'hidden',
            )
        );
    }

    $headersCacheControlMode = SucuriScanOption::getOption(':headers_cache_control');
    $isCacheControlHeaderDisabled = $headersCacheControlMode === 'disabled';

    $params['CacheOptions.NoItemsVisibility'] = 'hidden';
    $params['CacheOptions.CacheControl'] = $isCacheControlHeaderDisabled ? 0 : 1;
    $params['CacheOptions.Status'] = $isCacheControlHeaderDisabled ? __('Disabled', 'sucuri-scanner') : __('Enabled',
        'sucuri-scanner');
    $params['CacheOptions.Modes'] = str_replace('option value="' . $headersCacheControlMode . '"',
        'option value="' . $headersCacheControlMode . '" selected', $params['CacheOptions.Modes']);

    return SucuriScanTemplate::getSection('settings-headers-cache', $params);
}
