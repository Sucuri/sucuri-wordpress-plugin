<?php

/**
 * Code related to the request.lib.php interface.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2017 Sucuri Inc.
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
 * HTTP request handler.
 *
 * Function definitions to retrieve, validate, and clean the parameters during a
 * HTTP request, generally after a form submission or while loading a URL. Use
 * these methods at most instead of accessing an index in the global PHP
 * variables _POST, _GET, _REQUEST since they may come with insecure data.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2017 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanRequest extends SucuriScan
{
    /**
     * Returns the value of the _GET, _POST or _REQUEST key.
     *
     * You can pass an additional parameter to execute a regular expression that
     * will return False if the value doesn't matches what the RegExp defined.
     * Very useful to whitelist user input besides form validations.
     *
     * @param  array  $list    The array where the specified key will be searched.
     * @param  string $key     Name of the variable contained in _POST.
     * @param  string $pattern Optional pattern to match allowed values.
     * @return array|string|bool Value from the global _GET or _POST variable.
     */
    private static function request($list = array(), $key = '', $pattern = '')
    {
        $key = self::varPrefix((string) $key);

        if (!is_array($list) || !isset($list[$key])) {
            return false;
        }

        $key_value = $list[$key]; /* raw request parameter */

        /* if the request data is an array, then only cast the value. */
        if ($pattern === '_array' && is_array($key_value)) {
            return (array) $key_value;
        }

        /* match WordPress nonce */
        if ($pattern === '_nonce') {
            $pattern = '[a-z0-9]{10}';
        }

        /* match valid page identifier */
        if ($pattern === '_page') {
            $pattern = '[a-z_]+';
        }

        /* match every data format */
        if ($pattern === '') {
            $pattern = '.*';
        }

        /* check the format of the request data with a regex defined above. */
        if (@preg_match('/^' . $pattern . '$/', $key_value)) {
            return self::escape($key_value);
        }

        return false;
    }

    /**
     * Returns the value stored in a specific index in the global _GET variable,
     * you can specify a pattern as the second argument to match allowed values.
     *
     * @param  string $key     Name of the variable contained in _GET.
     * @param  string $pattern Optional pattern to match allowed values.
     * @return array|string    Value from the global _GET variable.
     */
    public static function get($key = '', $pattern = '')
    {
        return self::request($_GET, $key, $pattern);
    }

    /**
     * Returns the value stored in a specific index in the global _POST variable,
     * you can specify a pattern as the second argument to match allowed values.
     *
     * @param  string $key     Name of the variable contained in _POST.
     * @param  string $pattern Optional pattern to match allowed values.
     * @return array|string    Value from the global _POST variable.
     */
    public static function post($key = '', $pattern = '')
    {
        return self::request($_POST, $key, $pattern);
    }

    /**
     * Returns the value stored in a specific index in the global _REQUEST variable,
     * you can specify a pattern as the second argument to match allowed values.
     *
     * @param  string $key     Name of the variable contained in _REQUEST.
     * @param  string $pattern Optional pattern to match allowed values.
     * @return array|string    Value from the global _REQUEST variable.
     */
    public static function getOrPost($key = '', $pattern = '')
    {
        return self::request($_REQUEST, $key, $pattern);
    }
}
