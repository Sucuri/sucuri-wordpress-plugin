<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Miscellaneous library.
 *
 * Multiple and generic functions that will be used through out the code of
 * other libraries extending from this and functions defined in other files, be
 * aware of the hierarchy and check the other libraries for duplicated methods.
 */
class SucuriScan
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
    }

    /**
     * Throw generic exception instead of silent failure for unit-tests.
     *
     * @param  string $message Error or information message.
     * @param  string $type    Either info or error.
     * @return void
     */
    public static function throwException($message, $type = 'error')
    {
        if (defined('SUCURISCAN_THROW_EXCEPTIONS')
            && SUCURISCAN_THROW_EXCEPTIONS === true
            && is_string($message)
            && !empty($message)
        ) {
            $code = ($type === 'error' ? 157 : 333);
            $message = str_replace(
                '<b>Sucuri:</b>',
                ($type === 'error' ? 'Error:' : 'Info:'),
                $message
            );

            throw new Exception($message, $code);
        }
    }

    /**
     * Return name of a variable with the plugin's prefix (if needed).
     *
     * To facilitate the development, you can prefix the name of the key in the
     * request (when accessing it) with a single colon, this function will
     * automatically replace that character with the unique identifier of the
     * plugin.
     *
     * @param  string $var_name Name of a variable with an optional colon at the beginning.
     * @return string           Full name of the variable with the extra characters (if needed).
     */
    public static function varPrefix($var_name = '')
    {
        if (!empty($var_name) && $var_name[0] === ':') {
            $var_name = sprintf(
                '%s_%s',
                SUCURISCAN,
                substr($var_name, 1)
            );
        }

        return $var_name;
    }

    /**
     * Gets the value of a configuration option.
     *
     * @param  string $property The configuration option name.
     * @return string           Value of the configuration option as a string on success.
     */
    public static function iniGet($property = '')
    {
        $ini_value = ini_get($property);
        $default = array(
            'error_log' => 'error_log',
            'safe_mode' => 'Off',
            'memory_limit' => '128M',
            'upload_max_filesize' => '2M',
            'post_max_size' => '8M',
            'max_execution_time' => '30',
            'max_input_time' => '-1',
        );

        if ($ini_value === false) {
            $ini_value = 'Undefined';
        } elseif (empty($ini_value) || $ini_value === null) {
            if (array_key_exists($property, $default)) {
                $ini_value = $default[$property];
            } else {
                $ini_value = 'Off';
            }
        }

        if ($property == 'error_log') {
            $ini_value = basename($ini_value);
        }

        return $ini_value;
    }

    /**
     * Encodes the less-than, greater-than, ampersand, double quote and single quote
     * characters, will never double encode entities.
     *
     * @param  string $text The text which is to be encoded.
     * @return string       The encoded text with HTML entities.
     */
    public static function escape($text = '')
    {
        // Escape the value of the variable using a built-in function if possible.
        if (function_exists('esc_attr')) {
            $text = esc_attr($text);
        } else {
            $text = htmlspecialchars($text);
        }

        return $text;
    }

    /**
     * Generates a lowercase random string with an specific length.
     *
     * @param  integer $length Length of the string that will be generated.
     * @return string          The random string generated.
     */
    public static function randChar($length = 4)
    {
        $string = '';
        $offset = 25;
        $chars = array(
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
            'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        );

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, $offset);
            $string .= $chars[$index];
        }

        return $string;
    }

    /**
     * Translate a given number in bytes to a human readable file size using the
     * a approximate value in Kylo, Mega, Giga, etc.
     *
     * @link   https://www.php.net/manual/en/function.filesize.php#106569
     * @param  integer $bytes    An integer representing a file size in bytes.
     * @param  integer $decimals How many decimals should be returned after the translation.
     * @return string            Human readable representation of the given number in Kylo, Mega, Giga, etc.
     */
    public static function humanFileSize($bytes = 0, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[ $factor ];
    }

    /**
     * Check if the admin init hook must not be intercepted.
     *
     * @return boolean True if the admin init hook must not be intercepted.
     */
    public static function noAdminInit()
    {
        return (bool) (
            defined('SUCURISCAN_ADMIN_INIT')
            && SUCURISCAN_ADMIN_INIT === false
        );
    }

    /**
     * Check if the admin init hook must be intercepted.
     *
     * @return boolean True if the admin init hook must be intercepted.
     */
    public static function runAdminInit()
    {
        return (bool) (self::noAdminInit() === false);
    }

    /**
     * Fix the deliminar of a resource path.
     *
     * In Windows based system the directory separator is a back slash which
     * differs from what other file systems use. To keep consistency during the
     * unit-tests we have decided to replace any non forward slash with it.
     *
     * @return string Fixed file path.
     */
    public static function fixPath($path = '')
    {
        $delimiter = '/' /* Forward slash */;
        $path = str_replace(DIRECTORY_SEPARATOR, $delimiter, $path);
        $path = rtrim($path, $delimiter);

        return $path;
    }

    /**
     * Returns the system filepath to the relevant user uploads directory for this
     * site. This is a multisite capable function.
     *
     * @param  string $path The relative path that needs to be completed to get the absolute path.
     * @return string       The full filesystem path including the directory specified.
     */
    public static function dataStorePath($path = '')
    {
        $datastore = SucuriScanOption::getOption(':datastore_path');

        return self::fixPath($datastore . '/' . $path);
    }

    /**
     * Check whether the current site is working as a multi-site instance.
     *
     * @return boolean Either TRUE or FALSE in case WordPress is being used as a multi-site instance.
     */
    public static function isMultiSite()
    {
        return (bool) (function_exists('is_multisite') && is_multisite());
    }

    public static function adminURL($url = '')
    {
        if (self::isMultiSite()) {
            return network_admin_url($url);
        } else {
            return admin_url($url);
        }
    }

    /**
     * Find and retrieve the current version of Wordpress installed.
     *
     * @return string The version number of Wordpress installed.
     */
    public static function siteVersion()
    {
        global $wp_version;

        if ($wp_version === null) {
            $wp_version_path = ABSPATH . WPINC . '/version.php';

            if (file_exists($wp_version_path)) {
                include($wp_version_path);
                $wp_version = isset($wp_version) ? $wp_version : '0.0';
            } else {
                $option_version = get_option('version');
                $wp_version = $option_version ? $option_version : '0.0';
            }
        }

        return self::escape($wp_version);
    }

    /**
     * Find and retrieve the absolute path of the WordPress configuration file.
     *
     * @return string Absolute path of the WordPress configuration file.
     */
    public static function getWPConfigPath()
    {
        if (defined('ABSPATH')) {
            $file_path = ABSPATH . '/wp-config.php';

            // if wp-config.php doesn't exist, or is not readable check one directory up.
            if (!file_exists($file_path)) {
                $file_path = ABSPATH . '/../wp-config.php';
            }

            // Remove duplicated double slashes.
            $file_path = @realpath($file_path);

            if ($file_path) {
                return $file_path;
            }
        }

        return false;
    }

    /**
     * Find and retrieve the absolute path of the main WordPress htaccess file.
     *
     * @return string Absolute path of the main WordPress htaccess file.
     */
    public static function getHtaccessPath()
    {
        if (defined('ABSPATH')) {
            $base_dirs = array(
                rtrim(ABSPATH, '/'),
                dirname(ABSPATH),
                dirname(dirname(ABSPATH)),
            );

            foreach ($base_dirs as $base_dir) {
                $htaccess_path = sprintf('%s/.htaccess', $base_dir);

                if (file_exists($htaccess_path)) {
                    return $htaccess_path;
                }
            }
        }

        return false;
    }

    /**
     * Get the pattern of the definition related with a WordPress secret key.
     *
     * @return string Secret key definition pattern.
     */
    public static function secretKeyPattern()
    {
        return '/define\(\s*\'([A-Z_]+)\',(\s*)\'(.+)\'\s*\);/';
    }

    /**
     * Execute the plugin' scheduled tasks.
     *
     * @return void
     */
    public static function runScheduledTask()
    {
        SucuriScanEvent::filesystemScan();

        sucuriscan_core_files_data(true);
        sucuriscan_posthack_updates_content(true);
    }

    /**
     * List of allowed HTTP headers to retrieve the real IP.
     *
     * Once the DNS lookups are enabled to discover the real IP address of the
     * visitors the user may choose the HTTP header that will be used by default to
     * retrieve the real IP address of each HTTP request, generally they do not need
     * to set this but in rare cases the hosting provider may have a load balancer
     * that can interfere in the process, in which case they will have to explicitly
     * specify the main HTTP header. This is a list of the allowed headers that the
     * user can choose.
     *
     * @param  boolean $with_keys Return the array with its values are keys.
     * @return array              Allowed HTTP headers to retrieve real IP.
     */
    public static function allowedHttpHeaders($with_keys = false)
    {
        $allowed = array(
            /* CloudProxy custom HTTP headers */
            'HTTP_X_SUCURI_CLIENTIP',
            /* CloudFlare custom HTTP headers */
            'HTTP_CF_CONNECTING_IP', /* Real visitor IP. */
            'HTTP_CF_IPCOUNTRY', /* Country of visitor. */
            'HTTP_CF_RAY', /* https://support.cloudflare.com/entries/23046742-w. */
            'HTTP_CF_VISITOR', /* Determine if HTTP or HTTPS. */
            /* Possible HTTP headers */
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'SUCURI_RIP',
            'REMOTE_ADDR',
        );

        if ($with_keys === true) {
            $verbose = array();

            foreach ($allowed as $header) {
                $verbose[$header] = $header;
            }

            return $verbose;
        }

        return $allowed;
    }

    /**
     * List HTTP headers ordered.
     *
     * The list of HTTP headers is ordered per relevancy, and having the main HTTP
     * header as the first entry, this guarantees that the IP address of the
     * visitors will be retrieved from the HTTP header chosen by the user first and
     * fallback to the other alternatives if available.
     *
     * @return array Ordered allowed HTTP headers.
     */
    private static function orderedHttpHeaders()
    {
        $ordered = array();
        $allowed = self::allowedHttpHeaders();
        $addr_header = SucuriScanOption::getOption(':addr_header');
        $ordered[] = $addr_header;

        foreach ($allowed as $header) {
            if (!in_array($header, $ordered)) {
                $ordered[] = $header;
            }
        }

        return $ordered;
    }

    /**
     * Retrieve the real ip address of the user in the current request.
     *
     * @param  boolean $with_header Return HTTP header where the IP address was found.
     * @return string               Real IP address of the user in the current request.
     */
    public static function getRemoteAddr($with_header = false)
    {
        $remote_addr = false;
        $header_used = 'unknown';
        $headers = self::orderedHttpHeaders();

        foreach ($headers as $header) {
            if (array_key_exists($header, $_SERVER)
                && self::isValidIP($_SERVER[$header])
            ) {
                $remote_addr = $_SERVER[$header];
                $header_used = $header;
                break;
            }
        }

        if (!$remote_addr || $remote_addr === '::1') {
            $remote_addr = '127.0.0.1';
        }

        if ($with_header) {
            return $header_used;
        }

        return $remote_addr;
    }

    /**
     * Return the HTTP header used to retrieve the remote address.
     *
     * @return string The HTTP header used to retrieve the remote address.
     */
    public static function getRemoteAddrHeader()
    {
        return self::getRemoteAddr(true);
    }

    /**
     * Retrieve the user-agent from the current request.
     *
     * @return string The user-agent from the current request.
     */
    public static function getUserAgent()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            return self::escape($_SERVER['HTTP_USER_AGENT']);
        }

        return false;
    }

    /**
     * Get the clean version of the current domain.
     *
     * @return string The domain of the current site.
     */
    public static function getDomain($return_tld = false)
    {
        if (function_exists('get_site_url')) {
            $site_url = get_site_url();
            $pattern = '/([fhtps]+:\/\/)?([^:\/]+)(:[0-9:]+)?(\/.*)?/';
            $replacement = ($return_tld === true) ? '$2' : '$2$3$4';
            $domain_name = @preg_replace($pattern, $replacement, $site_url);

            return $domain_name;
        }

        return false;
    }

    /**
     * Get top-level domain (TLD) of the website.
     *
     * @return string Top-level domain (TLD) of the website.
     */
    public static function getTopLevelDomain()
    {
        return self::getDomain(true);
    }

    /**
     * Check whether reverse proxy servers must be supported.
     *
     * @return boolean TRUE if reverse proxies must be supported, FALSE otherwise.
     */
    public static function supportReverseProxy()
    {
        return SucuriScanOption::isEnabled(':revproxy');
    }

    /**
     * Check whether the DNS lookups should be execute or not.
     *
     * DNS lookups are only necessary if you are planning to use a reverse proxy or
     * firewall (like CloudProxy), this is used to set the correct IP address when
     * the firewall/proxy filters the requests. If you are not planning to use any
     * of these is better to disable this option, otherwise the load time of your
     * site may be affected.
     *
     * @return boolean True if the DNS lookups should be executed, false otherwise.
     */
    public static function executeDNSLookups()
    {
        if (( defined('NOT_USING_CLOUDPROXY') && NOT_USING_CLOUDPROXY === true )
            || SucuriScanOption::isDisabled(':dns_lookups')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Check whether the site is behind the Sucuri CloudProxy network.
     *
     * @param  boolean $verbose Return an array with the hostname, address, and status, or not.
     * @return boolean          Either TRUE or FALSE if the site is behind CloudProxy.
     */
    public static function isBehindCloudproxy($verbose = false)
    {
        $http_host = self::getTopLevelDomain();

        if (self::executeDNSLookups()) {
            $host_by_addr = @gethostbyname($http_host);
            $host_by_name = @gethostbyaddr($host_by_addr);
            $status = (bool) preg_match('/^cloudproxy[0-9]+\.sucuri\.net$/', $host_by_name);
        } else {
            $status = false;
            $host_by_addr = '::1';
            $host_by_name = 'localhost';
        }

        /*
         * If the DNS reversion failed but the CloudProxy API key is set, then consider
         * the site as protected by a firewall. A fake key can be used to bypass the DNS
         * checking, but that is not something that will affect us, only the client.
         */
        if (!$status && SucuriScanAPI::getCloudproxyKey()) {
            $status = true;
        }

        if ($verbose) {
            return array(
                'http_host' => $http_host,
                'host_name' => $host_by_name,
                'host_addr' => $host_by_addr,
                'status' => $status,
            );
        }

        return $status;
    }

    /**
     * Get the email address set by the administrator to receive the notifications
     * sent by the plugin, if the email is missing the WordPress email address is
     * chosen by default.
     *
     * @return string The administrator email address.
     */
    public static function getSiteEmail()
    {
        $email = get_option('admin_email');

        if (self::isValidEmail($email)) {
            return $email;
        }

        return false;
    }

    /**
     * Get user data by field and data.
     *
     * @param  integer $identifier User account identifier.
     * @return object              WordPress user object with data.
     */
    public static function getUserByID($identifier = 0)
    {
        if (function_exists('get_user_by')) {
            $user = get_user_by('id', $identifier);

            if ($user instanceof WP_User) {
                return $user;
            }
        }

        return false;
    }

    /**
     * Retrieve a list of all admin user accounts.
     *
     * @return array List of admin users, false otherwise.
     */
    public static function getAdminUsers()
    {
        if (function_exists('get_users')) {
            return get_users(array('role' => 'administrator'));
        }

        return false;
    }

    /**
     * Get a list of user emails that can be used to generate an API key for this
     * website. Only accounts with the status in zero will be returned, the status
     * field in the users table is officially deprecated but some 3rd-party plugins
     * still use it to check if the account was activated by the owner of the email,
     * a value different than zero generally means that the email was not verified
     * successfully.
     *
     * @return array List of user identifiers and email addresses.
     */
    public static function getUsersForAPIKey()
    {
        $valid_users = array();
        $users = self::getAdminUsers();

        if ($users !== false) {
            foreach ($users as $user) {
                if ($user->user_status === '0') {
                    $valid_users[$user->ID] = sprintf(
                        '%s - %s',
                        $user->user_login,
                        $user->user_email
                    );
                }
            }
        }

        return $valid_users;
    }

    /**
     * Returns the current time measured in the number of seconds since the Unix Epoch.
     *
     * @return integer Return current Unix timestamp.
     */
    public static function localTime()
    {
        if (function_exists('current_time')) {
            return current_time('timestamp');
        } else {
            return time();
        }
    }

    /**
     * Retrieve the date in localized format, based on timestamp.
     *
     * If the locale specifies the locale month and weekday, then the locale will
     * take over the format for the date. If it isn't, then the date format string
     * will be used instead.
     *
     * @param  integer $timestamp Unix timestamp.
     * @return string             The date, translated if locale specifies it.
     */
    public static function datetime($timestamp = null)
    {
        global $sucuriscan_date_format, $sucuriscan_time_format;

        $tz_format = $sucuriscan_date_format . "\x20" . $sucuriscan_time_format;

        if (is_numeric($timestamp) && $timestamp > 0) {
            return date_i18n($tz_format, $timestamp);
        }

        return date_i18n($tz_format);
    }

    /**
     * Retrieve the date in localized format based on the current time.
     *
     * @return string The date, translated if locale specifies it.
     */
    public static function currentDateTime()
    {
        return self::datetime();
    }

    /**
     * Return the time passed since the specified timestamp until now.
     *
     * @param  integer $timestamp The Unix time number of the date/time before now.
     * @return string             The time passed since the timestamp specified.
     */
    public static function timeAgo($timestamp = 0)
    {
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        $local_time = self::localTime();
        $diff = abs($local_time - intval($timestamp));

        if ($diff == 0) {
            return 'just now';
        }

        $intervals = array(
            1                => array( 'year', 31556926, ),
            $diff < 31556926 => array( 'month', 2592000, ),
            $diff < 2592000  => array( 'week', 604800, ),
            $diff < 604800   => array( 'day', 86400, ),
            $diff < 86400    => array( 'hour', 3600, ),
            $diff < 3600     => array( 'minute', 60, ),
            $diff < 60       => array( 'second', 1, ),
        );

        $value = floor($diff / $intervals[1][1]);
        $time_ago = sprintf(
            '%s %s%s ago',
            $value,
            $intervals[1][0],
            ( $value > 1 ? 's' : '' )
        );

        return $time_ago;
    }

    /**
     * Convert an string of characters into a valid variable name.
     *
     * @see https://www.php.net/manual/en/language.variables.basics.php
     *
     * @param  string $text A text containing alpha-numeric and special characters.
     * @return string       A valid variable name.
     */
    public static function human2var($text = '')
    {
        $text = strtolower($text);
        $pattern = '/[^a-z0-9_]/';
        $var_name = preg_replace($pattern, '_', $text);

        return $var_name;
    }

    /**
     * Check whether a variable contains a serialized data or not.
     *
     * @param  string  $data The data that will be checked.
     * @return boolean       TRUE if the data was serialized, FALSE otherwise.
     */
    public static function isSerialized($data = '')
    {
        return ( is_string($data) && preg_match('/^(a|O):[0-9]+:.+/', $data) );
    }

    /**
     * Check whether an IP address has a valid format or not.
     *
     * @param  string  $remote_addr The host IP address.
     * @return boolean              Whether the IP address specified is valid or not.
     */
    public static function isValidIP($remote_addr = '')
    {
        if (function_exists('filter_var')) {
            return (bool) filter_var($remote_addr, FILTER_VALIDATE_IP);
        } elseif (strlen($remote_addr) >= 7) {
            $pattern = '/^([0-9]{1,3}\.) {3}[0-9]{1,3}$/';

            if (preg_match($pattern, $remote_addr, $match)) {
                for ($i = 0; $i < 4; $i++) {
                    if ($match[ $i ] > 255) {
                        return false;
                    }
                }

                return true;
            }
        }

        return false;
    }


    /**
     * Check whether an IP address is formatted as CIDR or not.
     *
     * @param  string $remote_addr The supposed ip address that will be checked.
     * @return boolean             Either TRUE or FALSE if the ip address specified is valid or not.
     */
    public static function isValidCIDR($remote_addr = '')
    {
        if (preg_match('/^([0-9\.]{7,15})\/(8|16|24)$/', $remote_addr, $match)) {
            if (self::isValidIP($match[1])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Separate the parts of an IP address.
     *
     * @param  string $remote_addr The supposed ip address that will be formatted.
     * @return array               Clean address, CIDR range, and CIDR format; FALSE otherwise.
     */
    public static function getIPInfo($remote_addr = '')
    {
        if ($remote_addr) {
            $ip_parts = explode('/', $remote_addr);

            if (array_key_exists(0, $ip_parts)
                && self::isValidIP($ip_parts[0])
            ) {
                $addr_info = array();
                $addr_info['remote_addr'] = $ip_parts[0];
                $addr_info['cidr_range'] = isset($ip_parts[1]) ? $ip_parts[1] : '32';
                $addr_info['cidr_format'] = $addr_info['remote_addr'] . '/' . $addr_info['cidr_range'];

                return $addr_info;
            }
        }

        return false;
    }

    /**
     * Validate email address.
     *
     * This use the native PHP function filter_var which is available in PHP >=
     * 5.2.0 if it is not found in the interpreter this function will sue regular
     * expressions to check whether the email address passed is valid or not.
     *
     * @see https://www.php.net/manual/en/function.filter-var.php
     *
     * @param  string $email The string that will be validated as an email address.
     * @return boolean       TRUE if the email address passed to the function is valid, FALSE if not.
     */
    public static function isValidEmail($email = '')
    {
        if (function_exists('filter_var')) {
            return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
        } else {
            $pattern = '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix';
            return (bool) preg_match($pattern, $email);
        }
    }

    /**
     * Check whether a regular expression is valid or not.
     *
     * @param  string  $pattern The regular expression to check.
     * @return boolean          True if the regular expression is valid, false otherwise.
     */
    public static function isValidPattern($pattern = '')
    {
        return (bool) (
            is_string($pattern)
            && !empty($pattern)
            && @preg_match($pattern, null) !== false
        );
    }

    /**
     * Cut a long text to the length specified, and append suspensive points at the end.
     *
     * @param  string  $text   String of characters that will be cut.
     * @param  integer $length Maximum length of the returned string, default is 10.
     * @return string          Short version of the text specified.
     */
    public static function excerpt($text = '', $length = 10)
    {
        $text_length = strlen($text);

        if ($text_length > $length) {
            return substr($text, 0, $length) . '...';
        }

        return $text;
    }

    /**
     * Same as the excerpt method but with the string reversed.
     *
     * @param  string  $text   String of characters that will be cut.
     * @param  integer $length Maximum length of the returned string, default is 10.
     * @return string          Short version of the text specified.
     */
    public static function excerptReverse($text = '', $length = 10)
    {
        $str_reversed = strrev($text);
        $str_excerpt = self::excerpt($str_reversed, $length);
        $text_transformed = strrev($str_excerpt);

        return $text_transformed;
    }

    /**
     * Check whether an list is a multidimensional array or not.
     *
     * @param  array   $list An array or multidimensional array of different values.
     * @return boolean       TRUE if the list is multidimensional, FALSE otherwise.
     */
    public static function isMultiList($list = array())
    {
        if (!empty($list)) {
            foreach ((array) $list as $item) {
                if (is_array($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Join array elements with a string no matter if it is multidimensional.
     *
     * @param  string $separator Character that will act as a separator, default to an empty string.
     * @param  array  $list      The array of strings to implode.
     * @return string            String of all the items in the list, with the separator between them.
     */
    public static function implode($separator = '', $list = array())
    {
        if (self::isMultiList($list)) {
            $pieces = array();

            foreach ($list as $items) {
                $pieces[] = @implode($separator, $items);
            }

            $joined_pieces = '(' . implode('), (', $pieces) . ')';

            return $joined_pieces;
        } else {
            return implode($separator, $list);
        }
    }

    /**
     * Check whether the site is running over the Nginx web server.
     *
     * @return boolean TRUE if the site is running over Nginx, FALSE otherwise.
     */
    public static function isNginxServer()
    {
        return (bool) preg_match('/^nginx(\/[0-9\.]+)?$/', @$_SERVER['SERVER_SOFTWARE']);
    }

    /**
     * Check whether the site is running over the Nginx web server.
     *
     * @return boolean TRUE if the site is running over Nginx, FALSE otherwise.
     */
    public static function isIISServer()
    {
        return (bool) preg_match('/Microsoft-IIS/i', @$_SERVER['SERVER_SOFTWARE']);
    }

    /**
     * Returns list of supported languages.
     *
     * @return array Supported languages abbreviated.
     */
    public static function languages()
    {
        return array(
            'af' => 'af',
            'ak' => 'ak',
            'sq' => 'sq',
            'arq' => 'arq',
            'am' => 'am',
            'ar' => 'ar',
            'hy' => 'hy',
            'rup_MK' => 'rup_MK',
            'frp' => 'frp',
            'as' => 'as',
            'az' => 'az',
            'az_TR' => 'az_TR',
            'bcc' => 'bcc',
            'ba' => 'ba',
            'eu' => 'eu',
            'bel' => 'bel',
            'bn_BD' => 'bn_BD',
            'bs_BA' => 'bs_BA',
            'bre' => 'bre',
            'bg_BG' => 'bg_BG',
            'ca' => 'ca',
            'bal' => 'bal',
            'zh_CN' => 'zh_CN',
            'zh_HK' => 'zh_HK',
            'zh_TW' => 'zh_TW',
            'co' => 'co',
            'hr' => 'hr',
            'cs_CZ' => 'cs_CZ',
            'da_DK' => 'da_DK',
            'dv' => 'dv',
            'nl_NL' => 'nl_NL',
            'nl_BE' => 'nl_BE',
            'dzo' => 'dzo',
            'en_US' => 'en_US',
            'en_AU' => 'en_AU',
            'en_CA' => 'en_CA',
            'en_ZA' => 'en_ZA',
            'en_GB' => 'en_GB',
            'eo' => 'eo',
            'et' => 'et',
            'fo' => 'fo',
            'fi' => 'fi',
            'fr_BE' => 'fr_BE',
            'fr_CA' => 'fr_CA',
            'fr_FR' => 'fr_FR',
            'fy' => 'fy',
            'fuc' => 'fuc',
            'gl_ES' => 'gl_ES',
            'ka_GE' => 'ka_GE',
            'de_DE' => 'de_DE',
            'de_CH' => 'de_CH',
            'el' => 'el',
            'gn' => 'gn',
            'gu' => 'gu',
            'haw_US' => 'haw_US',
            'haz' => 'haz',
            'he_IL' => 'he_IL',
            'hi_IN' => 'hi_IN',
            'hu_HU' => 'hu_HU',
            'is_IS' => 'is_IS',
            'ido' => 'ido',
            'id_ID' => 'id_ID',
            'ga' => 'ga',
            'it_IT' => 'it_IT',
            'ja' => 'ja',
            'jv_ID' => 'jv_ID',
            'kab' => 'kab',
            'kn' => 'kn',
            'kk' => 'kk',
            'km' => 'km',
            'kin' => 'kin',
            'ky_KY' => 'ky_KY',
            'ko_KR' => 'ko_KR',
            'ckb' => 'ckb',
            'lo' => 'lo',
            'lv' => 'lv',
            'li' => 'li',
            'lin' => 'lin',
            'lt_LT' => 'lt_LT',
            'lb_LU' => 'lb_LU',
            'mk_MK' => 'mk_MK',
            'mg_MG' => 'mg_MG',
            'ms_MY' => 'ms_MY',
            'ml_IN' => 'ml_IN',
            'mri' => 'mri',
            'mr' => 'mr',
            'xmf' => 'xmf',
            'mn' => 'mn',
            'me_ME' => 'me_ME',
            'my_MM' => 'my_MM',
            'ne_NP' => 'ne_NP',
            'nb_NO' => 'nb_NO',
            'nn_NO' => 'nn_NO',
            'oci' => 'oci',
            'ory' => 'ory',
            'os' => 'os',
            'ps' => 'ps',
            'fa_IR' => 'fa_IR',
            'fa_AF' => 'fa_AF',
            'pl_PL' => 'pl_PL',
            'pt_BR' => 'pt_BR',
            'pt_PT' => 'pt_PT',
            'pa_IN' => 'pa_IN',
            'rhg' => 'rhg',
            'ro_RO' => 'ro_RO',
            'roh' => 'roh',
            'ru_RU' => 'ru_RU',
            'ru_UA' => 'ru_UA',
            'rue' => 'rue',
            'sah' => 'sah',
            'sa_IN' => 'sa_IN',
            'srd' => 'srd',
            'gd' => 'gd',
            'sr_RS' => 'sr_RS',
            'szl' => 'szl',
            'sd_PK' => 'sd_PK',
            'si_LK' => 'si_LK',
            'sk_SK' => 'sk_SK',
            'sl_SI' => 'sl_SI',
            'so_SO' => 'so_SO',
            'azb' => 'azb',
            'es_AR' => 'es_AR',
            'es_CL' => 'es_CL',
            'es_CO' => 'es_CO',
            'es_MX' => 'es_MX',
            'es_PE' => 'es_PE',
            'es_PR' => 'es_PR',
            'es_ES' => 'es_ES',
            'es_VE' => 'es_VE',
            'su_ID' => 'su_ID',
            'sw' => 'sw',
            'sv_SE' => 'sv_SE',
            'gsw' => 'gsw',
            'tl' => 'tl',
            'tg' => 'tg',
            'tzm' => 'tzm',
            'ta_IN' => 'ta_IN',
            'ta_LK' => 'ta_LK',
            'tt_RU' => 'tt_RU',
            'te' => 'te',
            'th' => 'th',
            'bo' => 'bo',
            'tir' => 'tir',
            'tr_TR' => 'tr_TR',
            'tuk' => 'tuk',
            'ug_CN' => 'ug_CN',
            'uk' => 'uk',
            'ur' => 'ur',
            'uz_UZ' => 'uz_UZ',
            'vi' => 'vi',
            'wa' => 'wa',
            'cy' => 'cy',
        );
    }
}
