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
    if (!SucuriScanInterface::checkNonce()) {
        SucuriScanInterface::error(__('Invalid nonce.', 'sucuri-scanner'));
        return '';
    }

    $isWooCommerceActive = in_array(
        'woocommerce/woocommerce.php',
        apply_filters('active_plugins', get_option('active_plugins'))
    );

    $params = array(
        'CacheOptions.Options' => '',
        'CacheOptions.Modes' => '',
    );

	$params['URL.Headers'] = admin_url('admin.php?page=sucuriscan_headers_management');

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
        if (!$isWooCommerceActive && in_array(
            $option['id'],
            array('woocommerce_products', 'woocommerce_categories')
        )) {
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
    $params['CacheOptions.Status'] = $isCacheControlHeaderDisabled ? __('Disabled', 'sucuri-scanner') : __(
        'Enabled',
        'sucuri-scanner'
    );
    $params['CacheOptions.Modes'] = str_replace(
        'option value="' . $headersCacheControlMode . '"',
        'option value="' . $headersCacheControlMode . '" selected',
        $params['CacheOptions.Modes']
    );

    return SucuriScanTemplate::getSection('settings-headers-cache', $params);
}

/**
 * Returns the HTML to configure the CSP security options.
 *
 * @param string $directive Name of the directive.
 * @param object $option Associative array with info of the directive.
 *
 * @return  string          HTML for the security CSP header.
 */
function sucuriscan_get_directive_html($directive, $option, $prefix)
{
    $type = isset($option['type']) ? $option['type'] : 'text';
    $description = isset($option['description']) ? $option['description'] : '';
    $directiveOptions = isset($option['options']) ? $option['options'] : array();
    $isDirectiveEnforced = isset($option['enforced']) && (bool)$option['enforced'];
    $value = isset($option['value']) ? $option['value'] : '';

    $enforcedChecked = $isDirectiveEnforced ? 'checked' : '';

    if ($type === 'multi_checkbox') {
        $options = '';

        foreach ($directiveOptions as $token => $optionObj) {
            $checked = $optionObj['enforced'] ? 'checked' : '';

            if (isset($option['value']) && is_string($option['value'])) {
                $currentValues = preg_split('/\s+/', $option['value'], -1, PREG_SPLIT_NO_EMPTY);
                $checked = in_array($token, $currentValues) ? 'checked' : '';
            }

            $options .= sprintf(
                '<div>
                    <input type="checkbox" name="sucuriscan_%s_%s_%s" value="1" %s>
                    <label>%s</label>
                </div>',
                sanitize_text_field($prefix),
                sanitize_text_field($directive),
                sanitize_text_field($token),
                $checked,
                sanitize_text_field($optionObj['title'])
            );
        }
    } else {
        // text input for normal directives
        $options = sprintf(
            '<input type="text" name="sucuriscan_%s_%s" value="%s" />',
            sanitize_text_field($prefix),
            sanitize_text_field($directive),
            esc_attr($value)
        );
    }

    return SucuriScanTemplate::getSnippet(
        'settings-headers-directive',
        array(
            'id' => sanitize_text_field($option['id']),
            'directive' => sanitize_text_field($directive),
            'displayName' => sanitize_text_field($option['title']),
            'description' => esc_html($description),
            'EnforcedChecked' => $enforcedChecked,
            'options' => $options,
            'prefix' => $prefix,
        )
    );
}

/**
 * Maps the posted directive values to the new options array.
 *
 * @param array Existing options from the store.
 *
 * @return array Updated options array with enforced and value fields updated.
 */
function sucuriscan_map_directive_options($headerOptions, $prefix)
{
    $newOptions = array();

    foreach ($headerOptions as $directive => $option) {
        $type = isset($option['type']) ? $option['type'] : 'text';
        $postKey = $prefix . '_' . $directive;
        $enforcedKey = 'sucuriscan_enforced_' . $directive;

        // Determine if directive is enforced
        $enforced = SucuriScanRequest::post($enforcedKey) === '1' ? true : false;

        if ($type === 'text') {
            if (SucuriScanRequest::post($postKey)) {
                $postValue = wp_unslash($_POST[$postKey]);

                $newOptions[$directive] = array(
                    'id' => esc_attr($option['id']),
                    'title' => esc_html($option['title']),
                    'type' => esc_html($type),
                    'description' => isset($option['description']) ? esc_html($option['description']) : '',
                    'value' => sanitize_text_field($postValue),
                    'enforced' => $enforced,
                );

                continue;
            }

            // If not set in $_POST, keep original but update enforced
            $option['enforced'] = $enforced;
            $newOptions[$directive] = $option;

            continue;
        }

        if ($type === 'multi_checkbox') {
            $newOptionsValues = array();

            if (isset($option['options']) && is_array($option['options'])) {
                foreach ($option['options'] as $token => $optionObj) {
                    $checked = SucuriScanRequest::post($postKey . '_' . $token) === '1' ? true : false;

                    if (isset($option['value']) && is_string($option['value'])) {
                        $currentValues = preg_split('/\s+/', $option['value'], -1, PREG_SPLIT_NO_EMPTY);
                        $checked = in_array($token, $currentValues) ? true : false;
                    }

                    $newOptionsValues[$token] = $optionObj;
                    $newOptionsValues[$token]['enforced'] = $checked;
                }
            }

            $newOptions[$directive] = array(
                'id' => esc_attr($option['id']),
                'title' => esc_html($option['title']),
                'type' => esc_html($type),
                'description' => isset($option['description']) ? esc_html($option['description']) : '',
                'options' => $newOptionsValues,
                'enforced' => $enforced,
            );
        }
    }

    return $newOptions;
}

/**
 * Returns the HTML to configure the header's CSP options.
 *
 * @param bool $nonce True if the CSRF protection worked, false otherwise.
 *
 * @return string HTML for the CSP settings.
 */
function sucuriscan_settings_csp_options($nonce)
{
    if (!SucuriScanInterface::checkNonce()) {
        SucuriScanInterface::error(__('Invalid nonce.', 'sucuri-scanner'));
        return '';
    }

    $params = array(
        'CSPOptions.Options' => '',
        'CSPOptions.Modes' => '',
        'CSPOptions.Status' => '',
        'CSPOptions.CSPControl' => '',
    );

	$params['URL.Headers'] = admin_url('admin.php?page=sucuriscan_headers_management');

    $headersCSPControlOptions = SucuriScanOption::getOption(':headers_csp_options');

    $availableModes = array(
        'disabled' => __('Disabled', 'sucuri-scanner'),
        'report-only' => __('Report Only', 'sucuri-scanner'),
    );

    // Process form submission
    if (SucuriScanRequest::post(':update_csp_options')) {
        $headerCSPMode = sanitize_text_field(SucuriScanRequest::post(':csp_options_mode'));

        // Validate selected CSP mode
        if (!array_key_exists($headerCSPMode, $availableModes)) {
            SucuriScanInterface::error(__('Invalid CSP mode selected.', 'sucuri-scanner'));
        } else {
            $newOptions = sucuriscan_map_directive_options($headersCSPControlOptions, 'sucuriscan_csp');

            // Save new options if valid
            SucuriScanOption::updateOption(':headers_csp', $headerCSPMode);
            SucuriScanOption::updateOption(':headers_csp_options', $newOptions);
            SucuriScanInterface::info(__('Content Security Policy settings were updated.', 'sucuri-scanner'));
        }
    }

    // Get the latest CSP options after update
    $headersCSPControl = SucuriScanOption::getOption(':headers_csp');
    $headersCSPControlOptions = SucuriScanOption::getOption(':headers_csp_options');
    $isCSPControlHeaderDisabled = ($headersCSPControl === 'disabled');

    $params['CSPOptions.CSPControl'] = $isCSPControlHeaderDisabled ? 0 : 1;

    foreach ($headersCSPControlOptions as $directive => $option) {
        $params['CSPOptions.Options'] .= sucuriscan_get_directive_html($directive, $option, 'csp');
    }

    // Render CSP mode dropdown
    foreach ($availableModes as $modeValue => $modeLabel) {
        $selected = ($headersCSPControl === $modeValue) ? ' selected' : '';
        $params['CSPOptions.Modes'] .= sprintf(
            '<option value="%s"%s>%s</option>',
            esc_attr($modeValue),
            $selected,
            esc_html($modeLabel)
        );
    }

    // Set CSP status
    $params['CSPOptions.Status'] = $isCSPControlHeaderDisabled ? __('Disabled', 'sucuri-scanner') : __(
        'Report Only',
        'sucuri-scanner'
    );

    return SucuriScanTemplate::getSection('settings-headers-csp', $params);
}

/**
 * Returns the HTML to configure the header's CORS options.
 *
 * @param bool $nonce True if the CSRF protection worked, false otherwise.
 *
 * @return string HTML for the CORS settings.
 */
function sucuriscan_settings_cors_options($nonce)
{
    if (!SucuriScanInterface::checkNonce()) {
        SucuriScanInterface::error(__('Invalid nonce.', 'sucuri-scanner'));
        return '';
    }

    $params = array(
        'CORSOptions.Options' => '',
        'CORSOptions.Modes' => '',
        'CORSOptions.Status' => '',
        'CORSOptions.CORSControl' => '',
    );

	$params['URL.Headers'] = admin_url('admin.php?page=sucuriscan_headers_management');

	$headersCORSControlOptions = SucuriScanOption::getOption(':headers_cors_options');

    $availableModes = array(
        'disabled' => __('Disabled', 'sucuri-scanner'),
        'enabled' => __('Enabled', 'sucuri-scanner'),
    );

    // Process form submission
    if (SucuriScanRequest::post(':update_cors_options')) {
        $headerCORSMode = sanitize_text_field(SucuriScanRequest::post(':cors_options_mode'));

        // Validate selected CORS mode
        if (!array_key_exists($headerCORSMode, $availableModes)) {
            SucuriScanInterface::error(__('Invalid CORS mode selected.', 'sucuri-scanner'));
        } else {
            $newOptions = sucuriscan_map_directive_options($headersCORSControlOptions, 'sucuriscan_cors');

            // Save new options if valid
            SucuriScanOption::updateOption(':headers_cors', $headerCORSMode);
            SucuriScanOption::updateOption(':headers_cors_options', $newOptions);
            SucuriScanInterface::info(__('CORS settings were updated.', 'sucuri-scanner'));
        }
    }

    // Get the latest CORS options after update
    $headersCORSControl = SucuriScanOption::getOption(':headers_cors');
    $headersCORSControlOptions = SucuriScanOption::getOption(':headers_cors_options');
    $isCORSControlHeaderDisabled = ($headersCORSControl === 'disabled');

    $params['CORSOptions.CORSControl'] = $isCORSControlHeaderDisabled ? 0 : 1;

    foreach ($headersCORSControlOptions as $directive => $option) {
        $params['CORSOptions.Options'] .= sucuriscan_get_directive_html($directive, $option, 'cors');
    }

    // Render CORS mode dropdown
    foreach ($availableModes as $modeValue => $modeLabel) {
        $selected = ($headersCORSControl === $modeValue) ? ' selected' : '';
        $params['CORSOptions.Modes'] .= sprintf(
            '<option value="%s"%s>%s</option>',
            esc_attr($modeValue),
            $selected,
            esc_html($modeLabel)
        );
    }

    // Set CORS status
    $params['CORSOptions.Status'] = $isCORSControlHeaderDisabled ? __('Disabled', 'sucuri-scanner') : __(
        'Enabled',
        'sucuri-scanner'
    );

    return SucuriScanTemplate::getSection('settings-headers-cors', $params);
}
