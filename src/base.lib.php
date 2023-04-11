<?php

/**
 * Code related to the base.lib.php interface.
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
 * Miscellaneous library.
 *
 * Multiple and generic methods that will be used through out the code of other
 * libraries extending from this and methods defined in other files, be aware of
 * the hierarchy and check the other libraries for duplicated methods.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScan
{
    /**
     * Throw generic exception instead of silent failure for unit-tests.
     *
     * @throws Exception
     *
     * @param  string $message Error or information message.
     * @param  string $type    Either info or error.
     * @return bool            False all the time.
     */
    public static function throwException($message, $type = 'error')
    {
        if (defined('SUCURISCAN_THROW_EXCEPTIONS')
            && SUCURISCAN_THROW_EXCEPTIONS === true
            && is_string($message)
        ) {
            $code = ($type === 'error' ? 157 : 333);
            $message = str_replace(
                SUCURISCAN_ADMIN_NOTICE_PREFIX,
                ($type === 'error' ? __('Error:', 'sucuri-scanner') : __('Info:', 'sucuri-scanner')),
                $message
            );

            /* throw catchable errors via tests */
            throw new Exception($message, $code);
        }

        return false; /* for backward compatibility */
    }

    /**
     * Return name of a variable with the plugin's prefix (if needed).
     *
     * To facilitate the development, you can prefix the name of the key in the
     * request (when accessing it) with a single colon, this method will auto-
     * matically replace that character with the unique plugin ID.
     *
     * @param  string $name Text with optional colon prefix.
     * @return string       Real variable name.
     */
    public static function varPrefix($name = '')
    {
        if (!empty($name) && $name[0] === ':') {
            return sprintf(
                '%s_%s',
                SUCURISCAN,
                substr($name, 1)
            );
        }

        return $name;
    }

    /**
     * Gets the value of a configuration option.
     *
     * @param  string $property The configuration option name.
     * @param  bool   $raw      Return the original value from the php.ini file.
     * @return string           Value of the option as a string on success.
     */
    public static function iniGet($property = '', $raw = false)
    {
        $ini_value = ini_get($property);

        if ($raw) {
            return $ini_value;
        }

        $default = array(
            'error_log' => 'error_log',
            'safe_mode' => 'not active',
            'memory_limit' => '128M',
            'upload_max_filesize' => '2M',
            'post_max_size' => '8M',
            'max_execution_time' => '30',
            'max_input_time' => '-1',
        );

        if ($ini_value === false) {
            $ini_value = 'unknown';
        } elseif (empty($ini_value) || $ini_value === null) {
            if (array_key_exists($property, $default)) {
                $ini_value = $default[$property];
            } else {
                $ini_value = 'not active';
            }
        }

        if ($property == 'error_log') {
            $ini_value = basename($ini_value);
        }

        return $ini_value;
    }

    /**
     * Encodes the less-than, greater-than, ampersand, double quote and single
     * quote characters, will never double encode entities.
     *
     * @see https://developer.wordpress.org/reference/functions/esc_attr/
     *
     * @param  string $text The text which is to be encoded.
     * @return string       The encoded text with HTML entities.
     */
    public static function escape($text = '')
    {
        return esc_attr($text);
    }

    /**
     * Translate a given number in bytes to a human readable file size using the
     * a approximate value in Kylo, Mega, Giga, etc.
     *
     * @see https://www.php.net/manual/en/function.filesize.php#106569
     *
     * @param  int $bytes    Integer representing a file size in bytes.
     * @param  int $decimals How many decimals should be returned.
     * @return string        Human readable representation of the given number.
     */
    public static function humanFileSize($bytes = 0, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = (int) floor((strlen((string) $bytes) - 1) / 3);
        $number = $bytes / pow(1024, $factor);
        $result = sprintf("%.{$decimals}f", $number) . @$sz[$factor];
        $zeroes = '.' . str_repeat('0', $decimals);
        $result = str_replace($zeroes, '', $result);

        return $result;
    }

    /**
     * Returns the human version of the time difference.
     *
     * If the timestamp is in the past in comparison with the current time, it
     * will return a string in the form of "X time ago". If the timestamp is in
     * the future in comparison with the current time, it will return a string
     * in the form of "in X time". If the timestamp is the same as the current
     * time it will return "right now".
     *
     * @param  int $time Unix timestamp.
     * @return string    Different between timestamp and current time.
     */
    public static function humanTime($time = 0)
    {
        if (!is_numeric($time)) {
            return 'N/A';
        }

        $now = time();

        if ($time === $now) {
            return 'right now';
        }

        $result = '';
        $template = '';
        $diff = $now - $time;
        $groups = array(
            31536000 => 'year',
            2592000 => 'month',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second',
        );

        if ($time < $now) {
            $template = '%d %s ago';
        } else {
            $template = 'in %d %s';
        }

        foreach ($groups as $secs => $label) {
            $distance = abs($diff / $secs);

            if ($distance >= 1) {
                $plural = (round($distance) == 1) ? '' : 's';
                $result = sprintf(
                    $template,
                    round($distance),
                    $label . $plural
                );
                break;
            }
        }

        return $result;
    }

    /**
     * Check if the admin init hook must not be intercepted.
     *
     * @return bool True if the admin init hook must not be intercepted.
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
     * @return bool True if the admin init hook must be intercepted.
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
     * @param  string $path Directory path to fix.
     * @return string       Fixed file path.
     */
    public static function fixPath($path = '')
    {
        $new = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        $new = rtrim($new, '/'); /* purge right side. */

        return empty($new) ? '/' : $new;
    }

    /**
     * Returns the full path to a specific directory or file.
     *
     * @param  string $path Path that needs to be completed.
     * @return string       Full path including the base directory.
     */
    public static function dataStorePath($path = '')
    {
        $folder = WP_CONTENT_DIR . '/uploads/sucuri';

        /* custom path no matter its existence */
        if (defined('SUCURI_DATA_STORAGE')) {
            $folder = SUCURI_DATA_STORAGE;
        }

        $fullpath = self::fixPath($folder . '/' . $path);
        $fullpath = str_replace('//', '/', $fullpath);
        $fullpath = rtrim($fullpath, '/');

        return $fullpath;
    }

    /**
     * Check whether the current site is working as a multi-site instance.
     *
     * @return bool Either TRUE or FALSE in case WordPress is being used as a multi-site instance.
     */
    public static function isMultiSite()
    {
        return (bool) (function_exists('is_multisite') && is_multisite());
    }

    /**
     * Returns an URL from the admin dashboard.
     *
     * @param  string $url Optional trailing of the URL.
     * @return string      Full valid URL from the admin dashboard.
     */
    public static function adminURL($url = '')
    {
        if (self::isMultiSite()) {
            // @codeCoverageIgnoreStart
            return network_admin_url($url);
            // @codeCoverageIgnoreEnd
        }

        return admin_url($url);
    }

    /**
     * Find and retrieve the current version of WordPress installed.
     *
     * @return string The version number of WordPress installed.
     */
    public static function siteVersion()
    {
        if (isset($GLOBALS['wp_version'])) {
            return self::escape($GLOBALS['wp_version']);
        }

        $wp_version = '';
        $filename = ABSPATH . '/' . WPINC . '/version.php';
        $lines = SucuriScanFileInfo::fileLines($filename);

        foreach ($lines as $line) {
            if (strpos($line, '$wp_version') === 0) {
                $version = str_replace("\x20", '', $line);
                $index = strpos($version, "'");
                $version = substr($version, $index + 1);
                $index = strpos($version, "'");
                $version = substr($version, 0, $index);
                $wp_version = $version;
                break;
            }
        }

        return self::escape($wp_version);
    }

    /**
     * Find and retrieve the absolute path of the WordPress configuration file.
     *
     * @return string|bool Absolute path of the WordPress configuration file.
     */
    public static function getConfigPath()
    {
        $filename = ABSPATH . '/wp-config.php';

        /* check one directory up */
        if (!file_exists($filename)) {
            $filename = ABSPATH . '/../wp-config.php';
        }

        return @realpath($filename);
    }

    /**
     * Find and retrieve the absolute path of the main WordPress htaccess file.
     *
     * @return string Absolute path of the main WordPress htaccess file.
     */
    public static function getHtaccessPath()
    {
        $result = '';
        $base_dirs = array(
            rtrim(ABSPATH, '/'),
            dirname(ABSPATH),
            dirname(dirname(ABSPATH)),
        );

        foreach ($base_dirs as $base_dir) {
            $htaccess_path = sprintf('%s/.htaccess', $base_dir);

            if (file_exists($htaccess_path)) {
                $result = $htaccess_path;
                break;
            }
        }

        return $result;
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
        SucuriScanEvent::reportSiteVersion();
        SucuriScanIntegrity::getIntegrityStatus(true);
        SucuriScanSettingsPosthack::availableUpdatesContent(true);
        SucuriScanEvent::sendLogsFromQueue(); /* blocking; keep at the end */
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
     * @param  bool $with_keys Return the array with its values are keys.
     * @return array           Allowed HTTP headers to retrieve real IP.
     */
    public static function allowedHttpHeaders($with_keys = false)
    {
        $allowed = array(
            /* Sucuri custom HTTP headers */
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
     * The list of HTTP headers is ordered per relevancy, and having the main
     * HTTP header as the first entry, this guarantees that the IP address of
     * the visitors will be retrieved from the HTTP header chosen by the user
     * first and fallback to the other alternatives if available.
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
     * @param  bool $with_header Return HTTP header where the IP address was found.
     * @return string            Real IP address of the user in the current request.
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

        return 'Mozilla/5.0 (KHTML, like Gecko) Safari/537.36';
    }

    /**
     * Get the clean version of the current domain.
     *
     * @see https://developer.wordpress.org/reference/functions/get_site_url/
     *
     * @param  bool $return_tld Returns the top-level domain instead.
     * @return string           The domain of the current site.
     */
    public static function getDomain($return_tld = false)
    {
        $site_url = get_site_url();
        $pattern = '/([fhtps]+:\/\/)?([^:\/]+)(:[0-9:]+)?(\/.*)?/';
        $replacement = ($return_tld === true) ? '$2' : '$2$3$4';
        $domain_name = @preg_replace($pattern, $replacement, $site_url);

        return $domain_name;
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
     * @return bool TRUE if reverse proxies must be supported, FALSE otherwise.
     */
    public static function supportReverseProxy()
    {
        return SucuriScanOption::isEnabled(':revproxy');
    }

    /**
     * Checks the existence of the HTTP_X_SUCURI_CLIENTIP header in the request headers
     *
     * Once active, the Sucuri Firewall sends custom headers to the server with information
     * about the original request.
     *
     * @return boolean True if the website is being reached with our HTTP_X_SUCURI_CLIENTIP header.
     */
    private static function hasSucuriClientIPHeader()
    {
        return array_key_exists('HTTP_X_SUCURI_CLIENTIP', $_SERVER);
    }

    /**
     * Checks whether the site is behind the firewall network.
     *
     * @param  bool $verbose Return array with HTTP and HOST information.
     * @return array|bool    True if the firewall is in use, false otherwise.
     */
    public static function isBehindFirewall($verbose = false)
    {
        if (!$verbose) {
            return (bool) self::hasSucuriClientIPHeader();
        }

        $http_host = self::getTopLevelDomain();
        $host_by_addr = @gethostbyname($http_host);
        $host_by_name = @gethostbyaddr($host_by_addr);

        return array(
            'status' => self::hasSucuriClientIPHeader(),
            'http_host' => self::getTopLevelDomain(),
            'host_name' => $host_by_name,
            'host_addr' => $host_by_addr,
        );
    }

    /**
     * Get the email address set by the administrator to receive the alerts
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

        return 'noreply@example.org';
    }

    /**
     * Get user data by field and data.
     *
     * @see https://developer.wordpress.org/reference/functions/get_user_by/
     *
     * @param  int $identifier User account identifier.
     * @return array           WordPress user object with data.
     */
    public static function getUserByID($identifier = 0)
    {
        return get_user_by('id', $identifier);
    }

    /**
     * Retrieve a list of all admin user accounts.
     *
     * @see https://developer.wordpress.org/reference/functions/get_users/
     *
     * @return array|bool List of admin users, false otherwise.
     */
    public static function getAdminUsers()
    {
        return get_users(array('role' => 'administrator'));
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
     * Retrieve the date in localized format, based on timestamp.
     *
     * If the locale specifies the locale month and weekday, then the locale will
     * take over the format for the date. If it isn't, then the date format string
     * will be used instead.
     *
     * @param  null|string|int $timestamp Unix timestamp.
     * @param  string          $format    Optional format for the date and time.
     * @return string                     The date, translated if locale specifies it.
     */
    public static function datetime($timestamp = null, $format = null)
    {
        $diff = 0; /* use server time */
        $tz = SucuriScanOption::getOption(':timezone');
        $length = strlen($tz); /* example: UTC+12.75 */

        if ($timestamp === null) {
            $timestamp = time();
        }

        if ($length === 9
            && substr($tz, 0, 3) === 'UTC'
            && ($tz[3] === '-' || $tz[3] === '+')
            && $tz[6] === '.'
        ) {
            $hour = (float) substr($tz, 4);
            $sign = ($tz[3] === '-') ? -1 : +1;
            $diff = ($hour * $sign);
        } else {
            /* reset; value seems to be corrupt */
            SucuriScanOption::deleteOption(':timezone');
        }

        if ($format === null) {
            $format = sprintf(
                "%s\x20%s",
                SucuriScanOption::getOption('date_format'),
                SucuriScanOption::getOption('time_format')
            );
        }

        return date($format, (intval($timestamp) + ($diff * 3600)));
    }

    /**
     * Check whether an IP address has a valid format or not.
     *
     * @param  string $remote_addr The host IP address.
     * @return bool                Whether the IP address specified is valid or not.
     */
    public static function isValidIP($remote_addr = '')
    {
        return (bool) @filter_var($remote_addr, FILTER_VALIDATE_IP);
    }


    /**
     * Check whether an IP address is formatted as CIDR or not.
     *
     * @param  string $remote_addr The supposed ip address that will be checked.
     * @return bool                Either TRUE or FALSE if the ip address specified is valid or not.
     */
    public static function isValidCIDR($remote_addr = '')
    {
        if (!is_string($remote_addr)) {
            return false;
        }

        if (preg_match('/^([0-9\.]{7,15})\/(8|16|24)$/', $remote_addr, $match)) {
            return self::isValidIP($match[1]);
        }

        return false;
    }

    /**
     * Separate the parts of an IP address.
     *
     * @param  string $remote_addr The supposed ip address that will be formatted.
     * @return array|bool          Clean address, CIDR range, and CIDR format; FALSE otherwise.
     */
    public static function getIPInfo($remote_addr = '')
    {
        if ($remote_addr) {
            $ip_parts = explode('/', $remote_addr);

            if (isset($ip_parts[0]) && self::isValidIP($ip_parts[0])) {
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
     * This use the native PHP method filter_var which is available in PHP >=
     * 5.2.0 if it is not found in the interpreter this method will sue regular
     * expressions to check whether the email address passed is valid or not.
     *
     * @see https://www.php.net/manual/en/function.filter-var.php
     *
     * @param  string $email The string that will be validated as an email address.
     * @return bool          TRUE if the email address passed to the method is valid, FALSE if not.
     */
    public static function isValidEmail($email = '')
    {
        return (bool) @filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Cut a long text to the length specified, and append suspensive points at the end.
     *
     * @param  string $text   String of characters that will be cut.
     * @param  int    $length Maximum length of the returned string, default is 10.
     * @return string         Short version of the text specified.
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
     * Check whether an list is a multidimensional array or not.
     *
     * @param  array $list An array or multidimensional array of different values.
     * @return bool        TRUE if the list is multidimensional, FALSE otherwise.
     */
    public static function isMultiList($list = array())
    {
        if (empty($list)) {
            return false;
        }

        $status = false;

        foreach ((array) $list as $item) {
            if (is_array($item)) {
                $status = true;
                break;
            }
        }

        return $status;
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
        if (!is_array($list)) {
            return 'INVALID_ARGS';
        }

        if (self::isMultiList($list)) {
            $pieces = array();

            foreach ($list as $items) {
                $pieces[] = @implode($separator, $items);
            }

            return '(' . implode('), (', $pieces) . ')';
        }

        return implode($separator, $list);
    }

    /**
     * Check whether the site is running over the Nginx web server.
     *
     * @return bool TRUE if the site is running over Nginx, FALSE otherwise.
     */
    public static function isNginxServer()
    {
        return (bool) (stripos(@$_SERVER['SERVER_SOFTWARE'], 'nginx') !== false);
    }

    /**
     * Check whether the site is running over the Nginx web server.
     *
     * @return bool TRUE if the site is running over Nginx, FALSE otherwise.
     */
    public static function isIISServer()
    {
        return (bool) (stripos(@$_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);
    }

    /**
     * Returns the md5 hash representing the content of a file.
     *
     * @param  string $file Relative path to the file.
     * @return string       Seven first characters in the hash of the file.
     */
    public static function fileVersion($file = '')
    {
        return substr(md5_file(SUCURISCAN_PLUGIN_PATH . '/' . $file), 0, 7);
    }

    public static function issetScanApiUrl() {
        return defined('SUCURISCAN_API_URL') && !empty(SUCURISCAN_API_URL);
    }
}
