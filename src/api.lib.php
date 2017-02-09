<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Plugin API library.
 *
 * When used in the context of web development, an API is typically defined as a
 * set of Hypertext Transfer Protocol (HTTP) request messages, along with a
 * definition of the structure of response messages, which is usually in an
 * Extensible Markup Language (XML) or JavaScript Object Notation (JSON) format.
 * While "web API" historically has been virtually synonymous for web service,
 * the recent trend (so-called Web 2.0) has been moving away from Simple Object
 * Access Protocol (SOAP) based web services and service-oriented architecture
 * (SOA) towards more direct representational state transfer (REST) style web
 * resources and resource-oriented architecture (ROA). Part of this trend is
 * related to the Semantic Web movement toward Resource Description Framework
 * (RDF), a concept to promote web-based ontology engineering technologies. Web
 * APIs allow the combination of multiple APIs into new applications known as
 * mashups.
 *
 * @see https://en.wikipedia.org/wiki/Application_programming_interface#Web_APIs
 */
class SucuriScanAPI extends SucuriScanOption
{
    /**
     * Check whether the SSL certificates will be verified while executing a HTTP
     * request or not. This is only for customization of the administrator, in fact
     * not verifying the SSL certificates can lead to a "Man in the Middle" attack.
     *
     * @return boolean Whether the SSL certs will be verified while sending a request.
     */
    public static function verifySslCert()
    {
        return (self::getOption(':verify_ssl_cert') === 'true');
    }

    /**
     * Seconds before consider a HTTP request as timeout.
     *
     * As for the 01/Jan/2016 if the number of seconds before a timeout is greater
     * than sixty (which is one minute) the function will reset the option to its
     * default value to keep the latency of the HTTP requests in a minimum to
     * minimize the interruptions in the admins workflow. The normal connection
     * timeout should be in the range of ten seconds, or fifteen if the DNS lookups
     * are slow.
     *
     * @return integer Seconds to consider a HTTP request timeout.
     */
    public static function requestTimeout()
    {
        $timeout = (int) self::getOption(':request_timeout');

        if ($timeout > SUCURISCAN_MAX_REQUEST_TIMEOUT) {
            self::deleteOption(':request_timeout');

            return self::requestTimeout();
        }

        return $timeout;
    }

    /**
     * Generate an user-agent for the HTTP requests.
     *
     * @return string An user-agent for the HTTP requests.
     */
    private static function userAgent()
    {
        return sprintf(
            'WordPress/%s; %s',
            self::siteVersion(),
            self::getDomain()
        );
    }

    /**
     * Alternative to the built-in PHP function http_build_query.
     *
     * Some PHP installations with different encoding or with different language
     * (German for example) might produce an unwanted behavior when building an
     * URL, because of this we decided to write our own URL query builder to
     * keep control of the output.
     *
     * @param  array  $params May be an array or object containing properties.
     * @return string         Returns a URL-encoded string.
     */
    private static function buildQuery($params = array())
    {
        $trail = '';

        foreach ($params as $param => $value) {
            $value = urlencode($value);
            $trail .= sprintf('&%s=%s', $param, $value);
        }

        return substr($trail, 1);
    }

    private static function canCurlFollowRedirection()
    {
        $safe_mode = ini_get('safe_mode');
        $open_basedir = ini_get('open_basedir');

        if ($safe_mode === '1' || $safe_mode === 'On') {
            return false;
        }

        if (!empty($open_basedir)) {
            return false;
        }

        return true;
    }

    /**
     * Assign the communication protocol.
     *
     * @see https://developer.wordpress.org/reference/functions/wp_http_supports/
     * @see https://developer.wordpress.org/reference/functions/set_url_scheme/
     *
     * @param  string $url      Valid URL with or without protocol
     * @param  string $protocol Optional protocol, we will get it from the config.
     * @return string           Full URL with the proper protocol.
     */
    public static function apiUrlProtocol($url = '', $protocol = false)
    {
        $pattern = 'sucuri://'; /* Placeholder for HTTP protocol. */

        if (strpos($url, $pattern) === 0) {
            if (!$protocol) {
                $protocol = SucuriScanOption::getOption(':api_protocol');
            }

            $protocol = ($protocol === 'https') ? 'https' : 'http';
            $url = str_replace($pattern, '', $url);
            $url = sprintf('%s://%s', $protocol, $url);
        }

        return $url;
    }

    /**
     * Affected URLs by API protocol setting.
     *
     * These URLs are the ones that will be modified when the admin decides to
     * enable or disable the API communication protocol. If this option is enabled
     * these URLs will be queried using HTTPS and HTTP otherwise. Find an updated
     * list of the affected URLs using the Grep command like this:
     *
     * Note: The string that identifies each URL is a descriptive unique string used
     * to differentiate and easily select the URLs from the list. Make sure that
     * they are different among them all.
     *
     * @see    grep -n 'sucuri://' sucuri.php
     * @return array API URLs affected by the HTTP protocol setting.
     */
    public static function ambiguousApiUrls()
    {
        return array(
            'sucuriwp' => 'sucuri://wordpress.sucuri.net/api/',
            'cproxywp' => 'sucuri://waf.sucuri.net/api',
            'sitechck' => 'sucuri://sitecheck.sucuri.net/',
            'wpssalts' => 'sucuri://api.wordpress.org/secret-key/1.1/salt/',
            'wphashes' => 'sucuri://api.wordpress.org/core/checksums/1.0/',
            'wpplugin' => 'sucuri://wordpress.org/plugins/PLUGIN/',
            'plugindt' => 'sucuri://api.wordpress.org/plugins/info/1.0/PLUGIN.json',
            'wpvfpath' => 'sucuri://core.svn.wordpress.org/tags/VERSION/FILEPATH',
        );
    }

    /**
     * Send test HTTP request to the API URLs.
     *
     * @param  string $unique Unique API URL selector.
     * @return object         WordPress HTTP request response.
     */
    public static function debugApiCall($unique = null)
    {
        $urls = self::ambiguousApiUrls();

        if (array_key_exists($unique, $urls)) {
            $params = array();
            $url = self::apiUrlProtocol($urls[$unique]);

            if ($unique === 'sitechck') {
                $response = self::getSitecheckResults('sucuri.net', false);
            } else {
                if ($unique === 'cproxywp') {
                    $params['v2'] = 'true';
                    $params['a'] = 'test';
                } elseif ($unique === 'wpplugin') {
                    $url = str_replace('/PLUGIN/', '/sucuri-scanner/', $url);
                } elseif ($unique === 'plugindt') {
                    $url = str_replace('/PLUGIN.json', '/sucuri-scanner.json', $url);
                } elseif ($unique === 'wpvfpath') {
                    $fpath = sprintf('/%s/wp-load.php', SucuriScan::siteVersion());
                    $url = str_replace('/VERSION/FILEPATH', $fpath, $url);
                }

                $response = self::apiCall($url, 'GET', $params);
            }

            if ($response) {
                if ($unique === 'sucuriwp'
                    && array_key_exists('status', $response)
                    && array_key_exists('action', $response)
                    && array_key_exists('output', $response)
                    && is_numeric($response['status'])
                ) {
                    return array('unique' => $unique, 'output' => 'OK');
                } elseif ($unique === 'cproxywp'
                    && array_key_exists('status', $response)
                    && array_key_exists('action', $response)
                    && array_key_exists('output', $response)
                    && is_numeric($response['status'])
                ) {
                    return array('unique' => $unique, 'output' => 'OK');
                } elseif ($unique === 'sitechck'
                    && array_key_exists('SCAN', $response)
                    && array_key_exists('SYSTEM', $response)
                    && array_key_exists('BLACKLIST', $response)
                ) {
                    return array('unique' => $unique, 'output' => 'OK');
                } elseif ($unique === 'wpssalts'
                    && strpos($response, 'AUTH_KEY')
                    && strpos($response, 'AUTH_SALT')
                    && strpos($response, 'SECURE_AUTH_KEY')
                ) {
                    return array('unique' => $unique, 'output' => 'OK');
                } elseif ($unique === 'wphashes'
                    && is_array($response)
                    && array_key_exists('checksums', $response)
                    && is_array($response['checksums'])
                ) {
                    return array('unique' => $unique, 'output' => 'OK');
                } elseif ($unique === 'wpplugin'
                    && strpos($response, '<title>Sucuri Security')
                    && strpos($response, 'wordpress.org/plugin/sucuri-scanner')
                ) {
                    return array('unique' => $unique, 'output' => 'OK');
                } elseif ($unique === 'plugindt'
                    && array_key_exists('slug', $response)
                    && $response['slug'] === 'sucuri-scanner'
                ) {
                    return array('unique' => $unique, 'output' => 'OK');
                } elseif ($unique === 'wpvfpath'
                    && strpos($response, 'ABSPATH')
                    && strpos($response, 'wp_die')
                ) {
                    return array('unique' => $unique, 'output' => 'OK');
                }
            }
        }

        return array('unique' => $unique, 'output' => 'ERROR');
    }

    /**
     * Communicates with a remote URL and retrieves its content.
     *
     * Curl is a reflective object-oriented programming language for interactive
     * web applications whose goal is to provide a smoother transition between
     * formatting and programming. It makes it possible to embed complex objects
     * in simple documents without needing to switch between programming
     * languages or development platforms.
     *
     * Using Curl instead of the custom WordPress HTTP functions allow us to
     * control the functionality at 100% without expecting breaking changes in
     * newer versions of the code. For exampe, as of WordPress 4.6.x the result
     * of executing the functions prefixed with "wp_remote_" returns an object
     * WP_HTTP_Requests_Response that is not compatible with older implementations
     * of the plugin.
     *
     * @see https://secure.php.net/manual/en/book.curl.php
     *
     * @param  string $url    The target URL where the request will be sent.
     * @param  string $method HTTP method that will be used to send the request.
     * @param  array  $params Parameters for the request defined in an associative array.
     * @param  array  $args   Request arguments like the timeout, headers, cookies, etc.
     * @return array          Response object after the HTTP request is executed.
     */
    public static function apiCall($url = '', $method = 'GET', $params = array(), $args = array())
    {
        if ($url && ($method === 'GET' || $method === 'POST')) {
            $handler = SucuriScanOption::getOption(':api_handler');
            $params['ssl'] = self::verifySslCert() ? 'true' : 'false';

            if (!function_exists('curl_init') || $handler === 'socket') {
                $params['socket'] = 'true';
                $output = self::apiCallSocket($url, $method, $params, $args);
            } else {
                $params['curl'] = 'true';
                $output = self::apiCallCurl($url, $method, $params, $args);
            }

            $result = @json_decode($output, true);

            if ($result) {
                return $result;
            }

            return $output;
        }

        return false;
    }

    private static function apiCallCurl($url = '', $method = 'GET', $params = array(), $args = array())
    {
        if ($url
            && function_exists('curl_init')
            && ($method === 'GET' || $method === 'POST')
        ) {
            $curl = curl_init();
            $url = self::apiUrlProtocol($url);
            $timeout = self::requestTimeout();

            if (is_array($args) && isset($args['timeout'])) {
                $timeout = $args['timeout'];
            }

            // Add random request parameter to avoid request reset.
            if (!empty($params) && !array_key_exists('time', $params)) {
                $params['time'] = time();
            }

            if ($method === 'GET'
                && is_array($params)
                && !empty($params)
            ) {
                $url .= '?' . self::buildQuery($params);
            }

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, self::userAgent());
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout * 2);

            if (self::canCurlFollowRedirection()) {
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_MAXREDIRS, 2);
            }

            if ($method === 'POST') {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, self::buildQuery($params));
            }

            if (self::verifySslCert()) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            } else {
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            }

            $output = curl_exec($curl);
            $header = curl_getinfo($curl);
            $errors = curl_error($curl);

            curl_close($curl);

            if (array_key_exists('http_code', $header)
                && $header['http_code'] === 200
                && !empty($output)
            ) {
                return $output;
            }

            SucuriScan::throwException($errors);
        }

        return false;
    }

    private static function apiCallSocket($url = '', $method = 'GET', $params = array(), $args = array())
    {
        if (function_exists('fsockopen')) {
            $url = self::apiUrlProtocol($url);
            $timeout = self::requestTimeout();

            if (is_array($args) && isset($args['timeout'])) {
                $timeout = $args['timeout'];
            }

            // Add random request parameter to avoid request reset.
            if (!empty($params) && !array_key_exists('time', $params)) {
                $params['time'] = time();
            }

            if ($method === 'GET'
                && is_array($params)
                && !empty($params)
            ) {
                $url .= '?' . self::buildQuery($params);
            }

            $url_parts = parse_url($url);

            if (is_array($url_parts)
                && array_key_exists('host', $url_parts)
                && array_key_exists('scheme', $url_parts)
            ) {
                $host = $url_parts['host'];
                $path = '/';
                $port = 80;

                if ($url_parts['scheme'] === 'https') {
                    $host = sprintf('ssl://%s', $url_parts['host']);
                    $port = 443;
                }

                if (array_key_exists('path', $url_parts)) {
                    $path = $url_parts['path'];
                }

                if (array_key_exists('query', $url_parts)) {
                    $path .= '?' . $url_parts['query'];
                }

                $socket = fsockopen($host, $port, $errno, $errstr, $timeout);

                if ($socket) {
                    $headers = '';
                    $response = '';

                    $out = sprintf("%s %s HTTP/1.1\r\n", $method, $path);
                    $out .= "Accept: */*\r\n";
                    $out .= sprintf("Host: %s\r\n", $url_parts['host']);
                    $out .= sprintf("User-Agent: %s\r\n", self::userAgent());
                    $out .= "Connection: Close\r\n";

                    if ($method === 'POST') {
                        $query = self::buildQuery($params);
                        $out .= sprintf("Content-Length: %s\r\n", strlen($query));
                        $out .= "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n";
                        $out .= "\r\n" . $query;
                    }

                    fwrite($socket, $out . "\r\n");

                    while (strpos($headers, "\r\n\r\n") === false) {
                        $headers .= fread($socket, 1);
                    }

                    $chunk = '';
                    $segmented = false;

                    while (!feof($socket)) {
                        $byte = fread($socket, 1);

                        if ($byte === "\r") { /* CR */
                            fread($socket, 1); /* LF */

                            if (strlen($chunk) <= 4) {
                                /* Chunk size, ignore */
                            } else {
                                $segmented = true;
                                $response .= $chunk;
                            }

                            /* Reset and continue */
                            $chunk = '';
                            continue;
                        }

                        $chunk .= $byte;
                    }

                    if ($segmented === false) {
                        $response = $chunk;
                    }

                    fclose($socket);

                    /* Follow explicit redirection */
                    if (stripos($headers, 'location:') !== false) {
                        if (@preg_match('/ocation:(.+)\r\n/', $headers, $match)) {
                            return self::apiCallSocket(
                                trim($match[1]),
                                $method,
                                $params,
                                $args
                            );
                        }

                        return false;
                    }

                    /* Return if we reached the destination */
                    if (strpos($headers, '200 OK')) {
                        return $response;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check whether the plugin API key is valid or not.
     *
     * @param  string  $api_key An unique string to identify this installation.
     * @return boolean          True if the API key is valid, false otherwise.
     */
    private static function isValidKey($api_key = '')
    {
        return (bool) @preg_match('/^[a-z0-9]{32}$/', $api_key);
    }

    /**
     * Store the API key locally.
     *
     * @param  string  $api_key  An unique string of characters to identify this installation.
     * @param  boolean $validate Whether the format of the key should be validated before store it.
     * @return boolean           Either true or false if the key was saved successfully or not respectively.
     */
    public static function setPluginKey($api_key = '', $validate = false)
    {
        if ($validate) {
            if (!self::isValidKey($api_key)) {
                SucuriScanInterface::error('Invalid API key format');
                return false;
            }
        }

        if (!empty($api_key)) {
            SucuriScanEvent::notifyEvent('plugin_change', 'API key updated successfully: ' . $api_key);
        }

        return self::updateOption(':api_key', $api_key);
    }

    /**
     * Retrieve the API key from the local storage.
     *
     * @return string|boolean The API key or false if it does not exists.
     */
    public static function getPluginKey()
    {
        $api_key = self::getOption(':api_key');

        if (is_string($api_key)
            && self::isValidKey($api_key)
        ) {
            return $api_key;
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
     * @return array|boolean false if the key is invalid or not present, an array otherwise.
     */
    public static function getCloudproxyKey()
    {
        $option_name = ':cloudproxy_apikey';
        $api_key = self::getOption($option_name);

        // Check the validity of the API key.
        $match = self::isValidCloudproxyKey($api_key, true);

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
     * Check whether the CloudProxy API key is valid or not.
     *
     * @param  string  $api_key      The CloudProxy API key.
     * @param  boolean $return_match Whether the parts of the API key must be returned or not.
     * @return boolean               true if the API key specified is valid, false otherwise.
     */
    public static function isValidCloudproxyKey($api_key = '', $return_match = false)
    {
        $pattern = '/^([a-z0-9]{32})\/([a-z0-9]{32})$/';

        if ($api_key && preg_match($pattern, $api_key, $match)) {
            if ($return_match) {
                return $match;
            }

            return true;
        }

        return false;
    }

    /**
     * Call an action from the remote API interface of our WordPress service.
     *
     * @param  string  $method       HTTP method that will be used to send the request.
     * @param  array   $params       Parameters for the request defined in an associative array of key-value.
     * @param  boolean $send_api_key Whether the API key should be added to the request parameters or not.
     * @param  array   $args         Request arguments like the timeout, redirections, headers, cookies, etc.
     * @return array                 Response object after the HTTP request is executed.
     */
    public static function apiCallWordpress($method = 'GET', $params = array(), $send_api_key = true, $args = array())
    {
        $url = SUCURISCAN_API;
        $params[ SUCURISCAN_API_VERSION ] = 1;
        $params['p'] = 'wordpress';

        if ($send_api_key) {
            $api_key = self::getPluginKey();

            if (!$api_key) {
                return false;
            }

            $params['k'] = $api_key;
        }

        return self::apiCall($url, $method, $params, $args);
    }

    /**
     * Call an action from the remote API interface of our CloudProxy service.
     *
     * @param  string $method HTTP method that will be used to send the request.
     * @param  array  $params Parameters for the request defined in an associative array of key-value.
     * @return array          Response object after the HTTP request is executed.
     */
    public static function apiCallCloudproxy($method = 'GET', $params = array())
    {
        $send_request = false;

        if (isset($params['k']) && isset($params['s'])) {
            $send_request = true;
        } else {
            $api_key = self::getCloudproxyKey();

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
     * Determine whether an API response was successful or not checking the expected
     * generic variables and types, in case of an error a notification will appears
     * in the administrator panel explaining the result of the operation.
     *
     * @param  array   $response HTTP response after API endpoint execution.
     * @param  boolean $enqueue  Add the log to the local queue on a failure.
     * @return boolean           False if the API call failed, true otherwise.
     */
    private static function handleResponse($response = array(), $enqueue = true)
    {
        if ($response !== false) {
            if (is_array($response)
                && array_key_exists('status', $response)
                && intval($response['status']) === 1
            ) {
                return true;
            }

            if (is_array($response)
                && array_key_exists('messages', $response)
                && !empty($response['messages'])
            ) {
                return self::handleErrorResponse($response, $enqueue);
            }
        }

        return false;
    }

    /**
     * Process failures in the HTTP response.
     *
     * Log file not found: means that the API key used to execute the request is
     * not associated to the website, this may indicate that either the key was
     * invalidated by an administrator of the service or that the API key was
     * custom generated with invalid data.
     *
     * Wrong API key: means that the TLD of the origin of the request is not the
     * domain used to generate the API key in the first place, or that the email
     * address of the site administrator was changed so the data is not valid
     * anymore.
     *
     * Connection timeout: means that the API service is down either because the
     * hosting provider has connectivity issues or because the code is being
     * deployed. There is an option in the settings page that allows to temporarily
     * disable the communication with the API service while the server is down, this
     * allows the admins to keep the latency at zero and continue working in their
     * websites without interruptions.
     *
     * SSL issues: depending on the options used to compile the OpenSSL library
     * built by each hosting provider, the connection with the HTTPs version of the
     * API service may be rejected because of a failure in the SSL algorithm check.
     * There is an option in the settings page that allows to disable the SSL pair
     * verification, this option it disable automatically when the error is detected
     * for the first time.
     *
     * @param  array   $response HTTP response after API endpoint execution.
     * @param  boolean $enqueue  Add the log to the local queue on a failure.
     * @return boolean           False if the API call failed, true otherwise.
     */
    private static function handleErrorResponse($response = array(), $enqueue = true)
    {
        $msg = 'Unknown error, there is no more information.';

        if (is_array($response)
            && array_key_exists('messages', $response)
            && !empty($response['messages'])
        ) {
            $msg = implode(".\x20", $response['messages']);
            $raw = $msg; /* Keep a copy of the original message. */

            // Special response for invalid API keys.
            if (stripos($raw, 'log file not found') !== false) {
                $key = SucuriScanOption::getOption(':api_key');
                $msg .= '; this generally happens when you add an invalid API '
                . 'key, the key will be deleted automatically to hide these w'
                . 'arnings, if you want to recover it go to the settings page'
                . ' and use the recover button to send the key to your email '
                . 'address: ' . SucuriScan::escape($key);

                SucuriScanOption::deleteOption(':api_key');
            }

            // Special response for invalid CloudProxy API keys.
            if (stripos($raw, 'wrong api key') !== false) {
                $key = SucuriScanOption::getOption(':cloudproxy_apikey');
                $msg .= '; invalid CloudProxy API key: ' . SucuriScan::escape($key);

                SucuriScanInterface::error($msg);
                $msg = ''; /* Force premature error message. */

                SucuriScanOption::deleteOption(':cloudproxy_apikey');
                SucuriScanOption::setAddrHeader('REMOTE_ADDR');
                SucuriScanOption::setRevProxy('disable');
            }

            // Stop SSL peer verification on connection failures.
            if (stripos($raw, 'no alternative certificate')
                || stripos($raw, 'error setting certificate')
                || stripos($raw, 'SSL connect error')
            ) {
                SucuriScanOption::updateOption(':verify_ssl_cert', 'false');

                $msg .= 'There were some issues with the SSL certificate eith'
                . 'er in this server or with the remote API service. The auto'
                . 'matic verification of the certificates has been deactivate'
                . 'd to reduce the noise during the execution of the HTTP req'
                . 'uests.';
            }

            // Check if the MX records as missing for API registration.
            if (strpos($raw, 'Invalid email') !== false) {
                $msg = 'Email has an invalid format, or the host '
                . 'associated to the email has no MX records.';
            }
        }

        if (!empty($msg) && $enqueue) {
            SucuriScanInterface::error($msg);
        }

        return false;
    }

    /**
     * Send a request to the API to register this site.
     *
     * @param  string  $email Optional email address for the registration.
     * @return boolean        True if the API key was generated, false otherwise.
     */
    public static function registerSite($email = '')
    {
        if (!is_string($email) || empty($email)) {
            $email = self::getSiteEmail();
        }

        $response = self::apiCallWordpress('POST', array(
            'e' => $email,
            's' => self::getDomain(),
            'a' => 'register_site',
        ), false);

        if (self::handleResponse($response)) {
            self::setPluginKey($response['output']['api_key']);

            SucuriScanEvent::scheduleTask();
            SucuriScanEvent::notifyEvent('plugin_change', 'Site registered and API key generated');
            SucuriScanInterface::info('The API key for your site was successfully generated and saved.');

            return true;
        }

        return false;
    }

    /**
     * Send a request to recover a previously registered API key.
     *
     * @return boolean true if the API key was sent to the administrator email, false otherwise.
     */
    public static function recoverKey()
    {
        $clean_domain = self::getDomain();

        $response = self::apiCallWordpress('GET', array(
            'e' => self::getSiteEmail(),
            's' => $clean_domain,
            'a' => 'recover_key',
        ), false);

        if (self::handleResponse($response)) {
            SucuriScanEvent::notifyEvent('plugin_change', 'API key recovered for domain: ' . $clean_domain);
            SucuriScanInterface::info($response['output']['message']);

            return true;
        }

        return false;
    }

    /**
     * Send a request to the API to store and analyze the events of the site. An
     * event can be anything from a simple request, an internal modification of the
     * settings or files in the administrator panel, or a notification generated by
     * this plugin.
     *
     * @param  string  $event   Event triggered by the core system functions.
     * @param  integer $time    Timestamp when the event was originally triggered.
     * @param  boolean $enqueue Add the log to the local queue on a failure.
     * @return boolean          True if the event was logged, false otherwise.
     */
    public static function sendLog($event = '', $time = 0, $enqueue = true)
    {
        if (!empty($event)) {
            $params = array();
            $params['a'] = 'send_log';
            $params['m'] = $event;

            if (intval($time) > 0) {
                $params['time'] = (int) $time;
            }

            $response = self::apiCallWordpress('POST', $params, true);

            if (self::handleResponse($response, $enqueue)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send all logs from the queue.
     *
     * Retry the HTTP calls for the logs that were not sent to the API service
     * because of a connection failure or misconfiguration. Each successful call
     * will remove the log from the queue and the failures will keep them until the
     * next function call is executed.
     *
     * @return void
     */
    public static function sendLogsFromQueue()
    {
        $cache = new SucuriScanCache('auditqueue');
        $entries = $cache->getAll();

        if (is_array($entries) && !empty($entries)) {
            foreach ($entries as $key => $entry) {
                $result = self::sendLog(
                    $entry->message,
                    $entry->created_at,
                    false
                );

                if ($result === true) {
                    $cache->delete($key);
                } else {
                    /**
                     * Stop loop on failures.
                     *
                     * If the log was successfully sent to the API service then we can continue
                     * sending the other logs in the queue, otherwise the operation must be stopped
                     * so it can be executed next time when the service is online, not stopping the
                     * operation when one or more of the API calls fails will cause a very long
                     * delay in the load of the page that is being requested.
                     */
                    break;
                }
            }
        }
    }

    /**
     * Retrieve all the event logs registered by the API service.
     *
     * @return array The object with the data returned from the API service.
     */
    public static function getAllLogs()
    {
        // Get the total number of lines in the logs.
        $response = self::apiCallWordpress('GET', array(
            'a' => 'get_logs',
            'l' => 0,
        ));

        // If success continue with the retrieval of the logs data.
        if (self::handleResponse($response)) {
            return self::getLogs($response['total_entries']);
        }

        return false;
    }

    /**
     * Retrieve the event logs registered by the API service.
     *
     * @param  integer $lines How many lines from the log file will be retrieved.
     * @return string         The response of the API service.
     */
    public static function getLogs($lines = 50)
    {
        $response = self::apiCallWordpress('GET', array(
            'a' => 'get_logs',
            'l' => $lines,
        ));

        if (self::handleResponse($response)) {
            $response['output_data'] = array();
            $log_pattern = '/^([0-9\-]+) ([0-9:]+) (\S+) : (.+)/';
            $extra_pattern = '/(.+ \(multiple entries\):) (.+)/';
            $generic_pattern = '/^@?([A-Z][a-z]{3,7}): ([^;]+; )?(.+)/';
            $auth_pattern = '/^User authentication (succeeded|failed): ([^<;]+)/';

            foreach ($response['output'] as $log) {
                if (@preg_match($log_pattern, $log, $log_match)) {
                    $log_data = array(
                        'event' => 'notice',
                        'date' => '',
                        'time' => '',
                        'datetime' => '',
                        'timestamp' => 0,
                        'account' => $log_match[3],
                        'username' => 'system',
                        'remote_addr' => '127.0.0.1',
                        'message' => $log_match[4],
                        'file_list' => false,
                        'file_list_count' => 0,
                    );

                    // Extract and fix the date and time using the Eastern time zone.
                    $datetime = sprintf('%s %s EDT', $log_match[1], $log_match[2]);
                    $log_data['timestamp'] = strtotime($datetime);
                    $log_data['datetime'] = date('Y-m-d H:i:s', $log_data['timestamp']);
                    $log_data['date'] = date('Y-m-d', $log_data['timestamp']);
                    $log_data['time'] = date('H:i:s', $log_data['timestamp']);

                    // Extract more information from the generic audit logs.
                    $log_data['message'] = str_replace('<br>', '; ', $log_data['message']);

                    if (@preg_match($generic_pattern, $log_data['message'], $log_extra)) {
                        $log_data['event'] = strtolower($log_extra[1]);
                        $log_data['message'] = trim($log_extra[3]);

                        // Extract the username and remote address from the log.
                        if (!empty($log_extra[2])) {
                            $username_address = rtrim($log_extra[2], ";\x20");

                            // Separate the username from the remote address.
                            if (strpos($username_address, ",\x20") !== false) {
                                $usip_parts = explode(",\x20", $username_address, 2);

                                if (count($usip_parts) == 2) {
                                    // Separate the username from the display name.
                                    $log_data['username'] = @preg_replace('/^.+ \((.+)\)$/', '$1', $usip_parts[0]);
                                    $log_data['remote_addr'] = $usip_parts[1];
                                }
                            } else {
                                $log_data['remote_addr'] = $username_address;
                            }
                        }

                        // Fix old user authentication logs for backward compatibility.
                        $log_data['message'] = str_replace(
                            'logged in',
                            'authentication succeeded',
                            $log_data['message']
                        );

                        if (@preg_match($auth_pattern, $log_data['message'], $user_match)) {
                            $log_data['username'] = $user_match[2];
                        }
                    }

                    // Extract more information from the special formatted logs.
                    if (@preg_match($extra_pattern, $log_data['message'], $log_extra)) {
                        $log_data['message'] = $log_extra[1];
                        $log_extra[2] = str_replace(', new size', '; new size', $log_extra[2]);
                        $log_extra[2] = str_replace(",\x20", ";\x20", $log_extra[2]);
                        $log_data['file_list'] = explode(',', $log_extra[2]);
                        $log_data['file_list_count'] = count($log_data['file_list']);
                    }

                    $response['output_data'][] = $log_data;
                }
            }

            return $response;
        }

        return false;
    }

    /**
     * Get a list of valid audit event types with their respective colors.
     *
     * @return array Valid audit event types with their colors.
     */
    public static function getAuditEventTypes()
    {
        return array(
            'critical' => '#000000',
            'debug' => '#c690ec',
            'error' => '#f27d7d',
            'info' => '#5bc0de',
            'notice' => '#428bca',
            'warning' => '#f0ad4e',
        );
    }

    /**
     * Parse the event logs with multiple entries.
     *
     * @param  string $event_log Event log that will be processed.
     * @return array             List of parts of the event log.
     */
    public static function parseMultipleEntries($event_log = '')
    {
        if (@preg_match('/^(.*:\s)\(multiple entries\):\s(.+)/', $event_log, $match)) {
            $event_log = array();
            $event_log[] = trim($match[1]);
            $grouped_items = @explode(',', $match[2]);
            $event_log = array_merge($event_log, $grouped_items);
        }

        return $event_log;
    }

    /**
     * Collect the information for the audit log report.
     *
     * @param  integer $lines How many lines from the log file will be retrieved.
     * @return array          All the information necessary to display the audit logs report.
     */
    public static function getAuditReport($lines = 50)
    {
        $audit_logs = self::getLogs($lines);

        if (is_array($audit_logs)
            && array_key_exists('total_entries', $audit_logs)
            && array_key_exists('output_data', $audit_logs)
            && !empty($audit_logs['output_data'])
        ) {
            // Data structure that will be returned.
            $report = array(
                'total_events' => 0,
                'start_timestamp' => 0,
                'end_timestamp' => 0,
                'event_colors' => array(),
                'events_per_type' => array(),
                'events_per_user' => array(),
                'events_per_ipaddress' => array(),
                'events_per_login' => array(
                    'successful' => 0,
                    'failed' => 0,
                ),
            );

            // Get a list of valid audit event types.
            $event_types = self::getAuditEventTypes();
            foreach ($event_types as $event => $event_color) {
                $report['events_per_type'][$event] = 0;
                $report['event_colors'][] = sprintf("'%s'", $event_color);
            }

            // Collect information for each report chart.
            foreach ($audit_logs['output_data'] as $event) {
                $report['total_events'] += 1;

                // Increment the number of events for this event type.
                if (array_key_exists($event['event'], $report['events_per_type'])) {
                    $report['events_per_type'][ $event['event'] ] += 1;
                } else {
                    $report['events_per_type'][ $event['event'] ] = 1;
                }

                // Find the lowest datetime among the filtered events.
                if ($event['timestamp'] <= $report['start_timestamp']
                    || $report['start_timestamp'] === 0
                ) {
                    $report['start_timestamp'] = $event['timestamp'];
                }

                // Find the highest datetime among the filtered events.
                if ($event['timestamp'] >= $report['end_timestamp']) {
                    $report['end_timestamp'] = $event['timestamp'];
                }

                // Increment the number of events generated by this user account.
                $_username = SucuriScan::escape($event['username']);
                if (array_key_exists($_username, $report['events_per_user'])) {
                    $report['events_per_user'][$_username] += 1;
                } else {
                    $report['events_per_user'][$_username] = 1;
                }

                // Increment the number of events generated from this remote address.
                $_remote_addr = SucuriScan::escape($event['remote_addr']);
                if (array_key_exists($_remote_addr, $report['events_per_ipaddress'])) {
                    $report['events_per_ipaddress'][$_remote_addr] += 1;
                } else {
                    $report['events_per_ipaddress'][$_remote_addr] = 1;
                }

                // Detect successful and failed user authentications.
                $auth_pattern = '/^User authentication (succeeded|failed):/';

                if (@preg_match($auth_pattern, $event['message'], $match)) {
                    if ($match[1] == 'succeeded') {
                        $report['events_per_login']['successful'] += 1;
                    } else {
                        $report['events_per_login']['failed'] += 1;
                    }
                } elseif (@preg_match('/^User logged in:/', $event['message'])) {
                    // Backward compatibility for previous user login messages.
                    $report['events_per_login']['successful'] += 1;
                }
            }

            if ($report['total_events'] > 0) {
                return $report;
            }
        }

        return false;
    }

    /**
     * Send a request to the API to store and analyze the file's hashes of the site.
     * This will be the core of the monitoring tools and will enhance the
     * information of the audit logs alerting the administrator of suspicious
     * changes in the system.
     *
     * @param  string  $hashes The information gathered after the scanning of the site's files.
     * @return boolean         true if the hashes were stored, false otherwise.
     */
    public static function sendHashes($hashes = '')
    {
        if (!empty($hashes)) {
            $response = self::apiCallWordpress('POST', array(
                'a' => 'send_hashes',
                'h' => $hashes,
            ));

            if (self::handleResponse($response)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve the public settings of the account associated with the API keys
     * registered by the administrator of the site. This function will send a HTTP
     * request to the remote API service and process its response, when successful
     * it will return an array/object containing the public attributes of the site.
     *
     * @param  boolean $api_key The CloudProxy API key.
     * @return array            A hash with the settings of a CloudProxy account.
     */
    public static function getCloudproxySettings($api_key = false)
    {
        $params = array('a' => 'show_settings');

        if ($api_key) {
            $params = array_merge($params, $api_key);
        }

        $response = self::apiCallCloudproxy('GET', $params);

        if (self::handleResponse($response)) {
            return $response['output'];
        }

        return false;
    }

    /**
     * Flush the cache of the site(s) associated with the API key.
     *
     * @param  boolean $api_key The CloudProxy API key.
     * @return string           Message explaining the result of the operation.
     */
    public static function clearCloudproxyCache($api_key = false)
    {
        $params = array( 'a' => 'clear_cache' );

        if ($api_key) {
            $params = array_merge($params, $api_key);
        }

        $response = self::apiCallCloudproxy('GET', $params);

        if (self::handleResponse($response)) {
            return $response;
        }

        return false;
    }

    /**
     * Retrieve the audit logs of the account associated with the API keys
     * registered b the administrator of the site. This function will send a HTTP
     * request to the remote API service and process its response, when successful
     * it will return an array/object containing a list of requests blocked by our
     * CloudProxy.
     *
     * By default the logs that will be retrieved are from today, if you need to see
     * the logs of previous days you will need to add a new parameter to the request
     * URL named "date" with format yyyy-mm-dd.
     *
     * @param  string  $api_key The CloudProxy API key.
     * @param  string  $date    Retrieve the data from this date.
     * @param  string  $query   Filter the data to match this query.
     * @param  integer $limit   Retrieve this maximum of data.
     * @param  integer $offset  Retrieve the data from this point.
     * @return array            Objects with details of each blocked request.
     */
    public static function firewallAuditLogs($api_key, $date = '', $query = '', $limit = 10, $offset = 0)
    {
        $params = array(
            'a' => 'audit_trails',
            'date' => $date,
            'query' => $query,
            'limit' => $limit,
            'offset' => $offset,
        );

        if (is_array($api_key) && !empty($api_key)) {
            $params = array_merge($params, $api_key);
        }

        $response = self::apiCallCloudproxy('GET', $params);

        if (self::handleResponse($response)) {
            return $response['output'];
        }

        return false;
    }

    /**
     * Scan a website through the public SiteCheck API [1] for known malware,
     * blacklisting status, website errors, and out-of-date software.
     *
     * [1] https://sitecheck.sucuri.net/
     *
     * @param  string  $domain The clean version of the website's domain.
     * @param  boolean $clear  Request the results from a fresh scan or not.
     * @return object          JSON encoded website scan results.
     */
    public static function getSitecheckResults($domain = '', $clear = true)
    {
        if (!empty($domain)) {
            $params = array();
            $timeout = (int) SucuriScanOption::getOption(':sitecheck_timeout');
            $params['scan'] = $domain;
            $params['fromwp'] = 2;
            $params['json'] = 1;

            // Request a fresh scan or not.
            if ($clear === true) {
                $params['clear'] = 1;
            }

            $response = self::apiCall(
                'sucuri://sitecheck.sucuri.net/',
                'GET',
                $params,
                array(
                    'assoc' => true,
                    'timeout' => $timeout,
                )
            );

            return $response;
        }

        return false;
    }

    /**
     * Extract detailed information from a SiteCheck malware payload.
     *
     * @param  array $malware Array with two entries with basic malware information.
     * @return array          Detailed information of the malware found by SiteCheck.
     */
    public static function getSitecheckMalware($malware = array())
    {
        if (count($malware) >= 2) {
            $data_set = array(
                'alert_message' => '',
                'infected_url' => '',
                'malware_type' => '',
                'malware_docs' => '',
                'malware_payload' => '',
            );

            // Extract the information from the alert message.
            $alert_parts = explode(':', $malware[0], 2);

            if (isset($alert_parts[1])) {
                $data_set['alert_message'] = $alert_parts[0];
                $data_set['infected_url'] = $alert_parts[1];
            }

            // Extract the information from the malware message.
            $malware_parts = explode("\n", $malware[1]);

            if (isset($malware_parts[1])) {
                if (@preg_match('/(.+)\. Details: (.+)/', $malware_parts[0], $match)) {
                    $data_set['malware_type'] = $match[1];
                    $data_set['malware_docs'] = $match[2];
                }

                $data_set['malware_payload'] = trim($malware_parts[1]);
            }

            return $data_set;
        }

        return false;
    }

    /**
     * Retrieve a new set of keys for the WordPress configuration file using the
     * official API provided by WordPress itself.
     *
     * @return array A list of the new set of keys generated by WordPress API.
     */
    public static function getNewSecretKeys()
    {
        $pattern = self::secretKeyPattern();
        $response = self::apiCall('sucuri://api.wordpress.org/secret-key/1.1/salt/', 'GET');

        if ($response && @preg_match_all($pattern, $response, $match)) {
            $new_keys = array();

            foreach ($match[1] as $i => $value) {
                $new_keys[$value] = $match[3][$i];
            }

            return $new_keys;
        }

        return false;
    }

    /**
     * Retrieve a list with the checksums of the files in a specific version of WordPress.
     *
     * @see Release Archive https://wordpress.org/download/release-archive/
     *
     * @param  integer $version Valid version number of the WordPress project.
     * @return object           Associative object with the relative filepath and the checksums of the project files.
     */
    public static function getOfficialChecksums($version = 0)
    {
        $language = SucuriScanOption::getOption(':language');
        $response = self::apiCall(
            'sucuri://api.wordpress.org/core/checksums/1.0/',
            'GET',
            array(
                'version' => $version,
                'locale' => $language,
            )
        );

        if (is_array($response)
            && array_key_exists('checksums', $response)
            && !empty($response['checksums'])
        ) {
            if (count((array) $response['checksums']) <= 1
                && array_key_exists($version, $response['checksums'])
            ) {
                return $response['checksums'][$version];
            } else {
                return $response['checksums'];
            }
        }

        return false;
    }

    /**
     * Check the plugins directory and retrieve all plugin files with plugin data.
     * This function will also retrieve the URL and name of the repository/page
     * where it is being published at the WordPress plugins market.
     *
     * @return array Key is the plugin file path and the value is an array of the plugin data.
     */
    public static function getPlugins()
    {
        // Check if the cache library was loaded.
        $can_cache = class_exists('SucuriScanCache');

        if ($can_cache) {
            $cache = new SucuriScanCache('plugindata');
            $cached_data = $cache->get('plugins', SUCURISCAN_GET_PLUGINS_LIFETIME, 'array');

            // Return the previously cached results of this function.
            if ($cached_data !== false) {
                return $cached_data;
            }
        }

        // Get the plugin's basic information from WordPress transient data.
        $plugins = get_plugins();
        $pattern = '/^http(s)?:\/\/wordpress\.org\/plugins\/(.*)\/$/';
        $wp_market = 'sucuri://wordpress.org/plugins/%s/';

        // Loop through each plugin data and complement its information with more attributes.
        foreach ($plugins as $plugin_path => $plugin_data) {
            // Default values for the plugin extra attributes.
            $repository = '';
            $repository_name = '';
            $is_free_plugin = false;

            /**
             * Extract the information of the plugin which includes the repository name,
             * repository URL, and if the source code of the plugin is publicly released or
             * not, in this last case if the source code of the plugin is not hosted in the
             * official WordPress server it means that it is premium and is being
             * distributed by an independent developer.
             */
            if (isset($plugin_data['PluginURI'])
                && preg_match($pattern, $plugin_data['PluginURI'], $match)
            ) {
                $repository = $match[0];
                $repository_name = $match[2];
                $is_free_plugin = true;
            } else {
                if (strpos($plugin_path, '/') !== false) {
                    $plugin_path_parts = explode('/', $plugin_path, 2);
                } else {
                    $plugin_path_parts = explode('.', $plugin_path, 2);
                }

                if (isset($plugin_path_parts[0])) {
                    $possible_repository = sprintf($wp_market, $plugin_path_parts[0]);
                    $possible_repository = SucuriScanAPI::apiUrlProtocol($possible_repository);
                    $resp = wp_remote_head($possible_repository);

                    if (!is_wp_error($resp)
                        && $resp['response']['code'] == 200
                    ) {
                        $repository = $possible_repository;
                        $repository_name = $plugin_path_parts[0];
                        $is_free_plugin = true;
                    }
                }
            }

            // Complement the plugin's information with these attributes.
            $plugins[$plugin_path]['Repository'] = $repository;
            $plugins[$plugin_path]['RepositoryName'] = $repository_name;
            $plugins[$plugin_path]['InstallationPath'] = sprintf('%s/%s', WP_PLUGIN_DIR, $repository_name);
            $plugins[$plugin_path]['IsFreePlugin'] = $is_free_plugin;
            $plugins[$plugin_path]['PluginType'] = ( $is_free_plugin ? 'free' : 'premium' );
            $plugins[$plugin_path]['IsPluginActive'] = false;
            $plugins[$plugin_path]['IsPluginInstalled'] = false;

            if (is_plugin_active($plugin_path)) {
                $plugins[$plugin_path]['IsPluginActive'] = true;
            }

            if (is_dir($plugins[$plugin_path]['InstallationPath'])) {
                $plugins[$plugin_path]['IsPluginInstalled'] = true;
            }
        }

        if ($can_cache) {
            // Add the information of the plugins to the file-based cache.
            $cache->add('plugins', $plugins);
        }

        return $plugins;
    }

    /**
     * Retrieve plugin installer pages from WordPress Plugins API.
     *
     * It is possible for a plugin to override the Plugin API result with three
     * filters. Assume this is for plugins, which can extend on the Plugin Info to
     * offer more choices. This is very powerful and must be used with care, when
     * overriding the filters.
     *
     * The first filter, 'plugins_api_args', is for the args and gives the action as
     * the second parameter. The hook for 'plugins_api_args' must ensure that an
     * object is returned.
     *
     * The second filter, 'plugins_api', is the result that would be returned.
     *
     * @param  string $plugin Frienly name of the plugin.
     * @return object         Object on success, WP_Error on failure.
     */
    public static function getRemotePluginData($plugin = '')
    {
        if (!empty($plugin)) {
            $url = sprintf('sucuri://api.wordpress.org/plugins/info/1.0/%s.json', $plugin);
            $response = self::apiCall($url, 'GET');

            if ($response) {
                return $response;
            }
        }

        return false;
    }

    /**
     * Retrieve a specific file from the official WordPress subversion repository,
     * the content of the file is determined by the tags defined using the site
     * version specified. Only official core files are allowed to fetch.
     *
     * @see https://core.svn.wordpress.org/
     * @see https://i18n.svn.wordpress.org/
     * @see https://core.svn.wordpress.org/tags/VERSION_NUMBER/
     *
     * @param  string $filepath Relative file path of a project core file.
     * @param  string $version  Optional site version, default will be the global version number.
     * @return string           Full content of the official file retrieved, false if the file was not found.
     */
    public static function getOriginalCoreFile($filepath = '', $version = 0)
    {
        if (!empty($filepath)) {
            if ($version == 0) {
                $version = self::siteVersion();
            }

            $url = sprintf('sucuri://core.svn.wordpress.org/tags/%s/%s', $version, $filepath);
            $response = self::apiCall($url, 'GET');

            if ($response) {
                return $response;
            }
        }

        return false;
    }
}
