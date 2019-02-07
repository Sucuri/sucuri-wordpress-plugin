<?php

/**
 * Code related to the firewall.lib.php interface.
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
 * Defines methods to interact with Sucuri Firewall's API service.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanFirewall extends SucuriScanAPI
{
    /**
     * Check whether the firewall API key is valid or not.
     *
     * @param  string $api_key      The firewall API key.
     * @param  bool   $return_match Whether the parts of the API key must be returned or not.
     * @return array|bool           True if the API key specified is valid, false otherwise.
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
     * @param  string $method HTTP method that will be used to send the request.
     * @param  array  $params HTTP request parameters (key-value array).
     * @return array|bool     HTTP response object.
     */
    public static function apiCallFirewall($method = 'GET', $params = array())
    {
        $send_request = (bool) (isset($params['k']) && isset($params['s']));

        if (!$send_request) {
            $api_key = self::getKey();

            if ($api_key) {
                $send_request = true;
                $params['k'] = $api_key['k'];
                $params['s'] = $api_key['s'];
            }
        }

        if ($send_request) {
            unset($params['string']);
            $params[SUCURISCAN_CLOUDPROXY_API_VERSION] = 1;
            return self::apiCall(SUCURISCAN_CLOUDPROXY_API, $method, $params);
        }

        return false;
    }

    /**
     * Retrieve the public settings of the account associated with the API keys
     * registered by the administrator of the site. This method will send a HTTP
     * request to the remote API service and process its response, when successful
     * it will return an array/object containing the public attributes of the site.
     *
     * @param  array|bool $api_key The firewall API key.
     * @return array|bool          A hash with the settings of a firewall account.
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
        );

        if (SucuriScanInterface::checkNonce()) {
            // Add and/or Update the Sucuri WAF API Key (do it before anything else).
            $option_name = ':cloudproxy_apikey';
            $api_key = SucuriScanRequest::post($option_name);

            if ($api_key !== false) {
                $api_key = trim($api_key);

                if (self::isValidKey($api_key)) {
                    SucuriScanOption::updateOption($option_name, $api_key);
                    SucuriScanInterface::info(__('Firewall API key was successfully saved', 'sucuri-scanner'));
                    SucuriScanOption::setRevProxy('enable');
                    SucuriScanOption::setAddrHeader('HTTP_X_SUCURI_CLIENTIP');
                } else {
                    SucuriScanInterface::error('Invalid firewall API key');
                }
            }

            // Delete the firewall API key from the plugin.
            if (SucuriScanRequest::post(':delete_wafkey') !== false) {
                SucuriScanOption::deleteOption($option_name);
                SucuriScanInterface::info(__('Firewall API key was successfully removed', 'sucuri-scanner'));
                SucuriScanOption::setRevProxy('disable');
                SucuriScanOption::setAddrHeader('REMOTE_ADDR');
            }
        }

        $api_key = self::getKey();

        if ($api_key && array_key_exists('string', $api_key)) {
            $params['Firewall.APIKeyVisibility'] = 'visible';
            $params['Firewall.APIKeyFormVisibility'] = 'hidden';
            $params['Firewall.APIKey'] = $api_key['string'];
        }

        return SucuriScanTemplate::getSection('firewall-settings', $params);
    }

    /**
     * Converts the value of some of the firewall settings into a human-readable
     * text, for example changing numbers or variable names into a more explicit
     * text so the administrator can understand the meaning of these settings.
     *
     * @param  array $settings A hash with the settings of a firewall account.
     * @return array           The explained version of the firewall settings.
     */
    public static function settingsExplanation($settings = array())
    {
        if (!is_array($settings)) {
            return array();
        }

        $cache_modes = array(
            'docache' => __('enabled (recommended)', 'sucuri-scanner'),
            'sitecache' => __('site caching (using your site headers)', 'sucuri-scanner'),
            'nocache' => __('minimal (only for a few minutes)', 'sucuri-scanner'),
            'nocacheatall' => __('caching disabled (use with caution)', 'sucuri-scanner'),
        );

        foreach ($settings as $keyname => $value) {
            if ($keyname == 'proxy_active') {
                $settings[$keyname] = ($value === 1) ? 'active' : 'not active';
                continue;
            }

            if ($keyname == 'cache_mode') {
                if (array_key_exists($value, $cache_modes)) {
                    $settings[$keyname] = $cache_modes[$value];
                } else {
                    $settings[$keyname] = 'unknown';
                }
                continue;
            }
        }

        return $settings;
    }

    /**
     * Returns the public firewall settings.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public static function getSettingsAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'firewall_settings') {
            return;
        }

        $response = array();
        $response['ok'] = false;
        $api_key = self::getKey();

        ob_start();
        $settings = self::settings($api_key);
        $error = ob_get_clean();

        if (!$settings) {
            if (empty($error)) {
                ob_start();
                SucuriScanInterface::error(__('Firewall API key was not found.', 'sucuri-scanner'));
                $response['error'] = ob_get_clean();
            } else {
                $response['error'] = $error;
            }

            wp_send_json($response, 200);
        }

        $response['ok'] = true;
        $response['settings'] = self::settingsExplanation($settings);
        unset($response['settings']['whitelist_list']);
        unset($response['settings']['blacklist_list']);

        wp_send_json($response, 200);
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
     * @param  array|string $api_key The firewall API key.
     * @param  string       $date    Retrieve the data from this date.
     * @param  string       $query   Filter the data to match this query.
     * @param  int          $limit   Retrieve this maximum of data.
     * @param  int          $offset  Retrieve the data from this point.
     * @return array|bool            Objects with details of each blocked request.
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
        $date = SucuriScan::datetime(strtotime('-1 day'), 'Y-m-d');

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
     *
     * @return void
     */
    public static function auditlogsAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'get_firewall_logs') {
            return;
        }

        $response = '';
        $api_key = self::getKey();

        if (!$api_key) {
            ob_start();
            SucuriScanInterface::error(__('Firewall API key was not found.', 'sucuri-scanner'));
            $response = ob_get_clean();
            wp_send_json($response, 200);
        }

        $query = SucuriScanRequest::post(':query');
        $month = SucuriScanRequest::post(':month');
        $year = SucuriScanRequest::post(':year');
        $day = SucuriScanRequest::post(':day');
        $limit = 50;
        $offset = 1;

        if ($year && $month && $day) {
            $date = sprintf('%s-%s-%s', $year, $month, $day);
        } else {
            $date = SucuriScan::datetime(null, 'Y-m-d');
        }

        ob_start();
        $auditlogs = self::auditlogs(
            $api_key,
            $date, /* Retrieve the data from this date. */
            $query, /* Filter the data to match this query. */
            $limit, /* Retrieve this maximum of data. */
            $offset /* Retrieve the data from this point. */
        );
        $error = ob_get_clean();

        if (!$auditlogs && !empty($error)) {
            wp_send_json($error, 200);
        }

        if ($auditlogs && array_key_exists('total_lines', $auditlogs)) {
            $response = self::auditlogsEntries($auditlogs['access_logs']);

            if (empty($response)) {
                $response = '<tr><td>' . __('no data available.', 'sucuri-scanner') . '</td></tr>';
            }
        }

        wp_send_json($response, 200);
    }

    /**
     * Returns the security logs from the firewall in HTML.
     *
     * @param  array $entries Security logs retrieved from the Firewall API.
     * @return string         HTML with the information from the logs.
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
                        $data_set[$keyname] = __('Anonymous', 'sucuri-scanner');
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
     * @param  string $type    Either years, months or days.
     * @param  string $date    Year, month and day selected from the request.
     * @param  bool   $in_html Whether the list should be converted to a HTML select options or not.
     * @return array|string    Either an array with the expected values, or a HTML code.
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
                $current_year = (int) SucuriScan::datetime(null, 'Y');
                $max_years = 5; /* Maximum number of years to keep the logs. */
                $options = range(($current_year - $max_years), $current_year);
                break;

            case 'months':
                $selected = $s_month;
                $options = array(
                    '01' => __('January', 'sucuri-scanner'),
                    '02' => __('February', 'sucuri-scanner'),
                    '03' => __('March', 'sucuri-scanner'),
                    '04' => __('April', 'sucuri-scanner'),
                    '05' => __('May', 'sucuri-scanner'),
                    '06' => __('June', 'sucuri-scanner'),
                    '07' => __('July', 'sucuri-scanner'),
                    '08' => __('August', 'sucuri-scanner'),
                    '09' => __('September', 'sucuri-scanner'),
                    '10' => __('October', 'sucuri-scanner'),
                    '11' => __('November', 'sucuri-scanner'),
                    '12' => __('December', 'sucuri-scanner'),
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
     * Generate the HTML code for the firewall IP access panel.
     *
     * @return string The parsed-content of the firewall IP access panel.
     */
    public static function ipAccessPage()
    {
        $params = array();

        return SucuriScanTemplate::getSection('firewall-ipaccess', $params);
    }

    /**
     * Returns the whitelisted and blacklisted IP addresses.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public static function ipAccessAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'firewall_ipaccess') {
            return;
        }

        $response = array();
        $response['ok'] = false;
        $api_key = self::getKey();

        ob_start();
        $settings = self::settings($api_key);
        $error = ob_get_clean();

        if (!$settings) {
            if (empty($error)) {
                ob_start();
                SucuriScanInterface::error(__('Firewall API key was not found.', 'sucuri-scanner'));
                $response['error'] = ob_get_clean();
            } else {
                $response['error'] = $error;
            }

            wp_send_json($response, 200);
        }

        $response['ok'] = true;
        $response['whitelist'] = $settings['whitelist_list'];
        $response['blacklist'] = $settings['blacklist_list'];

        wp_send_json($response, 200);
    }

    /**
     * Blacklists an IP address.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public static function blacklistAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'firewall_blacklist') {
            return;
        }

        $response = array();
        $response['ok'] = false;
        $params = self::getKey();

        if (!$params) {
            ob_start();
            SucuriScanInterface::error(__('Firewall API key was not found.', 'sucuri-scanner'));
            $response['msg'] = ob_get_clean();
            wp_send_json($response, 200);
        }

        $params['a'] = 'blacklist_ip';
        $params['ip'] = SucuriScanRequest::post('ip');
        $out = self::apiCallFirewall('POST', $params);
        $response['msg'] = __('Failure connecting to the API service; try again.', 'sucuri-scanner');

        if ($out && !empty($out['messages'])) {
            $response['ok'] = (bool) ($out['status'] == 1);
            $response['msg'] = implode(";\x20", $out['messages']);

            if ($out['status'] == 1) {
                SucuriScanEvent::reportInfoEvent(sprintf(__('IP has been blacklisted: %s', 'sucuri-scanner'), $params['ip']));
            }
        }

        wp_send_json($response, 200);
    }

    /**
     * Deletes an IP address from the blacklist.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public static function deblacklistAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'firewall_deblacklist') {
            return;
        }

        $response = array();
        $params = self::getKey();

        if (!$params) {
            ob_start();
            $response['ok'] = false;
            SucuriScanInterface::error(__('Firewall API key was not found.', 'sucuri-scanner'));
            $response['error'] = ob_get_clean();
            wp_send_json($response, 200);
        }

        $params['a'] = 'delete_blacklist_ip';
        $params['ip'] = SucuriScanRequest::post('ip');
        $out = self::apiCallFirewall('POST', $params);

        $response['ok'] = (bool) ($out['status'] == 1);
        $response['msg'] = implode(";\x20", $out['messages']);

        if ($out['status'] == 1) {
            SucuriScanEvent::reportInfoEvent(sprintf(__('IP has been unblacklisted: %s', 'sucuri-scanner'), $params['ip']));
        }

        wp_send_json($response, 200);
    }

    /**
     * Flush the cache of the site(s) associated with the API key.
     *
     * @param  array|bool $api_key The firewall API key.
     * @return string|bool         Message explaining the result of the operation.
     */
    public static function clearCache($api_key = false)
    {
        $params = array('a' => 'clear_cache');

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

        $params['FirewallAutoClearCache'] = 'data-status="disabled"';

        if (self::shouldAutoClearCache()) {
            $params['FirewallAutoClearCache'] = 'checked="checked"';
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
     * @return void
     */
    public static function clearCacheHook()
    {
        if (self::shouldAutoClearCache()) {
            ob_start();
            self::clearCache();
            $error = ob_get_clean();
        }
    }

    /**
     * Requests a cache flush to the firewall service.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public static function clearCacheAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'firewall_clear_cache') {
            return;
        }

        ob_start();
        SucuriScanInterface::error(__('Firewall API key was not found.', 'sucuri-scanner'));
        $response = ob_get_clean();
        $api_key = self::getKey();

        if ($api_key) {
            $res = self::clearCache($api_key);

            if (is_array($res) && isset($res['messages'])) {
                $response = sprintf(
                    '<div class="sucuriscan-inline-alert-%s"><p>%s</p></div>',
                    ($res['status'] == 1) ? 'success' : 'error',
                    implode('<br>', $res['messages'])
                );
            }
        }

        wp_send_json($response, 200);
    }

    /**
     * Configures the status of the automatic cache flush.
     *
     * @codeCoverageIgnore
     *
     * @return void
     */
    public static function clearAutoCacheAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'firewall_auto_clear_cache') {
            return;
        }

        $response = array();

        if (SucuriScanRequest::post('auto_clear_cache') === 'enable') {
            $response['ok'] = SucuriScanOption::updateOption(':auto_clear_cache', 'enabled');
            $response['status'] = 'enabled';
        } else {
            $response['ok'] = SucuriScanOption::deleteOption(':auto_clear_cache');
            $response['status'] = 'disabled';
        }

        wp_send_json($response, 200);
    }

    /**
     * Returns true if the plugin should flush the firewall cache.
     *
     * @return bool True if the plugin should flush the firewall cache.
     */
    private static function shouldAutoClearCache()
    {
        return (bool) (
            defined('SUCURI_CLEAR_CACHE_ON_PUBLISH')
            || SucuriScanOption::isEnabled(':auto_clear_cache')
        );
    }
}
