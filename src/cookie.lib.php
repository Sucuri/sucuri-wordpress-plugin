<?php

/**
 * Code related to the cookie.lib.php interface.
 *
 * Lightweight, defensive cookie accessor.
 *
 * Centralizes normalization, filtering and setting/deleting cookies to avoid
 * ad-hoc inline helpers across the codebase.
 *
 * PHP version 5+
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 */

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Cookie handler abstraction.
 *
 * Provides a tiny API:
 *  - get($name, $default = '')  : Safely fetch a cookie value (filtered)
 *  - has($name)                 : Whether a cookie exists (and passes name filter)
 *  - set($name, $value, $ttl)   : Set a cookie with secure defaults
 *  - delete($name)              : Expire a cookie client-side
 *
 * Security Notes:
 *  - Name is restricted to a conservative character set to prevent header
 *    injection or unusual delimiters.
 *  - Value is filtered through a whitelist pattern before being returned.
 *  - Returned value is NEVER automatically escaped; caller context decides.
 *  - set() enforces HttpOnly and Secure (when site is served over HTTPS) to
 *    reduce XSS impact and passive interception risk.
 */
class SucuriScanCookie extends SucuriScan
{
    const NAME_PATTERN = '/^[A-Za-z0-9._-]{1,64}$/';
    const VALUE_PATTERN = '/^[A-Za-z0-9._-]{0,128}$/';
    const MAX_TTL = 31536000; // 365 * 24 * 3600

    /**
     * Determine if current request is being served over HTTPS.
     * Uses WordPress is_ssl() when available; otherwise falls back to server vars.
     *
     * @return bool
     */
    private static function isSecure()
    {
        if (function_exists('is_ssl')) {
            return is_ssl();
        }

        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    }

    /**
     * Normalize and validate cookie name.
     *
     * @param string $name Raw cookie name.
     *
     * @return string Normalized name or empty string if invalid.
     */
    private static function normalizeName($name = '')
    {
        if (!is_string($name)) {
            return '';
        }

        $name = trim($name);

        if ($name === '' || !@preg_match(self::NAME_PATTERN, $name)) {
            return '';
        }

        return $name;
    }

    /**
     * Sanitize value by pattern; return empty string if invalid.
     *
     * @param string $value Raw cookie value.
     *
     * @return string Filtered value or empty string.
     */
    private static function filterValue($value = '')
    {
        if (!is_string($value)) {
            return '';
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (!@preg_match(self::VALUE_PATTERN, $value)) {
            return '';
        }

        return self::escape($value);
    }

    /**
     * Whether a cookie exists with a valid normalized name.
     *
     * @param string $name Cookie name.
     *
     * @return bool True if present (raw existence), regardless of value filtering.
     */
    public static function has($name)
    {
        $name = self::normalizeName($name);

        return ($name !== '' && isset($_COOKIE[$name]));
    }

    /**
     * Get a cookie value (filtered) or default if missing / invalid.
     *
     * @param string $name Cookie name.
     * @param string $default Fallback value.
     *
     * @return string Filtered cookie value or default.
     */
    public static function get($name, $default = '')
    {
        $name = self::normalizeName($name);

        if ($name === '' || !isset($_COOKIE[$name])) {
            return $default;
        }

        $filtered = self::filterValue((string) $_COOKIE[$name]);

        return ($filtered === '' && $_COOKIE[$name] !== '') ? $default : $filtered;
    }

    /**
     * Set a cookie with secure defaults (HttpOnly, Secure when possible, Lax policy).
     *
     * @param string $name Cookie name.
     * @param string $value Cookie value (will be filtered before storage; if invalid becomes empty string).
     * @param int $ttl Lifetime in seconds (0 => session cookie).
     * @param string $path Path scope.
     * @param string $domain Domain scope (auto-detect if empty).
     * @param string $sameSite SameSite policy (None|Lax|Strict). Lower PHP versions ignore; gracefully degrade.
     * 
     * @return bool True if the cookie header was successfully set.
     */
    public static function set($name, $value, $ttl = 0, $path = '/', $domain = '', $sameSite = 'Lax')
    {
        $name = self::normalizeName($name);

        if ($name === '') {
            return false;
        }

        $ttl = (int) $ttl;

        if ($ttl < 0) {
            $ttl = 0;
        }

        if ($ttl > self::MAX_TTL) {
            $ttl = self::MAX_TTL;
        }

        $value = self::filterValue((string) $value);
        $expire = ($ttl > 0) ? (time() + $ttl) : 0;

        $secure = self::isSecure();
        $httpOnly = true;

        if (is_string($sameSite) && strtolower($sameSite) === 'none') {
            $secure = true;
        }

        $supportsArray = version_compare(PHP_VERSION, '7.3', '>=');

        if ($supportsArray) {
            $validSameSite = in_array($sameSite, array('None', 'Lax', 'Strict'), true) ? $sameSite : 'Lax';

            return setcookie($name, $value, array(
                'expires' => $expire,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => $validSameSite,
            ));
        }

        return setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }

    /**
     * Delete (expire) a cookie client-side.
     *
     * @param string $name Cookie name.
     * @param string $path Path scope.
     * @param string $domain Domain scope.
     *
     * @return bool True if header set.
     */
    public static function delete($name, $path = '/', $domain = '')
    {
        $name = self::normalizeName($name);

        if ($name === '') {
            return false;
        }

        if (isset($_COOKIE[$name])) {
            unset($_COOKIE[$name]);
        }

        $secure = self::isSecure();

        return setcookie($name, '', time() - 3600, $path, $domain, $secure, true);
    }
}
