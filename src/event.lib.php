<?php

/**
 * Code related to the event.lib.php interface.
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

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * System events, reports and actions.
 *
 * An event is an action or occurrence detected by the program that may be
 * handled by the program. Typically events are handled synchronously with the
 * program flow, that is, the program has one or more dedicated places where
 * events are handled, frequently an event loop. Typical sources of events
 * include the user; another source is a hardware device such as a timer. Any
 * program can trigger its own custom set of events as well, e.g. to communicate
 * the completion of a task. A computer program that changes its behavior in
 * response to events is said to be event-driven, often with the goal of being
 * interactive.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanEvent extends SucuriScan
{
    /**
     * Creates a cronjob to run the file system scanner.
     *
     * Right after a fresh installation of the plugin, it will create a cronjob
     * that will execute the first scan in the next five minutes. This scan will
     * set the base-line for the file monitor through the API service. When a new
     * scan is execute the API will compare the checksum from the previous file
     * list with the checksum of the new file list, if there are differences we
     * will assume that someone or something modified one or more files and send
     * an email alsert about the incident.
     *
     * @return void
     */
    public static function installScheduledTask()
    {
        $task_name = 'sucuriscan_scheduled_scan';

        if (!wp_next_scheduled($task_name)) {
            wp_schedule_event(time() + 10, 'daily', $task_name);
        }
    }

    /**
     * Returns a list of available cronjob frequencies.
     *
     * This method will return not only the default WordPress cronjob frequencies
     * but also the custom ones defined by 3rd-party plugins or themes. It will
     * also add an additional option to allow the website owners to disable the
     * schedule tasks from the settings page.
     *
     * @see https://developer.wordpress.org/reference/functions/wp_get_schedules/
     *
     * @return array List of available cronjob frequencies.
     */
    public static function availableSchedules()
    {
        $schedules = array();
        $jobs = wp_get_schedules();

        foreach ($jobs as $unique => $info) {
            $schedules[$unique] = sprintf(
                __('%s (every %d seconds)', 'sucuri-scanner'),
                $info['display'],
                $info['interval']
            );
        }

        $schedules['_oneoff'] = __('Never (no execution)', 'sucuri-scanner');

        return $schedules;
    }

    /**
     * Returns a list of active cronjobs.
     *
     * This method will return not only the default WordPress cronjobs but also
     * the custom ones defined by 3rd-party plugins or themes.
     *
     * @see https://developer.wordpress.org/reference/functions/_get_cron_array/
     *
     * @return array List of available cronjobs.
     */
    public static function activeSchedules()
    {
        $activeCrons = array();
        foreach ((array) _get_cron_array() as $timestamp => $cronhooks) {
            foreach ((array) $cronhooks as $hook => $events) {
                foreach ((array) $events as $key => $event) {
                    if (empty($event['args'])) {
                        $event['args'] = array('[]');
                    }
                    $activeCrons[$hook] = array(
                        'schedule' => $event['schedule'],
                        'nextTime' => SucuriScan::datetime($timestamp),
                        'nextTimeHuman' => SucuriScan::humanTime($timestamp),
                        'arguments' => json_encode($event['args']),
                    );
                }
            }
        }
        return $activeCrons;
    }

    /**
     * Creates the cronjob weekly, monthly and quarterly frequencies.
     *
     * A few Sucuri services require additional cronjob frequencies that are not
     * available on WordPress by default. This function will add these schedules
     * frequency if they were not yet register by any a 3rd party extension.
     *
     * @return void
     */
    public static function additionalSchedulesFrequencies($schedules)
    {
        if (!defined('MONTH_IN_SECONDS')) {
            define('MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS);
        }
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = array(
                'display' => __('Weekly', 'sucuriscan'),
                'interval' => WEEK_IN_SECONDS,
            );
        }
        if (!isset($schedules['monthly'])) {
            $schedules['monthly'] = array(
                'display' => __('Monthly', 'sucuriscan'),
                'interval' => MONTH_IN_SECONDS,
            );
        }
        if (!isset($schedules['quarterly'])) {
            $schedules['quarterly'] = array(
                'display' => __('Quarterly', 'sucuriscan'),
                'interval' => 3 * MONTH_IN_SECONDS,
            );
        }
        return $schedules;
    }

    /**
     * Creates a cronjob.
     *
     * @return bool True if the cronjob is correctly created.
     */
    public static function addScheduledTask($hookName, $frequency)
    {
        // Return false if schedule frequency does not exist.
        if (!in_array($frequency, array_keys(self::availableSchedules()))) {
            return false;
        }

        // Remove cron first if already exists.
        if (wp_next_scheduled($hookName)) {
            self::deleteScheduledTask($hookName);
        }

        // Add cron job hook.
        wp_schedule_event(time() + 10, $frequency, $hookName);
        return true;
    }

    /**
     * Deletes a cronjob.
     *
     * @return bool True if the cronjob is correctly removed.
     */
    public static function deleteScheduledTask($hookName)
    {
        // Return false if task does not exist.
        if (!wp_next_scheduled($hookName)) {
            return false;
        }

        // Remove cron job hook.
        wp_clear_scheduled_hook($hookName);

        return true;
    }

    /**
     * Reports the WordPress version number to the API.
     *
     * @return bool True if the version number was reported, false otherwise.
     */
    public static function reportSiteVersion()
    {
        if (!SucuriScanAPI::getPluginKey()) {
            return self::throwException(__('API key is not available', 'sucuri-scanner'));
        }

        $wp_version = self::siteVersion();
        $reported_version = SucuriScanOption::getOption(':site_version');

        /* use simple comparison to leverage casting */
        if ($reported_version == $wp_version) {
            return self::throwException(__('WordPress version was already reported', 'sucuri-scanner'));
        }

        SucuriScanEvent::reportInfoEvent(sprintf(__('WordPress version detected %s', 'sucuri-scanner'), $wp_version));

        return SucuriScanOption::updateOption(':site_version', $wp_version);
    }

    /**
     * Decides if the file system scanner can run or not.
     *
     * @param  bool $force_scan Force the execution of the scanner.
     * @return bool             True if the scanner can run, false otherwise.
     */
    private static function runFileScanner($force_scan = false)
    {
        if ($force_scan) {
            return SucuriScanOption::updateOption(':runtime', time());
        }

        $current_time = time();
        $runtime = (int) SucuriScanOption::getOption(':runtime');
        $diff = abs($current_time - $runtime);

        if ($diff < SUCURISCAN_SCANNER_FREQUENCY) {
            return false;
        }

        return SucuriScanOption::updateOption(':runtime', $current_time);
    }

    /**
     * Gather all the checksums (aka. file hashes) of this site, send them, and
     * analyze them using the Sucuri Monitoring service, this will generate the
     * audit logs for this site and be part of the integrity checks.
     *
     * @param  bool $force_scan Whether the filesystem scan was forced by an administrator user or not.
     * @return bool             True if the filesystem scan was successful, false otherwise.
     */
    public static function filesystemScan($force_scan = false)
    {
        if (!SucuriScanAPI::getPluginKey()) {
            return self::throwException(__('API key is not available', 'sucuri-scanner'));
        }

        if (!self::runFileScanner($force_scan)) {
            return self::throwException(__('Scanner ran a couple of minutes ago', 'sucuri-scanner'));
        }

        $fifo = new SucuriScanFileInfo();
        $signatures = $fifo->getDirectoryTreeMd5(ABSPATH);

        SucuriScanOption::updateOption(':runtime', time());

        return SucuriScanAPI::sendHashes($signatures);
    }

    /**
     * Sends information of a security event to the API.
     *
     * If the website owner has enabled the security log exporter, this method
     * will also write the information about the security event to taht file.
     * This allows to integrate with different monitoring systems like OSSEC or
     * OpenVAS.
     *
     * If the communication with the API is enabled, it will also send all the
     * security logs collected on previous executions of the method that resulted
     * in a failure. However, this procedure depends on the ability of the plugin
     * to write the log into the queue when the previous request failed.
     *
     * @param  string     $message   Information about the event.
     * @param  string|int $timestamp Time when the event was triggered.
     * @param  int        $timeout   Maximum time in seconds to connect to the API.
     * @return bool                  True if the event was logged, false otherwise.
     */
    private static function sendLogToAPI($message = '', $timestamp = '', $timeout = 1)
    {
        if (empty($message)) {
            return self::throwException(__('Event identifier cannot be empty', 'sucuri-scanner'));
        }

        $params = array();
        $params['a'] = 'send_log';
        $params['m'] = $message;
        $params['time'] = $timestamp;
        $args = array('timeout' => $timeout);

        $resp = SucuriScanAPI::apiCallWordpress('POST', $params, true, $args);

        return (bool) (
            is_array($resp)
            && array_key_exists('status', $resp)
            && intval($resp['status']) === 1
        );
    }

    /**
     * Sends the event message to a local queue system.
     *
     * Note: The method is public to facilitate the execution of some unit-tests
     * but it could be private and be mocked by the test bootstrap script. Take
     * this in consideration during the static analysis of the code.
     *
     * @param  string $message Information about the security event.
     * @return bool            True if the operation succeeded, false otherwise.
     */
    public static function sendLogToQueue($message = '')
    {
        /* create storage directory if necessary */
        SucuriScanInterface::createStorageFolder();

        /**
         * Self-hosted Monitor.
         *
         * Send a copy of the event log to a local file, this will allow the
         * administrator of the server to integrate the events monitored by the
         * plugin with a 3rd-party service like OSSEC or similar. More info in
         * the Self-Hosting panel located in the plugin' settings page.
         */
        if (function_exists('sucuriscan_selfhosting_fpath')) {
            $monitor_fpath = sucuriscan_selfhosting_fpath();

            if ($monitor_fpath !== false) {
                $local_event = sprintf(
                    "%s WordPressAudit %s %s : %s\n",
                    SucuriScan::datetime(null, 'Y-m-d H:i:s'),
                    SucuriScan::getTopLevelDomain(),
                    SucuriScanOption::getOption(':account'),
                    $message
                );
                @file_put_contents(
                    $monitor_fpath,
                    $local_event,
                    FILE_APPEND
                );
            }
        }

        /**
         * Send event to the API if possible.
         *
         * If the user have not disabled the communication with the API service,
         * the plugin will send a message with information about every triggered
         * event in the website in realtime with a maximum connection time of
         * two seconds. If the API service does not responds on time the plugin
         * will insert the event into the local queue system and it will try to
         * send the message again with a scheduled task every 24 hours, once the
         * operation succeeds the event will be deleted from the queue.
         */
        if (SucuriScanOption::isEnabled(':api_service')) {
            $cache = new SucuriScanCache('auditqueue');
            $key = str_replace('.', '_', microtime(true));
            $written = $cache->add($key, $message);
        }

        return true;
    }

    /**
     * Sends all the events from the queue to the API.
     *
     * @return array|bool Information about the dequeue process.
     */
    public static function sendLogsFromQueue()
    {
        if (SucuriScanOption::isDisabled(':api_service')) {
            return false;
        }

        $cache = new SucuriScanCache('auditqueue');
        $finfo = $cache->getDatastoreInfo();
        $events = $cache->getAll();

        if (!$events) {
            return false;
        }

        $result = array(
            'maxtime' => -1,
            'ttllogs' => 0,
            'success' => 0,
            'failure' => 0,
            'elapsed' => 0,
        );

        /**
         * Send logs to the API with a limit.
         *
         * We will use the maximum execution time setting to limit the number of
         * logs that the plugin will try to send to the API service before the
         * server times out. In a regular installation, the limit is set to 30
         * seconds, since the timeout for the HTTP request is 5 seconds we will
         * instruct the plugin to wait (30 secs - 5 secs) and an additional one
         * second to spare processing, so in a regular installation the plugin
         * will try to send as much logs as possible to the API service in less
         * than 25 seconds.
         */
        $maxtime = (int) SucuriScan::iniGet('max_execution_time');
        $timeout = ($maxtime > 1) ? ($maxtime - 6) : 30;

        /* record some statistics */
        $startTime = microtime(true);
        $result['maxtime'] = $maxtime;
        $result['ttllogs'] = count($events);

        foreach ($events as $keyname => $message) {
            $offset = strpos($keyname, '_');
            $timestamp = substr($keyname, 0, $offset);
            $status = self::sendLogToAPI($message, $timestamp);

            /* skip; API is busy */
            if ($status !== true) {
                $result['failure']++;
                continue;
            }

            /* dequeue event message */
            unset($events[$keyname]);
            $result['success']++;

            /* avoid gateway timeout; max execution time */
            $elapsedTime = (microtime(true) - $startTime);
            if ($elapsedTime >= $timeout) {
                break;
            }
        }

        $result['elapsed'] = round(microtime(true) - $startTime, 4);
        $cache->override($events);

        return $result;
    }

    /**
     * Generates an audit event log (to be sent later).
     *
     * @param  int    $severity Importance of the event that will be reported.
     * @param  string $message  The explanation of the event.
     * @return bool             True if the event was logged, false otherwise.
     */
    private static function reportEvent($severity = 0, $message = '')
    {
        if (!function_exists('wp_get_current_user')) {
            return;
        }

        $user = wp_get_current_user();
        $remote_ip = self::getRemoteAddr();
        $username = false;

        // Identify current user in session.
        if ($user instanceof WP_User
            && isset($user->user_login)
            && !empty($user->user_login)
        ) {
            $username = sprintf("\x20%s,", $user->user_login);
        }

        $severity = intval($severity);
        $severity_name = __('Info', 'sucuri-scanner');
        $severities = array(
            /* 0 */ __('Debug', 'sucuri-scanner'),
            /* 1 */ __('Notice', 'sucuri-scanner'),
            /* 2 */ __('Info', 'sucuri-scanner'),
            /* 3 */ __('Warning', 'sucuri-scanner'),
            /* 4 */ __('Error', 'sucuri-scanner'),
            /* 5 */ __('Critical', 'sucuri-scanner'),
        );

        if (isset($severities[$severity])) {
            $severity_name = $severities[$severity];
        }

        /* remove unnecessary characters */
        $message = strip_tags($message);
        $message = str_replace("\r", '', $message);
        $message = str_replace("\n", '', $message);
        $message = str_replace("\t", '', $message);

        return self::sendLogToQueue(
            sprintf(
                '%s:%s %s; %s',
                $severity_name,
                $username,
                $remote_ip,
                $message
            )
        );
    }

    /**
     * Reports a debug event on the website.
     *
     * @param  string $message Text witht the explanation of the event or action performed.
     * @return bool            Either true or false depending on the success of the operation.
     */
    public static function reportDebugEvent($message = '')
    {
        return self::reportEvent(0, $message);
    }

    /**
     * Reports a notice event on the website.
     *
     * @param  string $message Text witht the explanation of the event or action performed.
     * @return bool            Either true or false depending on the success of the operation.
     */
    public static function reportNoticeEvent($message = '')
    {
        return self::reportEvent(1, $message);
    }

    /**
     * Reports a info event on the website.
     *
     * @param  string $message Text witht the explanation of the event or action performed.
     * @return bool            Either true or false depending on the success of the operation.
     */
    public static function reportInfoEvent($message = '')
    {
        return self::reportEvent(2, $message);
    }

    /**
     * Reports a warning event on the website.
     *
     * @param  string $message Text witht the explanation of the event or action performed.
     * @return bool            Either true or false depending on the success of the operation.
     */
    public static function reportWarningEvent($message = '')
    {
        return self::reportEvent(3, $message);
    }

    /**
     * Reports a error event on the website.
     *
     * @param  string $message Text witht the explanation of the event or action performed.
     * @return bool            Either true or false depending on the success of the operation.
     */
    public static function reportErrorEvent($message = '')
    {
        return self::reportEvent(4, $message);
    }

    /**
     * Reports a critical event on the website.
     *
     * @param  string $message Text witht the explanation of the event or action performed.
     * @return bool            Either true or false depending on the success of the operation.
     */
    public static function reportCriticalEvent($message = '')
    {
        return self::reportEvent(5, $message);
    }

    /**
     * Send a notification to the administrator of the specified events, only if
     * the administrator accepted to receive alerts for this type of events.
     *
     * @param  string $event   The name of the event that was triggered.
     * @param  string $content Body of the email that will be sent to the administrator.
     * @return bool            True if the email was apparently sent, false otherwise.
     */
    public static function notifyEvent($event = '', $content = '')
    {
        $email_params = array();
        $email = SucuriScanOption::getOption(':notify_to');
        $notify = SucuriScanOption::getOption(':notify_' . $event);

        /**
         * Skip if the IP address is trusted.
         *
         * Ignore event if the website owner has whitelisted the IP address of
         * the current user in session. This is useful if the administrator is
         * working in an office and they want to allow every person in the office
         * (aka. the same LAN) to execute any task without triggering a security
         * alert.
         */
        if (self::isTrustedIP()) {
            $notify = 'disabled';
        }

        /* skip if alerts for this event are disabled */
        if ($notify !== 'enabled') {
            return false;
        }

        switch ($event) {
            case 'post_publication':
                $event = 'post_update';
                break;

            case 'failed_login':
                $settings_url = SucuriScanTemplate::getUrl('settings');
                $content .= "\n" . sprintf(
                    __("<br><br>\n\n<em>Explanation: Someone failed to login to your site. If you are getting too many of these messages, it is likely your site is under a password guessing brute-force attack [1]. You can disable the failed login alerts from here [2]. Alternatively, you can consider to install a firewall between your website and your visitors to filter out these and other attacks, take a look at Sucuri Firewall [3].</em><br><br>\n\n[1] <a href='https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing'>https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing</a><br>\n[2] <a href='%s'>%s</a> <br>\n[3] <a href='https://sucuri.net/website-firewall/?wpalert'>https://sucuri.net/website-firewall/</a><br>\n", 'sucuri-scanner'),
                    $settings_url,
                    $settings_url
                );
                break;

            case 'bruteforce_attack':
                $email_params['Force'] = true;
                break;

            case 'scan_checksums':
                $event = 'core_integrity_checks';
                $email_params['Force'] = true;
                $email_params['ForceHTML'] = true;
                break;

            case 'available_updates':
                $email_params['Force'] = true;
                $email_params['ForceHTML'] = true;
        }

        $title = ucwords(str_replace('_', "\x20", $event));

        return SucuriScanMail::sendMail(
            $email,
            $title,
            $content,
            $email_params
        );
    }

    /**
     * Check whether an IP address is being trusted or not.
     *
     * @param  string $addr The supposed ip address that will be checked.
     * @return bool         True if the user IP is trusted, false otherwise.
     */
    public static function isTrustedIP($addr = '')
    {
        if (!$addr) {
            $addr = SucuriScan::getRemoteAddr();
        }

        $cache = new SucuriScanCache('trustip', false);
        $trusted_ips = $cache->getAll();

        if (!is_array($trusted_ips) || empty($trusted_ips)) {
            return false;
        }

        /* check if exact IP address match is whitelisted */
        if (array_key_exists(md5($addr), $trusted_ips)) {
            return true;
        }

        $is_trusted_ip = false;

        /* check if the CIDR in range 32 of this IP is trusted. */
        foreach ($trusted_ips as $cache_key => $ip_info) {
            $ip_parts = explode('.', $ip_info->remote_addr);
            $ip_pattern = false;

            // Generate the regular expression for a specific CIDR range.
            switch ($ip_info->cidr_range) {
                case 24:
                    $ip_pattern = sprintf(
                        '/^%d\.%d\.%d\.[0-9]{1,3}$/',
                        intval($ip_parts[0]),
                        intval($ip_parts[1]),
                        intval($ip_parts[2])
                    );
                    break;

                case 16:
                    $ip_pattern = sprintf(
                        '/^%d\.%d\.[0-9]{1,3}\.[0-9]{1,3}$/',
                        intval($ip_parts[0]),
                        intval($ip_parts[1])
                    );
                    break;

                case 8:
                    $ip_pattern = sprintf(
                        '/^%d\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/',
                        intval($ip_parts[0])
                    );
                    break;
            }

            if ($ip_pattern && preg_match($ip_pattern, $addr)) {
                $is_trusted_ip = true;
                break;
            }
        }

        return $is_trusted_ip;
    }

    /**
     * Generate and set a new password for a specific user not in session.
     *
     * @param  int $user_id User account identifier.
     * @return bool         True if the process exit clean, false otherwise.
     */
    public static function setNewPassword($user_id = 0)
    {
        $user = get_userdata($user_id);

        if (!($user instanceof WP_User)) {
            return false;
        }

        /* invalidates the password for the given user */
        $new_password = wp_generate_password(15, true, false);
        wp_set_password($new_password, $user_id);

        $website = SucuriScan::getDomain();
        $user_login = $user->user_login;
        $display_name = $user->display_name;
        $key = self::GetPasswordResetKey($user);

        if (is_wp_error($key)) {
            return false;
        }

        $reset_password_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login' );

        $message = SucuriScanTemplate::getSection(
            'settings-posthack-reset-password-alert',
            array(
                'ResetPassword.UserName' => $user_login,
                'ResetPassword.DisplayName' => $display_name,
                'ResetPassword.ResetURL' => $reset_password_url,
                'ResetPassword.Website' => $website,
            )
        );

        /* Skip per hour alert limit and force text/html content-type */
        $data_set = array('Force' => true, 'ForceHTML' => true);

        $sent = SucuriScanMail::sendMail(
            $user->user_email,
            __('Password Change', 'sucuri-scanner'),
            $message,
            $data_set
        );

        return true;
    }

    /**
     * Gets a new password reset key.
     *
     * @since 1.8.25
     *
     * @param WP_User $user WP_User object.
     * @return string|WP_Error Returns a password reset key as a string, WP_Error otherwise.
     */
    private static function GetPasswordResetKey($user)
    {
        global $wp_hasher;

        $key_error = new WP_Error('no_password_reset');

        if (!($user instanceof WP_User)) {
            return $key_error;
        }

        /**
         * As of version 1.8.25 of this plugin, we still support WordPress version 3.6 and up
         * and for that reason we can't take advantage of the native function get_password_reset_key
         * (https://developer.wordpress.org/reference/functions/get_password_reset_key/), introduced in
         * WordPress 4.4.
         *
         * When we drop support for versions prior to WordPress 4.4, we can use get_password_reset_key
         * instead of this function.
         */
        if (version_compare(SucuriScan::siteVersion(), '4.4', '>=')
            && function_exists('get_password_reset_key')
        ) {
            $key = get_password_reset_key($user);

            return $key;
        }

        if (is_multisite() && is_user_spammy($user)) {
            return $key_error;
        }

        // Generate something random for a password reset key.
        $key = wp_generate_password(20, false);

        if (empty($wp_hasher)) {
            require_once ABSPATH . WPINC . '/class-phpass.php';
            $wp_hasher = PasswordHash(8, true);
        }

        $hashed = time() . ':' . $wp_hasher->HashPassword($key);

        $key_saved = wp_update_user(
            array(
                'ID' => $user->ID,
                'user_activation_key' => $hashed,
            )
        );

        if (is_wp_error($key_saved)) {
            return $key_saved;
        }

        return $key;
    }

    /**
     * Changes the WordPress secret keys.
     *
     * Modify the WordPress configuration file to define new secret keys from a
     * new randomly generated list of strings from the official WordPress API.
     * The result of the operation will be either False in case of error, or an
     * array containing multiple indexes explaining the modification, among them
     * you will find the old and new keys.
     *
     * @return array|bool Array with the old and new keys, false otherwise.
     */
    public static function setNewConfigKeys()
    {
        $new_wpconfig = '';
        $config_path = self::getConfigPath();

        if (!$config_path) {
            return false;
        }

        $pattern = self::secretKeyPattern();
        $define_tpl = "define('%s',%s'%s');";
        $content = SucuriScanFileInfo::fileContent($config_path);
        $config_lines = explode("\n", $content); /* maintain new lines */
        $new_keys = SucuriScanAPI::getNewSecretKeys();
        $new_keys_string = '';
        $old_keys_string = '';
        $old_keys = array();

        if ($config_lines && $new_keys) {
            foreach ($config_lines as $config_line) {
                if (@preg_match($pattern, $config_line, $match)) {
                    $key_name = $match[1];

                    if (array_key_exists($key_name, $new_keys)) {
                        $white_spaces = $match[2];
                        $old_keys[$key_name] = $match[3];
                        $config_line = sprintf(
                            $define_tpl,
                            $key_name,
                            $white_spaces,
                            $new_keys[$key_name]
                        );
                        $old_keys_string .= sprintf(
                            $define_tpl . "\n",
                            $key_name,
                            $white_spaces,
                            $old_keys[$key_name]
                        );
                        $new_keys_string .= $config_line . "\n";
                    }
                }

                $new_wpconfig .= $config_line . "\n";
            }
        }

        $resp = array(
            'updated' => is_writable($config_path),
            'old_keys' => $old_keys,
            'old_keys_string' => $old_keys_string,
            'new_keys' => $new_keys,
            'new_keys_string' => $new_keys_string,
            'new_wpconfig' => $new_wpconfig,
        );

        if ($resp['updated']) {
            @file_put_contents($config_path, $new_wpconfig, LOCK_EX);
        }

        return $resp;
    }

    /**
     * Clear last logins or failed login logs.
     *
     * This can also be done via Sucuri Security -> Settings -> Data Storage,
     * however to improve the user experience, a button on Last Logins  and on
     * Failed logins sections was added and it triggers the removal of
     * sucuri/sucuri-lastlogins.php and sucuri/sucuri-failedlogins.php.
     *
     * @param string $filename Name of the file to be deleted.
     *
     * @return HTML Message with the delete action outcome.
     */
    public static function clearLastLogs($filename)
    {
        // Get the complete path of the file.
        $filepath = SucuriScan::dataStorePath($filename);

        // Do not proceed if not possible.
        if (!is_writable(dirname($filepath)) || is_dir($filepath)) {
            return SucuriScanInterface::error(
                sprintf(
                    __('%s cannot be deleted.', 'sucuri-scanner'),
                    $filename
                )
            );
        }

        // Delete $filepath.
        @unlink($filepath);
        
        // Register on audit logs and return result.
        SucuriScanEvent::reportInfoEvent(
            sprintf(
                __('%s was deleted.', 'sucuri-scanner'),
                $filename
            )
        );
        return SucuriScanInterface::info(
            sprintf(
                __('%s was deleted.', 'sucuri-scanner'),
                $filename
            )
        );
    }
}
