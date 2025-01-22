<?php
/**
 * Code related to the Content Security Policy (CSP) headers settings.
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
 * Content Security Policy (CSP) headers library.
 *
 * This class is responsible for setting the CSP headers based on the user's settings.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 */
class SucuriScanCSPHeaders extends SucuriScan
{
    /**
     * Basic sanitization for CSP directive values.
     *
     * @param string $input Raw input value.
     *
     * @return string Sanitized value
     */
    public static function sanitize_csp_directive($input)
    {
        // Allow letters, numbers, spaces, hyphens, single quotes, colons, semicolons, slashes, dots, and asterisks
        return preg_replace("/[^a-zA-Z0-9\s\-\'\:;\/\.\*]/", '', $input);
    }

    /**
     * Sets the CSP headers based on the user's settings.
     *
     * @return void
     */
    public function setCSPHeaders()
    {
        if (headers_sent()) {
            // Headers are already sent; nothing to do here.
            return;
        }

        $cspMode = SucuriScanOption::getOption(':headers_csp');
        if ($cspMode === 'disabled') {
            return;
        }

        $cspOptions = SucuriScanOption::getOption(':headers_csp_options');
        if (!is_array($cspOptions)) {
            $cspOptions = array();
        }

        $cspDirectives = array();

        foreach ($cspOptions as $directive => $option) {
            // If the directive is not enforced, skip
            if (!isset($option['enforced']) || !$option['enforced']) {
                continue;
            }

            $value = $this->collectDirectiveValue($option);

            if (empty($value)) {
                continue;
            }

            $normalizedDirective = str_replace('_', '-', $directive);
            $allowedDirective = $this->getValidDirectiveOrFalse($normalizedDirective);

            if (!$allowedDirective) {
                error_log("Invalid CSP directive: $normalizedDirective");
                continue;
            }

            $sanitizedValue = $this->sanitizeDirectiveValue($allowedDirective, $value);

            if (!$sanitizedValue) {
                error_log("Invalid value for CSP directive: $normalizedDirective => $value");
                continue;
            }

            // For upgrade-insecure-requests, there's no trailing value
            if ($allowedDirective === 'upgrade-insecure-requests') {
                $cspDirectives[] = $allowedDirective;
            } else {
                $cspDirectives[] = $allowedDirective . ' ' . $sanitizedValue;
            }
        }

        if (empty($cspDirectives)) {
            return;
        }

        $cspHeaderValue = implode('; ', $cspDirectives);

        // Validate the final CSP header value
        if (preg_match('/^[a-zA-Z0-9\-\'\:;\/\.\*\s]+$/', $cspHeaderValue)) {
            header('Content-Security-Policy-Report-Only: ' . $cspHeaderValue);
            return;
        }

        error_log("Invalid CSP header value: $cspHeaderValue");
    }

    /**
     * Returns a valid CSP directive name if recognized, or false if not recognized.
     *
     * @param string $directive The normalized directive name (e.g. 'child-src').
     *
     * @return string|false
     */
    protected function getValidDirectiveOrFalse($directive)
    {
        $allowedDirectives = array(
            'base-uri',
            'child-src',
            'connect-src',
            'default-src',
            'font-src',
            'form-action',
            'frame-ancestors',
            'frame-src',
            'img-src',
            'manifest-src',
            'media-src',
            'navigate-to',
            'object-src',
            'prefetch-src',
            'report-uri',
            'report-to',
            'require-trusted-types-for',
            'sandbox',
            'script-src',
            'script-src-attr',
            'script-src-elem',
            'style-src',
            'style-src-attr',
            'style-src-elem',
            'trusted-types',
            'upgrade-insecure-requests',
            'worker-src'
        );

        return in_array($directive, $allowedDirectives) ? $directive : false;
    }

    /**
     * Validates and sanitizes the value for a given directive according to CSP rules.
     *
     * @param string $directive The CSP directive (e.g. 'sandbox', 'script-src').
     * @param string $value The raw user input or combined multi_checkbox tokens.
     *
     * @return string|false A sanitized value string if valid, false otherwise.
     */
    protected function sanitizeDirectiveValue($directive, $value)
    {
        if ($directive === 'upgrade-insecure-requests') {
            return $this->sanitizeUpgradeInsecureRequests($value);
        }

        if ($directive === 'sandbox') {
            return $this->sanitizeSandboxTokens($value);
        }

        if ($directive === 'report-uri' || $directive === 'report-to') {
            return $this->sanitizeReportUriOrTo($value);
        }

        return $this->sanitizeSourceListDirective($value);
    }

    /**
     * Handle the upgrade-insecure-requests directive.
     *
     * @param string $value
     *
     * @return string|false
     */
    protected function sanitizeUpgradeInsecureRequests($value)
    {
        $val = trim(self::sanitize_csp_directive($value));

        return ($val === 'upgrade-insecure-requests') ? 'upgrade-insecure-requests' : false;
    }

    /**
     * Handle the sandbox directive, expecting a set of allowed tokens or empty.
     *
     * @param string $value Space-separated tokens (e.g. "allow-downloads allow-forms").
     *
     * @return string
     */
    protected function sanitizeSandboxTokens($value)
    {
        $sandboxTokens = array(
            'allow-downloads',
            'allow-forms',
            'allow-modals',
            'allow-orientation-lock',
            'allow-pointer-lock',
            'allow-popups',
            'allow-popups-to-escape-sandbox',
            'allow-presentation',
            'allow-same-origin',
            'allow-scripts',
            'allow-top-navigation'
        );

        $tokens = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        $finalTokens = array();

        foreach ($tokens as $t) {
            $t = self::sanitize_csp_directive($t);

            if (in_array($t, $sandboxTokens, true)) {
                $finalTokens[] = $t;
            }
        }

        return empty($finalTokens) ? 'sandbox' : implode(' ', $finalTokens);
    }

    /**
     * Handle the report-uri and report-to directives, expecting a valid URL or scheme.
     *
     * @param string $value Raw input value (e.g. "https://example.com/report").
     *
     * @return string|false
     */
    protected function sanitizeReportUriOrTo($value)
    {
        $val = self::sanitize_csp_directive($value);

        if (preg_match('#^(https?:)#i', $val) && filter_var($val, FILTER_VALIDATE_URL)) {
            return $val;
        }

        return false;
    }

    /**
     * Handle generic source-list directives (e.g. default-src, script-src).
     * These can have keywords, schemes, or host sources.
     *
     * @param string $value A space-separated list (e.g. "'self' https://example.com").
     *
     * @return string|false Sanitized list if valid, false if something doesn't match.
     */
    protected function sanitizeSourceListDirective($value)
    {
        $allowedKeywords = array(
            "'self'",
            "'none'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            "'strict-dynamic'",
            "'unsafe-hashed-attributes'",
            "'report-sample'"
        );

        $tokens = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        $finalTokens = array();

        foreach ($tokens as $token) {
            $t = self::sanitize_csp_directive($token);

            if (in_array($t, $allowedKeywords, true) ||
                preg_match('#^(https?:|data:|blob:|mediastream:|filesystem:)#i', $t) ||
                $t === '*' ||
                $this->isValidHostSource($t)) {
                $finalTokens[] = $t;
                continue;
            }

            return false;
        }

        return empty($finalTokens) ? false : implode(' ', $finalTokens);
    }

    /**
     * Checks if a token can be considered a valid host source.
     * A host source can be something like:
     * - example.com
     * - sub.example.com
     * - example.com:8080
     * - *.example.com
     *
     * @param string $source The token to check.
     *
     * @return bool True if valid, false otherwise.
     */
    protected function isValidHostSource($source)
    {
        $pattern = '/^(\*\.)?[a-zA-Z0-9\-]+(\.[a-zA-Z0-9\-]+)*(?::[0-9]+)?$/';
        return (bool)preg_match($pattern, $source);
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
}
