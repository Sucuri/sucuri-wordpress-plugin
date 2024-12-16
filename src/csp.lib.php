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
     * This removes invalid characters, but does not fully validate semantics.
     *
     * @param string $input Raw input value
     *
     * @return string Sanitized value
     */
    static function sanitize_csp_directive($input)
    {
        // Allow letters, numbers, spaces, hyphens, single quotes, colons, semicolons, slashes, dots, and asterisks
        return preg_replace("/[^a-zA-Z0-9\s\-\'\:;\/\.\*]/", '', $input);
    }

    /**
     * Sets the CSP headers based on the user's settings.
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
        if (empty($cspOptions) || !is_array($cspOptions)) {
            return;
        }

        $cspDirectives = array();

        foreach ($cspOptions as $directive => $option) {
            // Skip directives that aren't enforced
            if (!isset($option['enforced']) || !$option['enforced']) {
                continue;
            }

            if (!isset($option['value'])) {
                continue;
            }

            $value = trim($option['value']);
            $directive = str_replace('_', '-', $directive);
            if ($value === '') {
                continue;
            }

            $allowedDirective = $this->getValidDirectiveOrFalse($directive);
            if (!$allowedDirective) {
                continue;
            }

            // Sanitize and validate the value
            $sanitizedValue = $this->sanitizeDirectiveValue($allowedDirective, $value);
            if ($sanitizedValue !== false) {
                $cspDirectives[] = $allowedDirective . ' ' . $sanitizedValue;
            }
        }

        if (empty($cspDirectives)) {
            // No valid CSP directives to set
            return;
        }

        $cspHeaderValue = implode('; ', $cspDirectives);

        // Default to report-only to avoid breaking sites
        header('Content-Security-Policy-Report-Only: ' . $cspHeaderValue);
    }


    /**
     * Validate the directive name against allowed directives.
     *
     * @param string $directive The directive name.
     *
     * @return string|false The directive if valid, false otherwise.
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
            'worker-src',
        );

        if (in_array($directive, $allowedDirectives)) {
            return $directive;
        }

        return false;
    }

    /**
     * Validates and sanitizes the value for a given directive according to CSP rules.
     *
     * @param string $directive The CSP directive.
     * @param string $value The raw value for the directive.
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

        // For all other directives, treat as a source-list directive
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
        $val = trim($this->sanitize_csp_directive($value));

        if ($val === 'upgrade-insecure-requests' || $val === '') {
            return 'upgrade-insecure-requests';
        }

        return false;
    }

    /**
     * Handle the sandbox directive, expecting a set of allowed tokens or empty.
     *
     * @param string $value
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
            $t = $this->sanitize_csp_directive($t);

            if (in_array($t, $sandboxTokens)) {
                $finalTokens[] = $t;
            }
        }

        return empty($finalTokens) ? 'sandbox' : implode(' ', $finalTokens);
    }

    /**
     * Handle the report-uri and report-to directives, expecting a valid URL or scheme.
     *
     * @param string $value
     *
     * @return string|false
     */
    protected function sanitizeReportUriOrTo($value)
    {
        $val = $this->sanitize_csp_directive($value);

        // Basic URL check: it should start with http/https or a known scheme
        if (preg_match('#^(https?:|data:|blob:|mediastream:|filesystem:)#i', $val) || filter_var(
            $val,
            FILTER_VALIDATE_URL
        )) {
            return $val;
        }

        return false;
    }

    /**
     * Handle generic source-list directives (e.g. default-src, script-src).
     * These can have keywords, schemes, and host sources.
     *
     * @param string $value
     *
     * @return string|false
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
            $t = $this->sanitize_csp_directive($token);

            if (in_array($t, $allowedKeywords)) {
                $finalTokens[] = $t;
                continue;
            }

            if (preg_match('#^(https?:|data:|blob:|mediastream:|filesystem:)#i', $t) || $t === '*') {
                $finalTokens[] = $t;
                continue;
            }

            if ($this->isValidHostSource($t)) {
                $finalTokens[] = $t;
                continue;
            }

            // Invalid token found
            return false;
        }

        if (empty($finalTokens)) {
            return false;
        }

        return implode(' ', $finalTokens);
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
        $pattern = '/^(?:\*\.)?[a-zA-Z0-9\-\.]+(?:\:[0-9]+)?$/';

        return (bool)preg_match($pattern, $source);
    }
}
