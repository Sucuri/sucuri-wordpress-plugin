<?php

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
 */
class SucuriScanRequest extends SucuriScan
{
    /**
     * Returns the value stored in a specific index in the global _GET, _POST or
     * _REQUEST variables, you can specify a pattern as the second argument to
     * match allowed values.
     *
     * @param array $list The array where the specified key will be searched.
     * @param string $key Name of the variable contained in _POST.
     * @param string $pattern Optional pattern to match allowed values.
     * @return array|string Value from the global _GET or _POST variable.
     */
    public static function request($list = array(), $key = '', $pattern = '')
    {
        $key = self::varPrefix($key);

        if (is_array($list) && is_string($key) && isset($list[$key])) {
            // Select the key from the list and escape its content.
            $key_value = $list[ $key ];

            // Define regular expressions for specific value types.
            if ($pattern === '') {
                $pattern = '/.*/';
            } else {
                switch ($pattern) {
                    case '_nonce':
                        $pattern = '/^[a-z0-9]{10}$/';
                        break;
                    case '_page':
                        $pattern = '/^[a-z_]+$/';
                        break;
                    case '_array':
                        $pattern = '_array';
                        break;
                    case '_yyyymmdd':
                        $pattern = '/^[0-9]{4}(\-[0-9]{2}) {2}$/';
                        break;
                    default:
                        $pattern = '/^'.$pattern.'$/';
                        break;
                }
            }

            // If the request data is an array, then only cast the value.
            if ($pattern == '_array' && is_array($key_value)) {
                return (array) $key_value;
            }

            // Check the format of the request data with a regex defined above.
            if (@preg_match($pattern, $key_value)) {
                return self::escape($key_value);
            }
        }

        return '' /* empty value */;
    }

    /**
     * Returns the value stored in a specific index in the global _GET variable,
     * you can specify a pattern as the second argument to match allowed values.
     *
     * @param string $key Name of the variable contained in _GET.
     * @param string $pattern Optional pattern to match allowed values.
     * @return array|string Value from the global _GET variable.
     */
    public static function get($key = '', $pattern = '')
    {
        return self::request($_GET, $key, $pattern);
    }

    /**
     * Returns the value stored in a specific index in the global _POST variable,
     * you can specify a pattern as the second argument to match allowed values.
     *
     * @param string $key Name of the variable contained in _POST.
     * @param string $pattern Optional pattern to match allowed values.
     * @return array|string Value from the global _POST variable.
     */
    public static function post($key = '', $pattern = '')
    {
        return self::request($_POST, $key, $pattern);
    }

    /**
     * Returns the value stored in a specific index in the global _REQUEST variable,
     * you can specify a pattern as the second argument to match allowed values.
     *
     * @param string $key Name of the variable contained in _REQUEST.
     * @param string $pattern Optional pattern to match allowed values.
     * @return array|string Value from the global _REQUEST variable.
     */
    public static function getOrPost($key = '', $pattern = '')
    {
        return self::request($_REQUEST, $key, $pattern);
    }
}
