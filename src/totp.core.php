<?php
/**
 * Code related to the TOTP implementation.
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


// Abort if the file is loaded out of context.
if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

// Polyfill hash_equals() for PHP < 5.6
// WordPress ships a polyfill in newer versions, but we defensively include
// one here in case very old environments are still in play. This preserves
// constant-time comparison semantics for TOTP code verification.
if (!function_exists('hash_equals')) {
    function hash_equals($known_string, $user_string)
    {
        if (!is_string($known_string) || !is_string($user_string)) {
            return false;
        }

        $ks = strlen($known_string);
        $us = strlen($user_string);

        if ($ks !== $us) {
            return false;
        }

        $res = 0;

        for ($i = 0; $i < $ks; $i++) {
            $res |= ord($known_string[$i]) ^ ord($user_string[$i]);
        }

        return $res === 0;
    }
}

class SucuriScanTOTP extends SucuriScan
{
    const DEFAULT_KEY_BIT_SIZE = 160;
    const DEFAULT_CRYPTO = 'sha1';
    const DEFAULT_DIGIT_COUNT = 6;
    const DEFAULT_TIME_STEP_SEC = 30;
    const DEFAULT_TIME_STEP_ALLOWANCE = 2;

    private static $base32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * This function generates a random key suitable for TOTP.
     * 
     * PHP 7 and up will use random_bytes() to generate cryptographically secure keys.
     * The fallback is wp_generate_password() with special characters enabled.
     * 
     * @param mixed $bitsize
     * 
     * @return string
     */
    public static function generate_key($bitsize = self::DEFAULT_KEY_BIT_SIZE)
    {
        $secret = '';

        $bytes = (int) ceil($bitsize / 8);

        // PHP 7 and up.
        if (function_exists('random_bytes')) {
            try {
                $secret = random_bytes($bytes);
            } catch (Exception $e) {
                $secret = '';
            }
        }

        if (empty($secret)) {
            $secret = wp_generate_password($bytes, true, true);
        }

        return self::base32_encode($secret);
    }


    /**
     * This function generates a URL for a QR code that can be scanned by an authenticator app.
     * 
     * @param mixed $user
     * @param mixed $secret
     * 
     * @return string
     */
    public static function generate_qr_code_url($user, $secret)
    {
        $issuer = get_bloginfo('name', 'display');
        $user_login = isset($user->user_login) ? $user->user_login : '';
        $label = $issuer . ':' . $user_login;

        if (empty($issuer)) {
            $label = $user_login;
        }

        $base = 'otpauth://totp/' . rawurlencode($label);

        $query = array(
            'secret' => rawurlencode($secret),
            'issuer' => rawurlencode($issuer),
        );

        $url = add_query_arg($query, $base);

        return esc_url_raw($url, array('otpauth'));
    }

    /**
     * Validate an RFC 4648 Base32 key.
     * - Uppercases and strips "=" (padding) before validation.
     * - Accepts only A–Z and 2–7 after normalization.
     *
     * @param mixed $key
     * @return bool
     */
    public static function is_valid_key($key)
    {
        // Remove the padding, if exists.
        $key = strtoupper(str_replace('=', '', (string) $key));
        $check = sprintf('/^[%s]+$/', self::$base32_chars);

        return (preg_match($check, $key) === 1);
    }

    /**
     * Calculate a TOTP code using the class defaults (RFC 6238).
     *
     * Behavior
     * - Uses Base32 `key` as shared secret; decodes to bytes via `base32_decode()`.
     * - If `$step_count` is `false`, derives the moving factor from the current
     *   Unix time and `DEFAULT_TIME_STEP_SEC` (typically 30 seconds).
     * - Computes HMAC with `DEFAULT_CRYPTO` (typically 'sha1') over the packed
     *   8-byte big-endian counter (`pack64()`).
     * - Applies HOTP dynamic truncation (RFC 4226) using the low 4 bits of the
     *   **last** HMAC byte (generalized for SHA-1/256/512), extracts a 31-bit
     *   integer, then reduces modulo 10^`DEFAULT_DIGIT_COUNT` (typically 6) and
     *   left-pads with zeros.
     *
     * Notes
     * - This matches TOTP (RFC 6238) with defaults: 6 digits, 30-second period,
     *   SHA-1; these are widely supported by authenticator apps.
     * - The 6-digit default is consistent with common TOTP practice; RFC test
     *   vectors are often shown as 8 digits, but your 6-digit output is simply
     *   the RFC value modulo 10^6.
     *
     * @param string    $key        Base32 encoded secret.
     * @param int|false $step_count Moving counter; `false` to compute from time().
     * @return string               TOTP code with `DEFAULT_DIGIT_COUNT` digits.
     * @throws Exception            If Base32 decoding fails.
     */

    public static function calc_totp(
        $key,
        $step_count = false
    ) {
        $secret = self::base32_decode($key);

        if ($step_count === false) {
            $step_count = (int) floor(time() / self::DEFAULT_TIME_STEP_SEC);
        }

        $counter = self::pack64($step_count);

        $hmac = hash_hmac(self::DEFAULT_CRYPTO, $counter, $secret, true);

        $last = strlen($hmac) - 1;
        $offset = ord($hmac[$last]) & 0x0F;

        $binCode = substr($hmac, $offset, 4);
        $val = current(unpack('N', $binCode)) & 0x7fffffff;

        $mod = (int) pow(10, self::DEFAULT_DIGIT_COUNT);
        $code = (string) ($val % $mod);

        return str_pad($code, self::DEFAULT_DIGIT_COUNT, '0', STR_PAD_LEFT);
    }

    /**
     * This function returns the valid tick time for a given authcode and key.
     * 
     * If no valid tick time is found, it returns false.
     * 
     * @param mixed $key
     * @param mixed $authcode
     * 
     * @return bool|float|int
     */
    public static function get_authcode_valid_ticktime($key, $authcode)
    {
        $max_ticks = self::DEFAULT_TIME_STEP_ALLOWANCE;

        $ticks = range(-$max_ticks, $max_ticks);

        usort($ticks, array(__CLASS__, 'abssort'));

        $time = (int) floor(time() / self::DEFAULT_TIME_STEP_SEC);

        foreach ($ticks as $offset) {
            $log_time = (int) $time + $offset;
            $expected = self::calc_totp($key, $log_time);

            if (hash_equals($expected, $authcode)) {
                return $log_time * self::DEFAULT_TIME_STEP_SEC;
            }
        }

        return false;
    }

    /**
     * This function encodes data into Base32 format.
     *
     * @param mixed $input
     *
     * @return string
     */
    public static function base32_encode($input)
    {
        if (empty($input)) {
            return '';
        }

        $output = '';
        $bitBuffer = 0;
        $bitCount = 0;
        $len = strlen($input);

        for ($i = 0; $i < $len; $i++) {
            $bitBuffer = ($bitBuffer << 8) | ord($input[$i]);
            $bitCount += 8;

            while ($bitCount >= 5) {
                $bitCount -= 5;
                $index = ($bitBuffer >> $bitCount) & 0x1F;
                $output .= self::$base32_chars[$index];
            }
        }

        if ($bitCount > 0) {
            $index = ($bitBuffer << (5 - $bitCount)) & 0x1F;
            $output .= self::$base32_chars[$index];
        }

        return $output;
    }

    /**
     * This function decodes Base32 encoded data.
     *
     * @param mixed $input
     *
     * @return string
     * 
     * @throws Exception if invalid characters are found.
     */
    public static function base32_decode($input)
    {
        if (empty($input)) {
            return '';
        }

        $s = strtoupper((string) $input);
        $s = preg_replace('/\s+/', '', $s);
        $s = rtrim($s, '=');


        if ($s !== '' && !preg_match('/^[A-Z2-7]+$/', $s)) {
            throw new Exception('Invalid characters in Base32 input.');
        }

        $output = '';
        $bitBuffer = 0;
        $bitCount = 0;
        $len = strlen($s);

        for ($i = 0; $i < $len; $i++) {
            $val = strpos(self::$base32_chars, $s[$i]);

            $bitBuffer = ($bitBuffer << 5) | $val;
            $bitCount += 5;

            while ($bitCount >= 8) {
                $bitCount -= 8;
                $output .= chr(($bitBuffer >> $bitCount) & 0xFF);

                if ($bitCount > 0) {
                    $bitBuffer &= (1 << $bitCount) - 1;
                } else {
                    $bitBuffer = 0;
                }
            }
        }

        return $output;
    }

    /**
     * Pack an unsigned 64-bit counter as 8-byte **big-endian** (RFC 4226 / TOTP).
     *
     * Uses 'J' on PHP >= 5.6.3 (unsigned 64-bit, big-endian). Falls back to two
     * 32-bit big-endian words ('N2') otherwise.
     *
     * @param int $value Non-negative step counter (fits in 64 bits).
     * 
     * @return string 8-byte binary string (big-endian).
     * 
     * @throws Exception If $value is negative, non-integer, or cannot be represented.
     */
    public static function pack64($value)
    {
        if (!is_int($value) || $value < 0) {
            throw new \Exception('pack64 expects a non-negative integer.');
        }

        if (PHP_INT_SIZE >= 8) {
            if (version_compare(PHP_VERSION, '5.6.3', '>=')) {
                return pack('J', $value);
            }

            $hi = ($value >> 32) & 0xFFFFFFFF;
            $lo = $value & 0xFFFFFFFF;
            return pack('N2', $hi, $lo);
        }

        if ($value > 0xFFFFFFFF) {
            throw new \Exception('pack64 overflow on 32-bit PHP (value > 2^32-1).');
        }

        return pack('N2', 0, $value & 0xFFFFFFFF);
    }


    /**
     * This function sorts numbers by their absolute values.
     * 
     * @param mixed $a
     * @param mixed $b
     * 
     * @return int
     */
    private static function abssort($a, $b)
    {
        $a = abs($a);
        $b = abs($b);

        if ($a === $b) {
            return 0;
        }

        return ($a < $b) ? -1 : 1;
    }
}
