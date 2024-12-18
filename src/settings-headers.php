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
function sucuriscan_get_csp_directive_html($directive, $option)
{
    $type = isset($option['type']) ? $option['type'] : 'text';
    $description = isset($option['description']) ? $option['description'] : '';
    $directiveOptions = isset($option['options']) ? $option['options'] : array();
    $isDirectiveEnforced = isset($option['enforced']) && (bool)$option['enforced'];
    $value = isset($option['value']) ? $option['value'] : '';

    $enforcedChecked = $isDirectiveEnforced ? 'checked' : '';

    if ($type === 'multi_checkbox') {
        $options = '';
        $currentValues = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($directiveOptions as $token => $optionObj) {
            $checked = in_array($token, $currentValues) ? ' checked' : '';
            $options .= sprintf(
                '<div>
                    <input type="checkbox" name="sucuriscan_csp_%s[]" value="%s"%s>
                    <label>%s</label>
                </div>',
                sanitize_text_field($directive),
                sanitize_text_field($token),
                $checked,
                sanitize_text_field($optionObj['title'])
            );
        }
    } else {
        // text input for normal directives
        $options = sprintf(
            '<input type="text" name="sucuriscan_csp_%s" value="%s" />',
            sanitize_text_field($directive),
            esc_attr($value)
        );
    }

    return SucuriScanTemplate::getSnippet(
        'settings-headers-csp-directive',
        array(
            'id' => sanitize_text_field($option['id']),
            'directive' => sanitize_text_field($directive),
            'displayName' => sanitize_text_field($option['title']),
            'description' => esc_html($description),
            'EnforcedChecked' => $enforcedChecked,
            'options' => $options,
        )
    );
}

/**
 * Maps the posted CSP directive values to the new options array.
 *
 * @param array $headersCSPControlOptions Existing CSP options from the store.
 *
 * @return array Updated CSP options array with enforced and value fields updated.
 */
function sucuriscan_map_csp_options($headersCSPControlOptions)
{
    $newOptions = array();

    foreach ($headersCSPControlOptions as $directive => $option) {
        $type = isset($option['type']) ? $option['type'] : 'text';
        $postKey = 'sucuriscan_csp_' . $directive;
        $enforcedKey = 'sucuriscan_enforced_' . $directive;

        // Determine if enforced is checked
        $enforced = isset($_POST[$enforcedKey]) && $_POST[$enforcedKey] == '1';

        // Handle text directives
        if ($type === 'text') {
            // If directive value is set in $_POST, sanitize and store it
            if (isset($_POST[$postKey])) {
                $postValue = wp_unslash($_POST[$postKey]);
                $postValue = SucuriScanCSPHeaders::sanitize_csp_directive(sanitize_text_field($postValue));

                $newOptions[$directive] = array(
                    'id' => esc_attr($option['id']),
                    'title' => esc_html($option['title']),
                    'type' => $type,
                    'description' => isset($option['description']) ? esc_html($option['description']) : '',
                    'options' => isset($option['options']) ? $option['options'] : array(),
                    'enforced' => $enforced,
                    'value' => $postValue,
                );
                continue;
            }

            // If not set in $_POST, keep original but update enforced
            $option['enforced'] = $enforced;
            $newOptions[$directive] = $option;
            continue;
        }

        // Handle multi_checkbox directives
        if ($type === 'multi_checkbox') {
            $selectedValues = array();
            if (isset($_POST[$postKey]) && is_array($_POST[$postKey])) {
                foreach ($_POST[$postKey] as $val) {
                    $token = SucuriScanCSPHeaders::sanitize_csp_directive(sanitize_text_field($val));
                    if (!empty($token)) {
                        $selectedValues[] = $token;
                    }
                }
            }

            $finalValue = empty($selectedValues) ? '' : implode(' ', $selectedValues);

            $newOptions[$directive] = array(
                'id' => esc_attr($option['id']),
                'title' => esc_html($option['title']),
                'type' => $type,
                'description' => isset($option['description']) ? esc_html($option['description']) : '',
                'options' => isset($option['options']) ? $option['options'] : array(),
                'enforced' => $enforced,
                'value' => $finalValue,
            );
            continue;
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
            $newOptions = sucuriscan_map_csp_options($headersCSPControlOptions);

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
        $params['CSPOptions.Options'] .= sucuriscan_get_csp_directive_html($directive, $option);
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
