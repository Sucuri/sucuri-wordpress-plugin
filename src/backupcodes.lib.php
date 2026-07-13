<?php
/**
 * Code related to Two-Factor Authentication backup/recovery codes.
 *
 * PHP version 5
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Sucuri Inc.
 * @copyright  2010-2025 Sucuri Inc.
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

/**
 * Generates, stores, validates and consumes one-time Two-Factor backup codes.
 *
 * Codes are hashed with WordPress' own password hashing primitive (never
 * encrypted) because they are single-use secrets that never need to be
 * redisplayed once generated; the plugin's reversible AES-GCM secret storage
 * in SucuriScanOption is intentionally not used here.
 */
class SucuriScanBackupCodes extends SucuriScan
{
    const META_KEY = 'sucuriscan_topt_backup_codes';
    const CODE_COUNT = 10;
    const CODE_LENGTH = 8;
    const LOW_CODES_THRESHOLD = 2;
    const REVEAL_TRANSIENT_PREFIX = 'sucuriscan_backup_codes_reveal_';
    const REVEAL_REDIRECT_TRANSIENT_PREFIX = 'sucuriscan_backup_codes_redirect_';
    const REVEAL_TRANSIENT_TTL = 300;

    /**
     * Alphabet excludes visually ambiguous characters (0/O, 1/I/L).
     *
     * @var string
     */
    private static $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /**
     * Generate a brand-new set of backup codes for a user, overwriting any
     * existing set. Returns the plaintext codes; this is the only place the
     * plaintext values are ever available once this call returns.
     *
     * @param int $user_id
     *
     * @return string[] Plaintext codes, formatted as XXXX-XXXX.
     */
    public static function generate_for_user($user_id)
    {
        $user_id = (int) $user_id;
        $codes = array();
        $hashes = array();

        for ($i = 0; $i < self::CODE_COUNT; $i++) {
            $code = self::generate_code();
            $codes[] = $code;
            $hashes[] = wp_hash_password(self::normalize_code($code));
        }

        update_user_meta($user_id, self::META_KEY, $hashes);

        return $codes;
    }

    /**
     * Generate a set of backup codes for a user only if none currently exist.
     * Safe to call unconditionally from every Two-Factor "enable" code path.
     *
     * @param int $user_id
     *
     * @return string[]|null Plaintext codes on first generation, null if a
     *                       set already existed (no-op).
     */
    public static function maybe_generate_for_user($user_id)
    {
        $user_id = (int) $user_id;

        if (self::has_codes($user_id)) {
            return null;
        }

        return self::generate_for_user($user_id);
    }

    /**
     * Whether a user currently has any unused backup codes.
     *
     * @param int $user_id
     *
     * @return bool
     */
    public static function has_codes($user_id)
    {
        return !empty(self::get_hashes($user_id));
    }

    /**
     * Delete all backup codes for a user. Called wherever the Two-Factor
     * secret itself is deleted (reset flows), never on plain "deactivate"
     * (disabling enforcement should not destroy an existing setup).
     *
     * @param int $user_id
     *
     * @return void
     */
    public static function delete_for_user($user_id)
    {
        delete_user_meta((int) $user_id, self::META_KEY);
    }

    /**
     * Count of unused backup codes remaining for a user.
     *
     * @param int $user_id
     *
     * @return int
     */
    public static function remaining_count($user_id)
    {
        return count(self::get_hashes($user_id));
    }

    /**
     * Validate a submitted backup code and, if valid, consume it so it can
     * never be reused (single-use semantics).
     *
     * @param int    $user_id
     * @param string $code Raw user input; dashes/whitespace are stripped and
     *                     the value is uppercased before comparison.
     *
     * @return bool True if the code was valid and has now been consumed.
     */
    public static function validate_and_consume($user_id, $code)
    {
        $user_id = (int) $user_id;
        $normalized = self::normalize_code($code);

        if ($normalized === '' || strlen($normalized) !== self::CODE_LENGTH) {
            return false;
        }

        $hashes = self::get_hashes($user_id);

        foreach ($hashes as $index => $hash) {
            if (wp_check_password($normalized, $hash, $user_id)) {
                $previous_hashes = $hashes;
                unset($hashes[$index]);
                $hashes = array_values($hashes);

                // Require the stored value to match the value we verified so
                // concurrent requests cannot consume the same code twice.
                if (!update_user_meta($user_id, self::META_KEY, $hashes, $previous_hashes)) {
                    return false;
                }

                self::log_consumption($user_id, count($hashes));

                return true;
            }
        }

        return false;
    }

    /**
     * Stash a freshly generated plaintext code set for one-time reveal on
     * the next page render (used by redirect-based enable flows that cannot
     * return the codes directly in an AJAX/JSON response).
     *
     * @param int      $user_id
     * @param string[] $codes
     *
     * @return void
     */
    public static function stash_reveal($user_id, array $codes, $redirect_to = '')
    {
        set_transient(self::reveal_transient_key($user_id), $codes, self::REVEAL_TRANSIENT_TTL);

        $redirect_to = is_string($redirect_to) ? $redirect_to : '';

        if ($redirect_to !== '') {
            set_transient(self::reveal_redirect_transient_key($user_id), $redirect_to, self::REVEAL_TRANSIENT_TTL);
        } else {
            delete_transient(self::reveal_redirect_transient_key($user_id));
        }
    }

    /**
     * Read and immediately clear a stashed plaintext code set, so it can
     * only ever be rendered once, even within the transient's TTL window.
     *
     * @param int $user_id
     *
     * @return string[] Empty array if nothing was stashed (or already read).
     */
    public static function consume_reveal($user_id)
    {
        $key = self::reveal_transient_key($user_id);
        $codes = get_transient($key);
        delete_transient($key);

        return is_array($codes) ? $codes : array();
    }

    /**
     * Read and immediately clear the destination to use after a stashed code
     * reveal is acknowledged.
     *
     * @param int $user_id
     *
     * @return string
     */
    public static function consume_reveal_redirect($user_id)
    {
        $key = self::reveal_redirect_transient_key($user_id);
        $redirect_to = get_transient($key);
        delete_transient($key);

        return is_string($redirect_to) ? $redirect_to : '';
    }

    /**
     * @param int $user_id
     *
     * @return string
     */
    private static function reveal_transient_key($user_id)
    {
        return self::REVEAL_TRANSIENT_PREFIX . (int) $user_id;
    }

    /**
     * @param int $user_id
     *
     * @return string
     */
    private static function reveal_redirect_transient_key($user_id)
    {
        return self::REVEAL_REDIRECT_TRANSIENT_PREFIX . (int) $user_id;
    }

    /**
     * @param int $user_id
     *
     * @return array
     */
    private static function get_hashes($user_id)
    {
        $hashes = get_user_meta((int) $user_id, self::META_KEY, true);

        return is_array($hashes) ? $hashes : array();
    }

    /**
     * Uppercase and strip anything outside the code alphabet (dashes,
     * whitespace, stray characters) so "abcd-1234", "ABCD 1234" and
     * "ABCD1234" all normalize identically.
     *
     * @param mixed $code
     *
     * @return string
     */
    public static function normalize_code($code)
    {
        $code = is_scalar($code) ? strtoupper((string) $code) : '';

        return (string) preg_replace('/[^' . self::$alphabet . ']/', '', $code);
    }

    /**
     * @return string A single code formatted as XXXX-XXXX.
     */
    private static function generate_code()
    {
        $raw = '';
        $max = strlen(self::$alphabet) - 1;

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $index = 0;

            try {
                $index = random_int(0, $max);
            } catch (Exception $e) {
                // random_int() can throw if no CSPRNG source is available on
                // this platform; fall back to WP's own randomness rather
                // than fail Two-Factor setup entirely.
                $index = wp_rand(0, $max);
            }

            $raw .= self::$alphabet[$index];
        }

        return substr($raw, 0, 4) . '-' . substr($raw, 4);
    }

    /**
     * @param int $user_id
     * @param int $remaining
     *
     * @return void
     */
    private static function log_consumption($user_id, $remaining)
    {
        if (!class_exists('SucuriScanEvent')) {
            return;
        }

        SucuriScanEvent::reportWarningEvent(sprintf(
            'Two-factor backup code used for user ID %d (%d remaining).',
            (int) $user_id,
            (int) $remaining
        ));

        if ($remaining <= self::LOW_CODES_THRESHOLD) {
            SucuriScanEvent::reportWarningEvent(sprintf(
                'Two-factor backup codes running low for user ID %d (%d remaining); regeneration recommended.',
                (int) $user_id,
                (int) $remaining
            ));
        }
    }
}
