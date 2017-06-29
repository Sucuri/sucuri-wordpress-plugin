<?php

/**
 * Code related to the firewall.lib.php interface.
 *
 * @package Sucuri Security
 * @subpackage firewall.lib.php
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
 * Defines methods to interact with Sucuri Firewall's API service.
 */
class SucuriScanFirewall extends SucuriScanAPI
{
    /**
     * Check whether the firewall API key is valid or not.
     *
     * @param string $api_key The firewall API key.
     * @param bool $return_match Whether the parts of the API key must be returned or not.
     * @return array|bool True if the API key specified is valid, false otherwise.
     */
    public static function isValidKey($api_key = '', $return_match = false)
    {
        $pattern = '/^([a-z0-9]{32})\/([a-z0-9]{32})$/';

        if ($api_key && preg_match($pattern, $api_key, $match)) {
            return $return_match ? $match : true;
        }

        return false;
    }

    /**
     * Check and return the API key for the plugin.
     *
     * In this plugin the key is a pair of two strings concatenated by a single
     * slash, the first part of it is in fact the key and the second part is the
     * unique identifier of the site in the remote server.
     *
     * @return array|bool false if the key is invalid or not present, an array otherwise.
     */
    public static function getKey()
    {
        $option_name = ':cloudproxy_apikey';
        $api_key = self::getOption($option_name);

        // Check the validity of the API key.
        $match = self::isValidKey($api_key, true);

        if ($match) {
            return array(
                'string' => $match[1].'/'.$match[2],
                'k' => $match[1],
                's' => $match[2],
            );
        }

        return false;
    }

    /**
     * Call an action from the remote API interface of our firewall service.
     *
     * @param string $method HTTP method that will be used to send the request.
     * @param array $params Parameters for the request defined in an associative array of key-value.
     * @return array|bool Response object after the HTTP request is executed.
     */
    public static function apiCallFirewall($method = 'GET', $params = array())
    {
        $send_request = false;

        if (isset($params['k']) && isset($params['s'])) {
            $send_request = true;
        } else {
            $api_key = self::getKey();

            if ($api_key) {
                $send_request = true;
                $params['k'] = $api_key['k'];
                $params['s'] = $api_key['s'];
            }
        }

        if ($send_request) {
            $url = SUCURISCAN_CLOUDPROXY_API;
            $params[ SUCURISCAN_CLOUDPROXY_API_VERSION ] = 1;
            unset($params['string']);

            return self::apiCall($url, $method, $params);
        }

        return false;
    }

    /**
     * Retrieve the public settings of the account associated with the API keys
     * registered by the administrator of the site. This method will send a HTTP
     * request to the remote API service and process its response, when successful
     * it will return an array/object containing the public attributes of the site.
     *
     * @param array|bool $api_key The firewall API key.
     * @return array|bool A hash with the settings of a firewall account.
     */
    public static function settings($api_key = false)
    {
        $params = array('a' => 'show_settings');

        if ($api_key) {
            $params = array_merge($params, $api_key);
        }

        $response = self::apiCallFirewall('GET', $params);

        return self::handleResponse($response) ? $response['output'] : false;
    }

    /**
     * Generate the HTML code for the firewall settings panel.
     *
     * @codeCoverageIgnore
     *
     * @return string The parsed-content of the firewall settings panel.
     */
    public static function settingsPage()
    {
        $params = array(
            'Firewall.APIKey' => '',
            'Firewall.APIKeyVisibility' => 'hidden',
            'Firewall.APIKeyFormVisibility' => 'visible',
            'Firewall.SettingsVisibility' => 'hidden',
            'Firewall.SettingOptions' => '',
        );

        if (SucuriScanInterface::checkNonce()) {
            // Add and/or Update the Sucuri WAF API Key (do it before anything else).
            $option_name = ':cloudproxy_apikey';
            $api_key = SucuriScanRequest::post($option_name);

            if ($api_key !== false) {
                $api_key = trim($api_key);

                if (self::isValidKey($api_key)) {
                    SucuriScanOption::updateOption($option_name, $api_key);
                    SucuriScanInterface::info(__('FirewallAPIKeySet', SUCURISCAN_TEXTDOMAIN));
                    SucuriScanOption::setRevProxy('enable');
                    SucuriScanOption::setAddrHeader('HTTP_X_SUCURI_CLIENTIP');
                } else {
                    SucuriScanInterface::error(__('FirewallAPIKeyInvalid', SUCURISCAN_TEXTDOMAIN));
                }
            }

            // Delete the firewall API key from the plugin.
            if (SucuriScanRequest::post(':delete_wafkey') !== false) {
                SucuriScanOption::deleteOption($option_name);
                SucuriScanInterface::info(__('FirewallAPIKeyUnset', SUCURISCAN_TEXTDOMAIN));
                SucuriScanOption::setRevProxy('disable');
                SucuriScanOption::setAddrHeader('REMOTE_ADDR');
            }
        }

        $api_key = self::getKey(); /* extract API key information */

        if ($api_key && array_key_exists('string', $api_key)) {
            $settings = self::settings($api_key);

            $params['Firewall.APIKeyVisibility'] = 'visible';
            $params['Firewall.APIKeyFormVisibility'] = 'hidden';
            $params['Firewall.APIKey'] = $api_key['string'];

            if ($settings) {
                $params['Firewall.SettingsVisibility'] = 'visible';
                $settings = self::settingsExplanation($settings);

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
                            $html_list .= '<li>(' . __('NoData', SUCURISCAN_TEXTDOMAIN) . ')</li>';
                        }

                        $html_list .= '</ul>';
                        $option_value = $html_list;
                    } else {
                        $option_value = SucuriScan::escape($option_value);
                    }

                    // Parse the snippet template and replace the pseudo-variables.
                    $params['Firewall.SettingOptions']
                    .= SucuriScanTemplate::getSnippet('firewall-settings', array(
                        'Firewall.OptionName' => $option_title,
                        'Firewall.OptionValue' => $option_value,
                    ));
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
    public static function settingsExplanation($settings = array())
    {
        $cache_modes = array(
            'docache' => __('FirewallDoCache', SUCURISCAN_TEXTDOMAIN),
            'sitecache' => __('FirewallSiteCache', SUCURISCAN_TEXTDOMAIN),
            'nocache' => __('FirewallNoCache', SUCURISCAN_TEXTDOMAIN),
            'nocacheatall' => __('FirewallNoCacheAtAll', SUCURISCAN_TEXTDOMAIN),
        );

        // TODO: Prefer Array over stdClass, modify the API library.
        $settings = @json_decode(json_encode($settings), true);

        foreach ($settings as $keyname => $value) {
            if ($keyname == 'proxy_active') {
                $settings[$keyname] = ($value === 1)
                ? __('Active', SUCURISCAN_TEXTDOMAIN)
                : __('NotActive', SUCURISCAN_TEXTDOMAIN);
            } elseif ($keyname == 'cache_mode') {
                if (array_key_exists($value, $cache_modes)) {
                    $settings[$keyname] = $cache_modes[$value];
                } else {
                    $settings[$keyname] = __('Unknown', SUCURISCAN_TEXTDOMAIN);
                }
            }
        }

        return $settings;
    }

    /**
     * Retrieve the audit logs of the account associated with the API keys
     * registered b the administrator of the site. This method will send a HTTP
     * request to the remote API service and process its response, when successful
     * it will return an array/object containing a list of requests blocked by our
     * firewall.
     *
     * By default the logs that will be retrieved are from today, if you need to see
     * the logs of previous days you will need to add a new parameter to the request
     * URL named "date" with format yyyy-mm-dd.
     *
     * @param array|string $api_key The firewall API key.
     * @param string $date Retrieve the data from this date.
     * @param string $query Filter the data to match this query.
     * @param int $limit Retrieve this maximum of data.
     * @param int $offset Retrieve the data from this point.
     * @return array|bool Objects with details of each blocked request.
     */
    public static function auditlogs($api_key, $date = '', $query = '', $limit = 10, $offset = 0)
    {
        $params = array(
            'a' => 'audit_trails',
            'date' => $date,
            'query' => $query,
            'limit' => $limit,
            'offset' => $offset,
        );

        if (is_array($api_key)) {
            $params = array_merge($params, $api_key);
        }

        $response = self::apiCallFirewall('GET', $params);

        return self::handleResponse($response) ? $response['output'] : false;
    }

    /**
     * Generate the HTML code for the firewall logs panel.
     *
     * @return string The parsed-content of the firewall logs panel.
     */
    public static function auditlogsPage()
    {
        $params = array();

        /* logs are available after 24 hours */
        $date = date('Y-m-d', strtotime('-1 day'));

        $params['AuditLogs.DateYears'] = self::dates('years', $date);
        $params['AuditLogs.DateMonths'] = self::dates('months', $date);
        $params['AuditLogs.DateDays'] = self::dates('days', $date);

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
     * @codeCoverageIgnore
     */
    public static function auditlogsAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'get_firewall_logs') {
            return;
        }

        $response = ''; /* HTML code response */

        if ($api_key = self::getKey()) {
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

            $auditlogs = self::auditlogs(
                $api_key,
                $date, /* Retrieve the data from this date. */
                $query, /* Filter the data to match this query. */
                $limit, /* Retrieve this maximum of data. */
                $offset /* Retrieve the data from this point. */
            );

            if ($auditlogs && array_key_exists('total_lines', $auditlogs)) {
                $response = self::auditlogsEntries($auditlogs['access_logs']);

                if (empty($response)) {
                    $response = '<tr><td>' . __('NoData', SUCURISCAN_TEXTDOMAIN) . '.</td></tr>';
                }
            }
        } else {
            ob_start();
            SucuriScanInterface::error(__('FirewallAPIKeyMissing', SUCURISCAN_TEXTDOMAIN));
            $response = ob_get_clean();
        }

        wp_send_json($response, 200);
    }

    /**
     * Returns the security logs from the firewall in HTML.
     *
     * @param array $entries Security logs retrieved from the Firewall API.
     * @return string HTML with the information from the logs.
     */
    public static function auditlogsEntries($entries = array())
    {
        if (!is_array($entries) || empty($entries)) {
            return ''; /* empty response */
        }

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

        foreach ($entries as $entry) {
            if (array_key_exists('is_usable', $entry) && $entry['is_usable']) {
                $data_set = array();

                foreach ($attributes as $attr) {
                    /* generate variable name for the template pseudo-tags */
                    $keyname = str_replace('_', "\x20", $attr);
                    $keyname = ucwords($keyname);
                    $keyname = str_replace("\x20", '', $keyname);
                    $keyname = 'AccessLog.' . $keyname;

                    /* assign and escape variable value before rendering */
                    $data_set[$keyname] = isset($entry[$attr]) ? $entry[$attr] : '';

                    /* special cases to convert value to readable data */
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
    public static function dates($type = '', $date = '', $in_html = true)
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
                    '01' => __('January', SUCURISCAN_TEXTDOMAIN),
                    '02' => __('February', SUCURISCAN_TEXTDOMAIN),
                    '03' => __('March', SUCURISCAN_TEXTDOMAIN),
                    '04' => __('April', SUCURISCAN_TEXTDOMAIN),
                    '05' => __('May', SUCURISCAN_TEXTDOMAIN),
                    '06' => __('June', SUCURISCAN_TEXTDOMAIN),
                    '07' => __('July', SUCURISCAN_TEXTDOMAIN),
                    '08' => __('August', SUCURISCAN_TEXTDOMAIN),
                    '09' => __('September', SUCURISCAN_TEXTDOMAIN),
                    '10' => __('October', SUCURISCAN_TEXTDOMAIN),
                    '11' => __('November', SUCURISCAN_TEXTDOMAIN),
                    '12' => __('December', SUCURISCAN_TEXTDOMAIN),
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
     * Flush the cache of the site(s) associated with the API key.
     *
     * @param array|bool $api_key The firewall API key.
     * @return string|bool Message explaining the result of the operation.
     */
    public static function clearCache($api_key = false)
    {
        $params = array( 'a' => 'clear_cache' );

        if (is_array($api_key)) {
            $params = array_merge($params, $api_key);
        }

        $response = self::apiCallFirewall('GET', $params);

        return self::handleResponse($response) ? $response : false;
    }

    /**
     * Generate the HTML code for the firewall clear cache panel.
     *
     * @codeCoverageIgnore
     *
     * @return string The parsed-content of the firewall clear cache panel.
     */
    public static function clearCachePage()
    {
        $params = array();

        /* flush the cache of the site(s) associated with the API key. */
        if (SucuriScanInterface::checkNonce() && SucuriScanRequest::post(':clear_cache')) {
            $response = self::clearCache();

            if (!$response) {
                SucuriScanInterface::error(__('FirewallNotEnabled', SUCURISCAN_TEXTDOMAIN));
            } elseif (!isset($response['messages'][0])) {
                SucuriScanInterface::error(__('FirewallClearCacheFailure', SUCURISCAN_TEXTDOMAIN));
            } else {
                // Clear W3 Total Cache if it is installed.
                if (function_exists('w3tc_flush_all')) {
                    w3tc_flush_all();
                }

                SucuriScanInterface::info($response['messages'][0]);
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
    public static function clearCacheHook($post_id = 0)
    {
        /* prevent double execution of the save_post action */
        if (!wp_is_post_revision($post_id) && !wp_is_post_autosave($post_id)) {
            self::clearCache(); /* ignore HTTP request errors */
        }
    }
}
