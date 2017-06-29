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
 * @see https://en.wikipedia.org/wiki/Event_%28computing%29
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
                __('ScheduledTask', SUCURISCAN_TEXTDOMAIN),
                $info['display'],
                $info['interval']
            );
        }

        $schedules['_oneoff'] = __('ScheduledTaskNever', SUCURISCAN_TEXTDOMAIN);

        return $schedules;
    }

    /**
     * Reports the WordPress version number to the API.
     *
     * @return bool True if the version number was reported, false otherwise.
     */
    public static function reportSiteVersion()
    {
        if (!SucuriScanAPI::getPluginKey()) {
            return self::throwException('API key is not available');
        }

        $wp_version = self::siteVersion();
        $reported_version = SucuriScanOption::getOption(':site_version');

        /* use simple comparison to leverage casting */
        if ($reported_version == $wp_version) {
            return self::throwException('WordPress version was already reported');
        }

        SucuriScanEvent::reportInfoEvent('WordPress version detected ' . $wp_version);

        return SucuriScanOption::updateOption(':site_version', $wp_version);
    }

    /**
     * Decides if the file system scanner can run or not.
     *
     * @param bool $force_scan Force the execution of the scanner.
     * @return bool True if the scanner can run, false otherwise.
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
     * @param bool $force_scan Whether the filesystem scan was forced by an administrator user or not.
     * @return bool True if the filesystem scan was successful, false otherwise.
     */
    public static function filesystemScan($force_scan = false)
    {
        if (!SucuriScanAPI::getPluginKey()) {
            return self::throwException('API key is not available');
        }

        if (!self::runFileScanner($force_scan)) {
            return self::throwException('Scanner ran a couple of minutes ago');
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
     * @param string $message Information about the event.
     * @param string $timestamp Time when the event was triggered.
     * @return bool True if the event was logged, false otherwise.
     */
    private static function sendLogToAPI($message = '', $timestamp = '')
    {
        if (empty($message)) {
            return self::throwException('Event identifier cannot be empty');
        }

        $params = array();
        $params['a'] = 'send_log';
        $params['m'] = $message;
        $params['time'] = $timestamp;
        $args = array('timeout' => 5 /* seconds */);

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
     * @param string $message Information about the security event.
     * @return bool True if the operation succeeded, false otherwise.
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
                    date('Y-m-d H:i:s'),
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

        /* enqueue the event if the API is enabled */
        if (SucuriScanOption::isEnabled(':api_service')) {
            $cache = new SucuriScanCache('auditqueue');
            $key = str_replace('.', '_', microtime(true));
            $written = $cache->add($key, $message);
        }

        return true;
    }

    /**
     * Sends all the events from the queue to the API.
     */
    public static function sendLogsFromQueue()
    {
        if (SucuriScanOption::isDisabled(':api_service')) {
            return;
        }

        $cache = new SucuriScanCache('auditqueue');
        $finfo = $cache->getDatastoreInfo();
        $events = $cache->getAll();
        $counter = 0;

        if (!$events) {
            return;
        }

        /* Send around 15,000 logs for maximum 30 seconds */
        $maxtime = (int) SucuriScan::iniGet('max_execution_time');
        $maxreqs = ($maxtime > 1) ? (500 * $maxtime) : 5000;

        foreach ($events as $keyname => $message) {
            $offset = strpos($keyname, '_');
            $timestamp = substr($keyname, 0, $offset);
            $status = self::sendLogToAPI($message, $timestamp);

            if ($status !== true) {
                /* API is down */
                break;
            }

            /* dequeue event message */
            unset($events[$keyname]);
            $counter++;

            /* avoid memory limit */
            if ($counter >= $maxreqs) {
                break;
            }
        }

        $cache->override($events);
    }

    /**
     * Generates an audit event log (to be sent later).
     *
     * @param int $severity Importance of the event that will be reported, values from one to five.
     * @param string $message The explanation of the event.
     * @return bool True if the event was logged in the monitoring service, false otherwise.
     */
    private static function reportEvent($severity = 0, $message = '')
    {
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

        /* remove unnecessary characters */
        $message = strip_tags($message);
        $message = str_replace("\r", '', $message);
        $message = str_replace("\n", '', $message);
        $message = str_replace("\t", '', $message);

        return self::sendLogToQueue(sprintf(
            '%s:%s %s; %s',
            $severity_name,
            $username,
            $remote_ip,
            $message
        ));
    }

    /**
     * Reports a debug event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportDebugEvent($message = '')
    {
        return self::reportEvent(0, $message);
    }

    /**
     * Reports a notice event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportNoticeEvent($message = '')
    {
        return self::reportEvent(1, $message);
    }

    /**
     * Reports a info event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportInfoEvent($message = '')
    {
        return self::reportEvent(2, $message);
    }

    /**
     * Reports a warning event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportWarningEvent($message = '')
    {
        return self::reportEvent(3, $message);
    }

    /**
     * Reports a error event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportErrorEvent($message = '')
    {
        return self::reportEvent(4, $message);
    }

    /**
     * Reports a critical event on the website.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportCriticalEvent($message = '')
    {
        return self::reportEvent(5, $message);
    }

    /**
     * Reports a notice or error event for enable and disable actions.
     *
     * @param string $message Text witht the explanation of the event or action performed.
     * @param string $action An optional text, hopefully either enabled or disabled.
     * @return bool Either true or false depending on the success of the operation.
     */
    public static function reportAutoEvent($message = '', $action = '')
    {
        $message = strip_tags($message);

        /* auto-detect the action performed, either enabled or disabled. */
        if (preg_match('/( was )?(enabled|disabled)$/', $message, $match)) {
            $action = $match[2];
        }

        if ($action === 'enabled') {
            return self::reportNoticeEvent($message);
        }

        if ($action === 'disabled') {
            return self::reportErrorEvent($message);
        }

        return self::reportInfoEvent($message);
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
                $content .= sprintf(__('FailedLoginFooter'), $settings_url, $settings_url);
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

        $title = __('EmailSubject.' . $event, SUCURISCAN_TEXTDOMAIN);

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
    public static function isTrustedIP($remote_addr = '')
    {
        if (!$remote_addr) {
            $remote_addr = SucuriScan::getRemoteAddr();
        }

        $cache = new SucuriScanCache('trustip', false);
        $trusted_ips = $cache->getAll();

        if (!is_array($trusted_ips) || empty($trusted_ips)) {
            return false;
        }

        /* check if exact IP address match is whitelisted */
        if (array_key_exists(md5($remote_addr), $trusted_ips)) {
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
                    $ip_pattern =
                    '/^' . $ip_parts[0]
                    . '.' . $ip_parts[1]
                    . '.' . $ip_parts[2]
                    . '\.[0-9]{1,3}$/';
                    break;

                case 16:
                    $ip_pattern =
                    '/^' . $ip_parts[0]
                    . '.' . $ip_parts[1]
                    . '\.[0-9]{1,3}'
                    . '\.[0-9]{1,3}$/';
                    break;

                case 8:
                    $ip_pattern =
                    '/^' . $ip_parts[0]
                    . '\.[0-9]{1,3}'
                    . '\.[0-9]{1,3}'
                    . '\.[0-9]{1,3}$/';
                    break;
            }

            if ($ip_pattern && preg_match($ip_pattern, $remote_addr)) {
                $is_trusted_ip = true;
                break;
            }
        }

        return $is_trusted_ip;
    }

    /**
     * Generate and set a new password for a specific user not in session.
     *
     * @param int $user_id User account identifier.
     * @return bool True if the process exit clean, false otherwise.
     */
    public static function setNewPassword($user_id = 0)
    {
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

        $sent = SucuriScanMail::sendMail(
            $user->user_email,
            __('EmailSubject.password_change', SUCURISCAN_TEXTDOMAIN),
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
}
