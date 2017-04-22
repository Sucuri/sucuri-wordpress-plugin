<?php

/**
 * Code related to the event.lib.php interface.
 *
 * @package Sucuri Security
 * @subpackage event.lib.php
 * @copyright Since 2010 Sucuri Inc.
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
 * @see https://en.wikipedia.org/wiki/Event_(computing)
 */
class SucuriScanEvent extends SucuriScan
{
    /**
     * Creates a cronjob to run the file system scanner twice-daily.
     *
     * Right after a fresh installation of the plugin, it will create a cronjob
     * that will execute the first scan in the next five minutes. This scan will
     * set the base-line for the file monitor through the API service. When a new
     * scan is execute the API will compare the checksum from the previous file
     * list with the checksum of the new file list, if there are differences we
     * will assume that someone or something modified one or more files and send
     * an email alsert about the incident.
     *
     * @param bool $run_now Forces the execute of the scanner right now.
     */
    public static function scheduleTask($run_now = true)
    {
        $task_name = 'sucuriscan_scheduled_scan';

        if (SucuriScanOption::getOption(':scan_frequency') === '_oneoff') {
            /* Stop if the user has disabled the cronjobs. */
            return;
        }

        if (!wp_next_scheduled($task_name)) {
            wp_schedule_event(time() + 10, 'twicedaily', $task_name);
        }

        if ($run_now === true) {
            // Execute scheduled task after five minutes.
            wp_schedule_single_event(time() + 300, $task_name);
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
     * @return array List of available cronjob frequencies.
     */
    public static function availableSchedules()
    {
        if (!function_exists('wp_get_schedules')) {
            return array(/* empty */);
        }

        $schedules = array();
        $jobs = wp_get_schedules();

        foreach ($jobs as $unique => $info) {
            $schedules[$unique] = sprintf(
                '%s (every %d seconds)',
                $info['display'],
                $info['interval']
            );
        }

        $schedules['_oneoff'] = 'Never (no execution)';

        return $schedules;
    }

    /**
     * Checks last time we ran to avoid running twice (or too often).
     *
     * @param int $runtime When the filesystem scan must be scheduled to run.
     * @param bool $force_scan Whether the filesystem scan was forced by an administrator user or not.
     * @return bool Either TRUE or FALSE representing the success or fail of the operation respectively.
     */
    private static function verifyRun($runtime = 0, $force_scan = false)
    {
        $current_time = time();
        $option_name = ':runtime';
        $last_run = SucuriScanOption::getOption($option_name);

        // Check if the last runtime is too near the current time.
        if ($last_run && !$force_scan) {
            $runtime_diff = $current_time - $runtime;

            if ($last_run >= $runtime_diff) {
                return false;
            }
        }

        return SucuriScanOption::updateOption($option_name, $current_time);
    }

    /**
     * Check whether the current WordPress version must be reported to the API
     * service or not, this is to avoid duplicated information in the audit logs.
     *
     * @return bool True if the current WordPress version must be reported, false otherwise.
     */
    private static function reportSiteVersion()
    {
        $option_name = ':site_version';
        $wp_version = self::siteVersion();
        $reported_version = SucuriScanOption::getOption($option_name);

        /* use simple comparison to leverage casting */
        if ($reported_version == $wp_version) {
            return false;
        }

        SucuriScanEvent::reportInfoEvent('WordPress version detected ' . $wp_version);

        return SucuriScanOption::updateOption($option_name, $wp_version);
    }

    /**
     * Gather all the checksums (aka. file hashes) of this site, send them, and
     * analyze them using the Sucuri Monitoring service, this will generate the
     * audit logs for this site and be part of the integrity checks.
     *
     * @param bool $force_scan Whether the filesystem scan was forced by an administrator user or not.
     * @return bool True if the filesystem scan was successful, false otherwise.
     */
    public static function filesystemScan($force_scan = false)
    {
        if (!class_exists('SucuriScanFileInfo')) {
            SucuriScan::throwException('FileInfo class does not exists');
            return;
        }

        if (!SucuriScanAPI::getPluginKey()) {
            SucuriScan::throwException('API key is not available');
            return;
        }

        if (!self::verifyRun(SUCURISCAN_MINIMUM_RUNTIME, $force_scan)) {
            SucuriScan::throwException('Background scanner was executed just now');
            return false;
        }

        self::reportSiteVersion();

        $file_info = new SucuriScanFileInfo();
        $signatures = $file_info->getDirectoryTreeMd5(ABSPATH);

        if (!$signatures) {
            SucuriScanInterface::error('The file hashes could not be retrieved.');
            return false;
        }

        if (!SucuriScanAPI::sendHashes($signatures)) {
            SucuriScanInterface::error('The file hashes could not be stored.');
            return false;
        }

        return SucuriScanOption::updateOption(':runtime', time());
    }

    /**
     * Generates an audit event log (to be sent later).
     *
     * @param int $severity Importance of the event that will be reported, values from one to five.
     * @param string $message The explanation of the event.
     * @param bool $internal Whether the event will be publicly visible or not.
     * @return bool True if the event was logged in the monitoring service, false otherwise.
     */
    private static function reportEvent($severity = 0, $message = '', $internal = false)
    {
        $user = wp_get_current_user();
        $remote_ip = self::getRemoteAddr();
        $username = false;

        // Identify current user in session.
        if ($user instanceof WP_User
            && isset($user->user_login)
            && !empty($user->user_login)
        ) {
            if ($user->user_login != $user->display_name) {
                $username = sprintf("\x20%s (%s),", $user->display_name, $user->user_login);
            } else {
                $username = sprintf("\x20%s,", $user->user_login);
            }
        }

        $severity = intval($severity);
        $severity_name = 'Info'; /* default */
        $severities = array(
            /* 0 */ 'Debug',
            /* 1 */ 'Notice',
            /* 2 */ 'Info',
            /* 3 */ 'Warning',
            /* 4 */ 'Error',
            /* 5 */ 'Critical',
        );

        if (isset($severities[$severity])) {
            $severity_name = $severities[$severity];
        }

        if ($internal === true) {
            /* mark the event as internal if necessary. */
            $severity_name = '@' . $severity_name;
        }

        /* remove unnecessary characters */
        $message = strip_tags($message);
        $message = str_replace("\r", '', $message);
        $message = str_replace("\n", '', $message);
        $message = str_replace("\t", '', $message);

        return self::sendEventLog(sprintf(
            '%s:%s %s; %s',
            $severity_name,
            $username,
            $remote_ip,
            $message
        ));
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
     * @param string $event_message Information about the security event.
     * @return bool True if the operation succeeded, false otherwise.
     */
    public static function sendEventLog($event_message = '')
    {
        /* create storage directory if necessary */
        SucuriScanInterface::createStorageFolder();

        /**
         * Self-hosted Monitor.
         *
         * Send a copy of the event log to a local file, this will allow the
         * administrator of the server to integrate the events monitored by the plugin
         * with a 3rd-party service like OSSEC or similar. More information in the Self-
         * Hosting panel located in the plugin' settings page.
         */
        if (function_exists('sucuriscan_selfhosting_fpath')) {
            $monitor_fpath = sucuriscan_selfhosting_fpath();

            if ($monitor_fpath !== false) {
                $local_event = sprintf(
                    "%s WordPressAudit %s %s : %s\n",
                    date('Y-m-d H:i:s'),
                    SucuriScan::getTopLevelDomain(),
                    SucuriScanOption::getOption(':account'),
                    $event_message
                );
                @file_put_contents(
                    $monitor_fpath,
                    $local_event,
                    FILE_APPEND
                );
            }
        }

        if (SucuriScanOption::isEnabled(':api_service')) {
            SucuriScanAPI::sendLogsFromQueue();

            return SucuriScanAPI::sendLog($event_message);
        }

        return true;
    }

    /**
     * Reports a debug event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @param bool $internal Whether the event will be publicly visible or not.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportDebugEvent($message = '', $internal = false)
    {
        return self::reportEvent(0, $message, $internal);
    }

    /**
     * Reports a notice event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @param bool $internal Whether the event will be publicly visible or not.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportNoticeEvent($message = '', $internal = false)
    {
        return self::reportEvent(1, $message, $internal);
    }

    /**
     * Reports a info event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @param bool $internal Whether the event will be publicly visible or not.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportInfoEvent($message = '', $internal = false)
    {
        return self::reportEvent(2, $message, $internal);
    }

    /**
     * Reports a warning event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @param bool $internal Whether the event will be publicly visible or not.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportWarningEvent($message = '', $internal = false)
    {
        return self::reportEvent(3, $message, $internal);
    }

    /**
     * Reports a error event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @param bool $internal Whether the event will be publicly visible or not.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportErrorEvent($message = '', $internal = false)
    {
        return self::reportEvent(4, $message, $internal);
    }

    /**
     * Reports a critical event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @param bool $internal Whether the event will be publicly visible or not.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportCriticalEvent($message = '', $internal = false)
    {
        return self::reportEvent(5, $message, $internal);
    }

    /**
     * Reports a notice or error event for enable and disable actions.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @param string $action An optional text, hopefully either enabled or disabled.
     * @param bool $internal Whether the event will be publicly visible or not.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportAutoEvent($message = '', $action = '', $internal = false)
    {
        $message = strip_tags($message);

        /* auto-detect the action performed, either enabled or disabled. */
        if (preg_match('/( was )?(enabled|disabled)$/', $message, $match)) {
            $action = $match[2];
        }

        if ($action === 'enabled') {
            return self::reportNoticeEvent($message, $internal);
        }

        if ($action === 'disabled') {
            return self::reportErrorEvent($message, $internal);
        }

        return self::reportInfoEvent($message, $internal);
    }

    /**
     * Reports an esception on the code.
     *
     * @param Exception $exception A valid exception object of any type.
     * @return bool Whether the report was filled correctly or not.
     */
    public static function reportException($exception)
    {
        $e_trace = $exception->getTrace();
        $multiple_entries = array();

        foreach ($e_trace as $e_child) {
            $e_file = array_key_exists('file', $e_child)
                ? basename($e_child['file'])
                : '[internal function]';
            $e_line = array_key_exists('line', $e_child)
                ? basename($e_child['line'])
                : '0';
            $e_method = array_key_exists('class', $e_child)
                ? $e_child['class'] . $e_child['type'] . $e_child['function']
                : $e_child['function'];
            $multiple_entries[] = sprintf(
                '%s(%s): %s',
                $e_file,
                $e_line,
                $e_method
            );
        }

        return self::reportDebugEvent(sprintf(
            '%s: (multiple entries): %s',
            $exception->getMessage(),
            @implode(',', $multiple_entries)
        ));
    }

    /**
     * Send a notification to the administrator of the specified events, only if
     * the administrator accepted to receive alerts for this type of events.
     *
     * @param string $event The name of the event that was triggered.
     * @param string $content Body of the email that will be sent to the administrator.
     * @return bool True if the email was apparently sent, false otherwise.
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
        if (self::isTrustedIp()) {
            $notify = 'disabled';
        }

        /* skip if alerts for this event are disabled */
        if ($notify !== 'enabled') {
            return false;
        }

        if ($event == 'post_publication') {
            $event = 'post_update';
        } elseif ($event == 'failed_login') {
            $settings_url = SucuriScanTemplate::getUrl('settings');
            $content .= "<br>\n<br>\n<em>Explanation: Someone failed to login to your "
                . "site. If you are getting too many of these messages, it is likely your "
                . "site is under a password guessing brute-force attack [1]. You can disable "
                . "the failed login alerts from here [2]. Alternatively, you can consider "
                . "to install a firewall between your website and your visitors to filter "
                . "out these and other attacks, take a look at Sucuri Firewall [3].</em>"
                . "<br>\n<br>\n"
                . "[1] <a href='https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing'>"
                . "https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing</a><br>\n"
                . "[2] <a href='" . $settings_url . "'>" . $settings_url . "</a> <br>\n"
                . "[3] <a href='https://sucuri.net/website-firewall/?wpalert'>"
                . "https://sucuri.net/website-firewall/</a> <br>\n";
        } elseif ($event == 'bruteforce_attack') {
            // Send a notification even if the limit of emails per hour was reached.
            $email_params['Force'] = true;
        } elseif ($event == 'scan_checksums') {
            $event = 'core_integrity_checks';
            $email_params['Force'] = true;
            $email_params['ForceHTML'] = true;
        } elseif ($event == 'available_updates') {
            $email_params['Force'] = true;
            $email_params['ForceHTML'] = true;
        }

        $title = str_replace('_', "\x20", $event);

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
     * @param string $remote_addr The supposed ip address that will be checked.
     * @return bool True if the IP address of the user is trusted, false otherwise.
     */
    private static function isTrustedIp($remote_addr = '')
    {
        $cache = new SucuriScanCache('trustip', false);
        $trusted_ips = $cache->getAll();

        if (!$remote_addr) {
            $remote_addr = SucuriScan::getRemoteAddr();
        }

        $addr_md5 = md5($remote_addr);

        // Check if the CIDR in range 32 of this IP is trusted.
        if (is_array($trusted_ips)
            && !empty($trusted_ips)
            && array_key_exists($addr_md5, $trusted_ips)
        ) {
            return true;
        }

        if ($trusted_ips) {
            foreach ($trusted_ips as $cache_key => $ip_info) {
                $ip_parts = explode('.', $ip_info->remote_addr);
                $ip_pattern = false;

                // Generate the regular expression for a specific CIDR range.
                switch ($ip_info->cidr_range) {
                    case 24:
                        $ip_pattern = sprintf('/^%d\.%d\.%d\.[0-9]{1,3}$/', $ip_parts[0], $ip_parts[1], $ip_parts[2]);
                        break;
                    case 16:
                        $ip_pattern = sprintf('/^%d\.%d(\.[0-9]{1,3}) {2}$/', $ip_parts[0], $ip_parts[1]);
                        break;
                    case 8:
                        $ip_pattern = sprintf('/^%d(\.[0-9]{1,3}) {3}$/', $ip_parts[0]);
                        break;
                }

                if ($ip_pattern && preg_match($ip_pattern, $remote_addr)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generate and set a new password for a specific user not in session.
     *
     * @param int $user_id The user identifier that will be changed, this must be different than the user in session.
     * @return bool Either true or false in case of success or error respectively.
     */
    public static function setNewPassword($user_id = 0)
    {
        $user_id = intval($user_id);

        if (!$user_id || !function_exists('wp_generate_password')) {
            return false;
        }

        $user = get_userdata($user_id);

        if (!($user instanceof WP_User)) {
            return false;
        }

        $website = SucuriScan::getDomain();
        $user_login = $user->user_login;
        $display_name = $user->display_name;
        $new_password = wp_generate_password(15, true, false);

        $message = SucuriScanTemplate::getSection('settings-posthack-reset-password-alert', array(
            'ResetPassword.UserName' => $user_login,
            'ResetPassword.DisplayName' => $display_name,
            'ResetPassword.Password' => $new_password,
            'ResetPassword.Website' => $website,
        ));

        /* Skip per hour alert limit and force text/html content-type */
        $data_set = array('Force' => true, 'ForceHTML' => true);

        SucuriScanMail::sendMail(
            $user->user_email,
            'Password Changed',
            $message,
            $data_set
        );

        /* send email before changing the password */
        wp_set_password($new_password, $user_id);

        return true;
    }

    /**
     * Modify the WordPress configuration file and change the keys that were defined
     * by a new random-generated list of keys retrieved from the official WordPress
     * API. The result of the operation will be either FALSE in case of error, or an
     * array containing multiple indexes explaining the modification, among them you
     * will find the old and new keys.
     *
     * @return array|bool Either FALSE in case of error, or an array with the old and new keys.
     */
    public static function setNewConfigKeys()
    {
        $new_wpconfig = '';
        $config_path = self::getWPConfigPath();

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

        if (is_array($config_lines) && is_array($new_keys)) {
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

        $response = array(
            'updated' => is_writable($config_path),
            'old_keys' => $old_keys,
            'old_keys_string' => $old_keys_string,
            'new_keys' => $new_keys,
            'new_keys_string' => $new_keys_string,
            'new_wpconfig' => $new_wpconfig,
        );

        if ($response['updated']) {
            @file_put_contents($config_path, $new_wpconfig, LOCK_EX);
        }

        return $response;
    }
}
