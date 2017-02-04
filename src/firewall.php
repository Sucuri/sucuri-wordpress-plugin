<?php

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
 * @param  string $api_key The CloudProxy API key.
 * @return string          The parsed-content of the firewall settings panel.
 */
function sucuriscan_firewall_settings($api_key = '')
{
    $params = array(
        'Firewall.APIKey' => '',
        'Firewall.APIKeyVisibility' => 'hidden',
        'Firewall.APIKeyFormVisibility' => 'visible',
        'Firewall.SettingsVisibility' => 'hidden',
        'Firewall.SettingOptions' => '',
    );

    if ($api_key && array_key_exists('string', $api_key)) {
        $settings = SucuriScanAPI::getCloudproxySettings($api_key);

        $params['Firewall.APIKeyVisibility'] = 'visible';
        $params['Firewall.APIKeyFormVisibility'] = 'hidden';
        $params['Firewall.APIKey'] = $api_key['string'];

        if ($settings) {
            $counter = 0;
            $params['Firewall.SettingsVisibility'] = 'visible';
            $settings = sucuriscan_explain_firewall_settings($settings);

            foreach ($settings as $option_name => $option_value) {
                $css_class = ($counter % 2 === 0) ? 'alternate' : '';
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
                        'Firewall.OptionCssClass' => $css_class,
                        'Firewall.OptionName' => $option_title,
                        'Firewall.OptionValue' => $option_value,
                    )
                );
                $counter++;
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
 * @param  array $settings A hash with the settings of a CloudProxy account.
 * @return array           The explained version of the CloudProxy settings.
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

function sucuriscan_firewall_auditlogs_ajax()
{
    if (SucuriScanRequest::post('form_action') == 'get_audit_logs') {
        $response = '';
        $api_key = SucuriScanAPI::getCloudproxyKey();

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
            SucuriScanInterface::error('CloudProxy API Key was not found.');
        }

        print($response);
        exit(0);
    }
}

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
        $counter = 0;

        foreach ($entries as $entry) {
            if (array_key_exists('is_usable', $entry) && $entry['is_usable']) {
                $data_set = array();
                $data_set['AccessLog.CssClass'] = ($counter % 2 == 0) ? '' : 'alternate';

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
                $counter++;
            }
        }
    }

    return $output;
}

/**
 * Get a list of years, months or days depending of the type specified.
 *
 * @param  string  $type    Either years, months or days.
 * @param  string  $date    Year, month and day selected from the request.
 * @param  boolean $in_html Whether the list should be converted to a HTML select options or not.
 * @return array            Either an array with the expected values, or a HTML code.
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
                $value = str_pad($value, 2, 0, STR_PAD_LEFT);
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
 * @param  string $nonce Identifier of the HTTP request for CSRF protection
 * @return string        The parsed-content of the firewall clear cache panel.
 */
function sucuriscan_firewall_clearcache($nonce)
{
    $params = array();

    if ($nonce) {
        // Flush the cache of the site(s) associated with the API key.
        if (SucuriScanRequest::post(':clear_cache') == 1) {
            $response = SucuriScanAPI::clearCloudproxyCache();

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
                SucuriScanInterface::error('CloudProxy is not enabled on your site, or your API key is invalid.');
            }
        }
    }

    return SucuriScanTemplate::getSection('firewall-clearcache', $params);
}

/**
 * CloudProxy firewall page.
 *
 * It checks whether the WordPress core files are the original ones, and the state
 * of the themes and plugins reporting the availability of updates. It also checks
 * the user accounts under the administrator group.
 *
 * @return void
 */
function sucuriscan_firewall_page()
{
    SucuriScanInterface::check_permissions();

    // Process all form submissions.
    $nonce = SucuriScanInterface::check_nonce();
    sucuriscan_firewall_form_submissions($nonce);

    // Get the dynamic values for the template variables.
    $api_key = SucuriScanAPI::getCloudproxyKey();

    // Page pseudo-variables initialization.
    $params = array(
        'PageTitle' => 'Firewall WAF',
        'Firewall.Settings' => sucuriscan_firewall_settings($api_key),
        'Firewall.AuditLogs' => sucuriscan_firewall_auditlogs($api_key),
        'Firewall.ClearCache' => sucuriscan_firewall_clearcache($nonce),
    );

    echo SucuriScanTemplate::getTemplate('firewall', $params);
}

/**
 * Handle an Ajax request for this specific page.
 *
 * @return mixed.
 */
function sucuriscan_firewall_ajax()
{
    SucuriScanInterface::check_permissions();

    if (SucuriScanInterface::check_nonce()) {
        sucuriscan_firewall_auditlogs_ajax();
    }

    wp_die();
}

/**
 * Process the requests sent by the form submissions originated in the firewall
 * page, all forms must have a nonce field that will be checked against the one
 * generated in the template render function.
 *
 * @return void
 */
function sucuriscan_firewall_form_submissions($nonce)
{
    if ($nonce) {
        // Add and/or Update the Sucuri WAF API Key (do it before anything else).
        $option_name = ':cloudproxy_apikey';
        $api_key = SucuriScanRequest::post($option_name);

        if ($api_key !== false) {
            $api_key = trim($api_key);

            if (SucuriScanAPI::isValidCloudproxyKey($api_key)) {
                SucuriScanOption::update_option($option_name, $api_key);
                SucuriScanInterface::info('CloudProxy API key saved successfully');
                SucuriScanOption::setRevProxy('enable');
                SucuriScanOption::setAddrHeader('HTTP_X_SUCURI_CLIENTIP');
            } else {
                SucuriScanInterface::error('Invalid CloudProxy API key.');
            }
        }

        // Delete CloudProxy API key from the plugin.
        if (SucuriScanRequest::post(':delete_wafkey') !== false) {
            SucuriScanOption::delete_option($option_name);
            SucuriScanInterface::info('CloudProxy API key removed successfully');
            SucuriScanOption::setRevProxy('disable');
            SucuriScanOption::setAddrHeader('REMOTE_ADDR');
        }
    }
}
