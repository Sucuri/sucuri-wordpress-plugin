<?php

/**
 * Code related to the lastlogins-blocked.php interface.
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
 * Allows the website owner to block usernames.
 *
 * An admin can select one or more usernames from the list of failed login and
 * block them so the next time someone tries to log into the website with that
 * username the operation will be stopped before the request hits the database.
 *
 * Notice that this feature does not allows the website owner to block requests
 * coming from a specific IP address. This is because the feature already exists
 * in the Firewall service and it also provides a better filtering mechanism for
 * any other suspicious login attempt. We will encourage people to leverage the
 * power of the Firewall.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2017 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanBlockedUsers extends SucuriScanLastLogins
{
    /**
     * Renders the page with a list of blocked usernames.
     *
     * @return string HTML code with a list of blocked usernames.
     */
    public static function page()
    {
        $output = array();
        $output['BlockedUsers.List'] = '';
        $output['BlockedUsers.NoItemsVisibility'] = 'visible';

        if (SucuriScanInterface::checkNonce()) {
            $unblockUsers = SucuriScanRequest::post(':unblock_user', '_array');

            if (is_array($unblockUsers) && !empty($unblockUsers)) {
                self::unblock($unblockUsers);
                SucuriScanInterface::info('Selected user accounts were unblocked');
            }
        }

        $cache = new SucuriScanCache('blockedusers', false);
        $blocked = $cache->getAll();

        if (is_array($blocked) && !empty($blocked)) {
            foreach ($blocked as $data) {
                $output['BlockedUsers.List'] .= SucuriScanTemplate::getSnippet(
                    'lastlogins-blockedusers',
                    array(
                        'BlockedUsers.Username' => $data->username,
                        'BlockedUsers.BlockedAt' => self::datetime($data->blocked_at),
                        'BlockedUsers.FirstAttempt' => self::datetime($data->first_attempt),
                        'BlockedUsers.LastAttempt' => self::datetime($data->last_attempt),
                    )
                );
            }

            $output['BlockedUsers.NoItemsVisibility'] = 'hidden';
        }

        return SucuriScanTemplate::getSection('lastlogins-blockedusers', $output);
    }

    /**
     * Blocks one or more usernames.
     *
     * @param  array $users List of usernames.
     * @return void
     */
    public static function block($users = array())
    {
        if (is_array($users) && !empty($users)) {
            $logs = sucuriscan_get_all_failed_logins();
            $cache = new SucuriScanCache('blockedusers');
            $blocked = $cache->getAll();

            foreach ($users as $user) {
                if (array_key_exists($user, $blocked)) {
                    continue;
                }

                $firstAttempt = self::firstAttempt($logs, $user);
                $lastAttempt = self::lastAttempt($logs, $user);
                $data = array(
                    'username' => $user,
                    'blocked_at' => time(),
                    'first_attempt' => $firstAttempt,
                    'last_attempt' => $lastAttempt,
                );
                $cache->add(md5($user), $data);
            }
        }
    }

    /**
     * Unblocks one or more usernames.
     *
     * @param  array $users List of usernames.
     * @return void
     */
    public static function unblock($users = array())
    {
        if (is_array($users) && !empty($users)) {
            $cache = new SucuriScanCache('blockedusers');
            $blocked = $cache->getAll();

            foreach ($users as $user) {
                $cache_key = md5($user);

                if (array_key_exists($cache_key, $blocked)) {
                    $cache->delete($cache_key);
                }
            }
        }
    }

    /**
     * Stops an user login attempt.
     *
     * This method will run right after the user submits the login form. We will
     * check to see if the username has been blocked by an admin and proceed
     * according to the expected behavior. Either we will stop the request right
     * here or let it propagate to the authentication checker.
     *
     * @return void
     */
    public static function blockUserLogin()
    {
        if (!class_exists('SucuriScanRequest') || !class_exists('SucuriScanCache')) {
            return;
        }

        $username = SucuriScanRequest::post('log');
        $password = SucuriScanRequest::post('pwd');

        if ($username === false || $password === false) {
            return;
        }

        $cache = new SucuriScanCache('blockedusers');
        $blocked = $cache->getAll();
        $cache_key = md5($username);

        if (is_array($blocked)
            && is_string($cache_key)
            && array_key_exists($cache_key, $blocked)
        ) {
            $blocked[$cache_key]->last_attempt = time();
            $cache->set($cache_key, $blocked[$cache_key]);

            if (!headers_sent()) {
                header('HTTP/1.1 403 Forbidden');
            }

            exit(0);
        }
    }

    /**
     * Finds the first login attempt of a specific username.
     *
     * @param  array  $logs List of failed login attempts.
     * @param  string $user Username to be inspected.
     * @return int          Timestamp of the first login attempt.
     */
    private static function firstAttempt($logs, $user)
    {
        $attempts = array();

        foreach ($logs['entries'] as $login) {
            if ($login['user_login'] === $user) {
                $attempts[] = $login['attempt_time'];
            }
        }

        if (empty($attempts)) {
            return -1;
        }

        return min($attempts);
    }

    /**
     * Finds the last login attempt of a specific username.
     *
     * @param  array  $logs List of failed login attempts.
     * @param  string $user Username to be inspected.
     * @return int          Timestamp of the last login attempt.
     */
    private static function lastAttempt($logs, $user)
    {
        $attempts = array();

        foreach ($logs['entries'] as $login) {
            if ($login['user_login'] === $user) {
                $attempts[] = $login['attempt_time'];
            }
        }

        if (empty($attempts)) {
            return -1;
        }

        return max($attempts);
    }
}
