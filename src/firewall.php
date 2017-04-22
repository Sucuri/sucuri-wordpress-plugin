<?php

/**
 * Code related to the firewall.php interface.
 *
 * @package Sucuri Security
 * @subpackage firewall.php
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
 * Generate the HTML code for the firewall settings panel.
 *
 * @param array|bool $api_key The firewall API key.
 * @return string The parsed-content of the firewall settings panel.
 */
function sucuriscan_firewall_settings($api_key)
{
    $params = array(
        'Firewall.APIKey' => '',
        'Firewall.APIKeyVisibility' => 'hidden',
        'Firewall.APIKeyFormVisibility' => 'visible',
        'Firewall.SettingsVisibility' => 'hidden',
        'Firewall.SettingOptions' => '',
    );

    if ($api_key && array_key_exists('string', $api_key)) {
        $settings = SucuriScanAPI::getFirewallSettings($api_key);

        $params['Firewall.APIKeyVisibility'] = 'visible';
        $params['Firewall.APIKeyFormVisibility'] = 'hidden';
        $params['Firewall.APIKey'] = $api_key['string'];

        if ($settings) {
            $params['Firewall.SettingsVisibility'] = 'visible';
            $settings = sucuriscan_explain_firewall_settings($settings);

            foreach ($settings as $option_name => $option_value) {
                $option_title = ucwords(str_replace('_', "\x20", $option_name));

                // Generate a HTML list when the option's value is an array.
                if (is_array($option_value)) {
                    $css_scrollable = count($option_value) > 10 ? 'sucuriscan-list-as-table-scrollable' : '';
                    $html_list  = '<ul class="sucuriscan-list-as-table ' . $css_scrollable . '">';

                    if (!empty($option_value)) {
                        foreach ($option_value as $single_value) {
                            $single_value = SucuriScan::escape($single_value);
                            $html_list .= '<li>' . SucuriScan::escape($single_value) . '</li>';
                        }
                    } else {
                        $html_list .= '<li>(no data available)</li>';
                    }

                    $html_list .= '</ul>';
                    $option_value = $html_list;
                } else {
                    $option_value = SucuriScan::escape($option_value);
                }

                // Parse the snippet template and replace the pseudo-variables.
                $params['Firewall.SettingOptions'] .= SucuriScanTemplate::getSnippet(
                    'firewall-settings',
                    array(
                        'Firewall.OptionName' => $option_title,
                        'Firewall.OptionValue' => $option_value,
                    )
                );
            }
        }
    }

    return SucuriScanTemplate::getSection('firewall-settings', $params);
}

/**
 * Converts the value of some of the firewall settings into a human-readable
 * text, for example changing numbers or variable names into a more explicit
 * text so the administrator can understand the meaning of these settings.
 *
 * @param array $settings A hash with the settings of a firewall account.
 * @return array The explained version of the firewall settings.
 */
function sucuriscan_explain_firewall_settings($settings = array())
{
    $cache_modes = array(
        'docache' => 'enabled (recommended)',
        'sitecache' => 'site caching (using your site headers)',
        'nocache' => 'minimal (only for a few minutes)',
        'nocacheatall' => 'caching disabled (use with caution)',
    );

    // TODO: Prefer Array over stdClass, modify the API library.
    $settings = @json_decode(json_encode($settings), true);

    foreach ($settings as $keyname => $value) {
        if ($keyname == 'proxy_active') {
            $settings[$keyname] = ($value === 1) ? 'active' : 'not active';
        } elseif ($keyname == 'cache_mode') {
            if (array_key_exists($keyname, $cache_modes)) {
                $settings[$keyname] = $cache_modes[$keyname];
            } else {
                $settings[$keyname] = 'unknown';
            }
        }
    }

    return $settings;
}

/**
 * Generate the HTML code for the firewall logs panel.
 *
 * @return string The parsed-content of the firewall logs panel.
 */
function sucuriscan_firewall_auditlogs()
{
    $date = date('Y-m-d');
    $params = array();

    $params['AuditLogs.DateYears'] = sucuriscan_firewall_dates('years', $date);
    $params['AuditLogs.DateMonths'] = sucuriscan_firewall_dates('months', $date);
    $params['AuditLogs.DateDays'] = sucuriscan_firewall_dates('days', $date);

    return SucuriScanTemplate::getSection('firewall-auditlogs', $params);
}

/**
 * Returns the security logs from the Firewall API.
 *
 * The API allows to filter the logs by day and by user input. This operation
 * depends on the availability of the Firewall API key, if the website owner has
 * not signed up for the Firewall service then they will not have access to this
 * feature. The plugin will display a warning in this case.
 *
 * @return string HTML with the security logs from the Firewall.
 */
function sucuriscan_firewall_auditlogs_ajax()
{
    if (SucuriScanRequest::post('form_action') == 'get_firewall_logs') {
        $response = '';
        $api_key = SucuriScanAPI::getFirewallKey();

        if ($api_key) {
            $query = SucuriScanRequest::post(':query');
            $month = SucuriScanRequest::post(':month');
            $year = SucuriScanRequest::post(':year');
            $day = SucuriScanRequest::post(':day');
            $limit = 50;
            $offset = 1;

            if ($year && $month && $day) {
                $date = sprintf('%s-%s-%s', $year, $month, $day);
            } else {
                $date = date('Y-m-d');
            }

            $auditlogs = SucuriScanAPI::firewallAuditLogs(
                $api_key,
                $date, /* Retrieve the data from this date. */
                $query, /* Filter the data to match this query. */
                $limit, /* Retrieve this maximum of data. */
                $offset /* Retrieve the data from this point. */
            );

            if ($auditlogs && array_key_exists('total_lines', $auditlogs)) {
                $response = sucuriscan_firewall_auditlogs_entries($auditlogs['access_logs']);

                if (empty($response)) {
                    $response = '<tr><td>No data available for this filter.</td></tr>';
                }
            }
        } else {
            SucuriScanInterface::error('Firewall API key was not found.');
        }

        print($response);
        exit(0);
    }
}

/**
 * Returns the security logs from the firewall in HTML.
 *
 * @param array $entries Security logs retrieved from the Firewall API.
 * @return string HTML with the information from the logs.
 */
function sucuriscan_firewall_auditlogs_entries($entries = array())
{
    $output = '';
    $attributes = array(
        'remote_addr',
        'request_date',
        'request_time',
        'request_timezone',
        'request_method',
        'resource_path',
        'http_protocol',
        'http_status',
        'http_status_title',
        'http_referer',
        'http_user_agent',
        'sucuri_block_code',
        'sucuri_block_reason',
        'request_country_name',
        'request_country_code',
    );

    if (is_array($entries) && !empty($entries)) {
        foreach ($entries as $entry) {
            if (array_key_exists('is_usable', $entry) && $entry['is_usable']) {
                $data_set = array();

                foreach ($attributes as $attr) {
                    // Generate variable name for the template pseudo-tags.
                    $keyname = str_replace('_', "\x20", $attr);
                    $keyname = ucwords($keyname);
                    $keyname = str_replace("\x20", '', $keyname);
                    $keyname = 'AccessLog.' . $keyname;

                    // Assign and escape variable value before rendering.
                    if (array_key_exists($attr, $entry)) {
                        $data_set[$keyname] = $entry[$attr];
                    } else {
                        $data_set[$keyname] = '';
                    }

                    // Special cases to convert value to readable data.
                    if ($attr == 'resource_path' && $data_set[$keyname] == '/') {
                        $data_set[$keyname] = '/ (root of the website)';
                    } elseif ($attr == 'http_referer' && $data_set[$keyname] == '-') {
                        $data_set[$keyname] = '- (no referer)';
                    } elseif ($attr == 'request_country_name' && $data_set[$keyname] == '') {
                        $data_set[$keyname] = 'Anonymous';
                    }
                }

                $output .= SucuriScanTemplate::getSnippet('firewall-auditlogs', $data_set);
            }
        }
    }

    return $output;
}

/**
 * Get a list of years, months or days depending of the type specified.
 *
 * @param string $type Either years, months or days.
 * @param string $date Year, month and day selected from the request.
 * @param bool $in_html Whether the list should be converted to a HTML select options or not.
 * @return array|string Either an array with the expected values, or a HTML code.
 */
function sucuriscan_firewall_dates($type = '', $date = '', $in_html = true)
{
    $options = array();
    $selected = '';
    $pattern = '/^([0-9]{4})\-([0-9]{2})\-([0-9]{2})$/';
    $s_year = '';
    $s_month = '';
    $s_day = '';

    if (@preg_match($pattern, $date, $date_m)) {
        $s_year = $date_m[1];
        $s_month = $date_m[2];
        $s_day = $date_m[3];
    }

    switch ($type) {
        case 'years':
            $selected = $s_year;
            $current_year = (int) date('Y');
            $max_years = 5; /* Maximum number of years to keep the logs. */
            $options = range(($current_year - $max_years), $current_year);
            break;
        case 'months':
            $selected = $s_month;
            $options = array(
                '01' => 'January',
                '02' => 'February',
                '03' => 'March',
                '04' => 'April',
                '05' => 'May',
                '06' => 'June',
                '07' => 'July',
                '08' => 'August',
                '09' => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December',
            );
            break;
        case 'days':
            $options = range(1, 31);
            $selected = $s_day;
            break;
    }

    if ($in_html) {
        $html_options = '';

        foreach ($options as $key => $value) {
            if (is_numeric($value)) {
                $value = str_pad($value, 2, '0', STR_PAD_LEFT);
            }

            if ($type != 'months') {
                $key = $value;
            }

            $selected_tag = ( $key == $selected ) ? 'selected="selected"' : '';
            $html_options .= sprintf('<option value="%s" %s>%s</option>', $key, $selected_tag, $value);
        }

        return $html_options;
    }

    return $options;
}

/**
 * Generate the HTML code for the firewall clear cache panel.
 *
 * @param bool $nonce Identifier of the HTTP request for CSRF protection
 * @return string The parsed-content of the firewall clear cache panel.
 */
function sucuriscan_firewall_clearcache($nonce)
{
    $params = array();

    if ($nonce) {
        // Flush the cache of the site(s) associated with the API key.
        if (SucuriScanRequest::post(':clear_cache') == 1) {
            $response = SucuriScanAPI::clearFirewallCache();

            if ($response) {
                if (isset($response['messages'][0])) {
                    // Clear W3 Total Cache if it is installed.
                    if (function_exists('w3tc_flush_all')) {
                        w3tc_flush_all();
                    }

                    SucuriScanInterface::info($response['messages'][0]);
                } else {
                    SucuriScanInterface::error('Could not clear the cache of your site, try later again.');
                }
            } else {
                SucuriScanInterface::error('Firewall is not enabled on your site, or your API key is invalid.');
            }
        }
    }

    return SucuriScanTemplate::getSection('firewall-clearcache', $params);
}

/**
 * Clear the firewall cache if necessary.
 *
 * Every time a page or post is modified and saved into the database the
 * plugin will send a HTTP request to the firewall API service and except
 * that, if the API key is valid, the cache is reset. Notice that the cache
 * of certain files is going to stay as it is due to the configuration on the
 * edge of the servers.
 *
 * @param int $post_id The post ID.
 */
function sucuriscanFirewallClearCacheSavePost($post_id = 0)
{
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return; /* Prevent double execution of the save_post action */
    }

    SucuriScanAPI::clearFirewallCache(); /* ignore errors */
}

/**
 * Process the requests sent by the form submissions originated in the firewall
 * page, all forms must have a nonce field that will be checked against the one
 * generated in the template render function.
 *
 * @param bool $nonce True if the form submission was validated, false otherwise.
 */
function sucuriscan_firewall_form_submissions($nonce)
{
    if ($nonce) {
        // Add and/or Update the Sucuri WAF API Key (do it before anything else).
        $option_name = ':cloudproxy_apikey';
        $api_key = SucuriScanRequest::post($option_name);

        if ($api_key !== false) {
            $api_key = trim($api_key);

            if (SucuriScanAPI::isValidFirewallKey($api_key)) {
                SucuriScanOption::updateOption($option_name, $api_key);
                SucuriScanInterface::info('Firewall API key saved successfully');
                SucuriScanOption::setRevProxy('enable');
                SucuriScanOption::setAddrHeader('HTTP_X_SUCURI_CLIENTIP');
            } else {
                SucuriScanInterface::error('Invalid firewall API key.');
            }
        }

        // Delete the firewall API key from the plugin.
        if (SucuriScanRequest::post(':delete_wafkey') !== false) {
            SucuriScanOption::deleteOption($option_name);
            SucuriScanInterface::info('Firewall API key removed successfully');
            SucuriScanOption::setRevProxy('disable');
            SucuriScanOption::setAddrHeader('REMOTE_ADDR');
        }
    }
}
