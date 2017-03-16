<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

class SucuriScanBlockedUsers extends SucuriScanLastLogins
{
    public static function page()
    {
        $output = array();
        $output['BlockedUsers.List'] = '';
        $output['BlockedUsers.NoItemsVisibility'] = 'visible';

        if (SucuriScanInterface::check_nonce()) {
            $unblockUsers = SucuriScanRequest::post(':unblock_user', '_array');

            if (is_array($unblockUsers) && !empty($unblockUsers)) {
                self::unblock($unblockUsers);
                SucuriScanInterface::info('Selected user accounts were unblocked');
            }
        }

        $cache = new SucuriScanCache('blockedusers', false);
        $blocked = $cache->getAll();

        if (is_array($blocked) && !empty($blocked)) {
            $counter = 0;

            foreach ($blocked as $data) {
                $css_class = ($counter % 2 === 0) ? '' : 'alternate';
                $output['BlockedUsers.List'] .= SucuriScanTemplate::getSnippet(
                    'lastlogins-blockedusers',
                    array(
                        'BlockedUsers.CssClass' => $css_class,
                        'BlockedUsers.Username' => $data->username,
                        'BlockedUsers.BlockedAt' => self::datetime($data->blocked_at),
                        'BlockedUsers.FirstAttempt' => self::datetime($data->first_attempt),
                        'BlockedUsers.LastAttempt' => self::datetime($data->last_attempt),
                    )
                );
                $counter++;
            }

            if ($counter > 0) {
                $output['BlockedUsers.NoItemsVisibility'] = 'hidden';
            }
        }

        return SucuriScanTemplate::getSection('lastlogins-blockedusers', $output);
    }

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

    public static function blockUserLogin()
    {
        if (class_exists('SucuriScanRequest')
            && class_exists('SucuriScanCache')
        ) {
            $username = SucuriScanRequest::post('log');
            $password = SucuriScanRequest::post('pwd');

            if ($username !== false && $password !== false) {
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
        }
    }

    private static function firstAttempt($logs, $user)
    {
        $attempts = array();

        foreach ($logs['entries'] as $login) {
            if ($login['user_login'] === $user) {
                $attempts[] = $login['attempt_time'];
            }
        }

        if (empty($attempts)) {
            return null;
        }

        return min($attempts);
    }

    private static function lastAttempt($logs, $user)
    {
        $attempts = array();

        foreach ($logs['entries'] as $login) {
            if ($login['user_login'] === $user) {
                $attempts[] = $login['attempt_time'];
            }
        }

        if (empty($attempts)) {
            return null;
        }

        return max($attempts);
    }
}
