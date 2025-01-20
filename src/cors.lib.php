<?php
/**
 * Code related to the CORS (Cross-Origin Resource Sharing) headers settings.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 */

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * CORS headers library.
 *
 * This class is responsible for setting the CORS headers based on the user's settings.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 */
class SucuriScanCORSHeaders extends SucuriScan
{
    /**
     * Sets the CORS headers according to the stored plugin options.
     *
     * @return void
     */
    public function setCORSHeaders()
    {
        if (headers_sent()) {
            return;
        }

        $corsMode = SucuriScanOption::getOption(':headers_cors');
        if ($corsMode === 'disabled') {
            return;
        }

        $corsOptions = SucuriScanOption::getOption(':headers_cors_options');

        if (!is_array($corsOptions)) {
            $corsOptions = array();
        }

        foreach ($corsOptions as $directive => $option) {
            // If the directive is not enforced, skip
            if (!isset($option['enforced']) || !$option['enforced']) {
                continue;
            }

            // Collect the directive’s raw value (may be from 'value' or multi_checkbox 'options')
            $rawValue = $this->collectDirectiveValue($option);

            if (empty($rawValue)) {
                continue;
            }

            switch ($directive) {
                case 'Access-Control-Allow-Origin':
                    $cleanValue = $this->sanitizeSimpleValue($rawValue);

                    if (!empty($cleanValue)) {
                        header('Access-Control-Allow-Origin: ' . $cleanValue);
                    }

                    break;

                case 'Access-Control-Expose-Headers':
                    $csv = $this->sanitizeCommaSeparatedValue($rawValue);

                    if (!empty($csv)) {
                        header('Access-Control-Expose-Headers: ' . $csv);
                    }

                    break;

                case 'Access-Control-Allow-Methods':
                    $methods = $this->sanitizeMultiMethodValue($rawValue);

                    if (!empty($methods)) {
                        header('Access-Control-Allow-Methods: ' . $methods);
                    }

                    break;

                case 'Access-Control-Allow-Headers':
                    $csv = $this->sanitizeCommaSeparatedValue($rawValue);

                    if (!empty($csv)) {
                        header('Access-Control-Allow-Headers: ' . $csv);
                    }

                    break;

                case 'Access-Control-Allow-Credentials':
                    if (!empty($rawValue)) {
                        header('Access-Control-Allow-Credentials: true');
                    }

                    break;

                case 'Access-Control-Max-Age':
                    $numeric = $this->sanitizeNumericValue($rawValue);

                    if (!empty($numeric)) {
                        header('Access-Control-Max-Age: ' . $numeric);
                    }

                    break;
            }
        }
    }

    /**
     * Collects a string representing the directive value:
     * If it's a normal text directive, use 'value' directly;
     * if it's a multi_checkbox directive, gather sub-options that are enforced.
     *
     * @param array $option Directive config array (type, value, options, enforced, etc.).
     *
     * @return string A space-separated list if multi_checkbox, or the text value otherwise.
     */
    protected function collectDirectiveValue($option)
    {
        if (isset($option['type']) && $option['type'] === 'multi_checkbox') {
            if (!isset($option['options']) || !is_array($option['options'])) {
                return '';
            }

            $subTokens = array();

            foreach ($option['options'] as $token => $tokenObj) {
                if ($tokenObj['enforced']) {
                    $subTokens[] = $token;
                }
            }

            return implode(' ', $subTokens);
        }

        if (isset($option['value']) && is_string($option['value'])) {
            return trim($option['value']);
        }

        return '';
    }

    /**
     * Removes HTML tags, replaces newlines, and trims whitespace.
     *
     * @param string $value The raw input string.
     *
     * @return string The cleaned string (could be empty).
     */
    protected function sanitizeSimpleValue($value)
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\r\n]+/', ' ', $value);

        return trim($value);
    }

    /**
     * Splits a comma-delimited string into tokens, sanitizes each to valid
     * header token characters, and rejoins them with a comma.
     *
     * @param string $rawValue The raw input string.
     *
     * @return string The cleaned, comma-separated string.
     */
    protected function sanitizeCommaSeparatedValue($rawValue)
    {
        $rawValue = $this->sanitizeSimpleValue($rawValue);

        if (empty($rawValue)) {
            return '';
        }

        $tokens = preg_split('/\s*,\s*/', $rawValue, -1, PREG_SPLIT_NO_EMPTY);
        $final = array();

        foreach ($tokens as $token) {
            $token = $this->sanitizeHeaderToken($token);

            if (!empty($token)) {
                $final[] = $token;
            }
        }

        return implode(', ', $final);
    }

    /**
     * Splits a space-delimited string of HTTP methods, e.g. "GET POST OPTIONS",
     * and returns them as uppercase comma-separated tokens.
     *
     * @param string $rawValue The raw input string.
     *
     * @return string The cleaned, comma-separated string of uppercase methods.
     */
    protected function sanitizeMultiMethodValue($rawValue)
    {
        $rawValue = $this->sanitizeSimpleValue($rawValue);

        if (empty($rawValue)) {
            return '';
        }

        $tokens = preg_split('/\s+/', $rawValue, -1, PREG_SPLIT_NO_EMPTY);
        $final = array();

        foreach ($tokens as $t) {
            $t = strtoupper(preg_replace('/[^A-Z]/', '', $t));

            if (!empty($t)) {
                $final[] = $t;
            }
        }

        return implode(', ', $final);
    }

    /**
     * Restricts characters to typical token chars from RFC 7230:
     * ^[!#$%&'*+-.^_`|~0-9A-Za-z]+$
     *
     * @param string $value Potential header token.
     *
     * @return string Cleaned header token (could be empty).
     */
    protected function sanitizeHeaderToken($value)
    {
        $value = preg_replace("/[^!#$%&'*+\-.\^_`|~0-9A-Za-z]/", '', $value);

        return trim($value);
    }

    /**
     * For numeric-only directives like Access-Control-Max-Age.
     *
     * @param string $rawValue The raw input string.
     *
     * @return string Digits only (could be empty).
     */
    protected function sanitizeNumericValue($rawValue)
    {
        $cleaned = $this->sanitizeSimpleValue($rawValue);
        $digitsOnly = preg_replace('/\D/', '', $cleaned);

        return trim($digitsOnly);
    }
}
