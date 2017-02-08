<?php

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
     * Schedule the task to run the first filesystem scan.
     *
     * @return void
     */
    public static function schedule_task($run_now = true)
    {
        $task_name = 'sucuriscan_scheduled_scan';

        if (SucuriScanOption::get_option(':scan_frequency') === '_oneoff') {
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
     * Checks last time we ran to avoid running twice (or too often).
     *
     * @param  integer $runtime    When the filesystem scan must be scheduled to run.
     * @param  boolean $force_scan Whether the filesystem scan was forced by an administrator user or not.
     * @return boolean             Either TRUE or FALSE representing the success or fail of the operation respectively.
     */
    private static function verify_run($runtime = 0, $force_scan = false)
    {
        $option_name = ':runtime';
        $last_run = SucuriScanOption::get_option($option_name);
        $current_time = time();

        // The filesystem scanner can be disabled from the settings page.
        if (SucuriScanOption::is_disabled(':fs_scanner')
            && $force_scan === false
        ) {
            return false;
        }

        // Check if the last runtime is too near the current time.
        if ($last_run && !$force_scan) {
            $runtime_diff = $current_time - $runtime;

            if ($last_run >= $runtime_diff) {
                return false;
            }
        }

        SucuriScanOption::update_option($option_name, $current_time);

        return true;
    }

    /**
     * Check whether the current WordPress version must be reported to the API
     * service or not, this is to avoid duplicated information in the audit logs.
     *
     * @return boolean TRUE if the current WordPress version must be reported, FALSE otherwise.
     */
    private static function report_site_version()
    {
        $option_name = ':site_version';
        $reported_version = SucuriScanOption::get_option($option_name);
        $wp_version = self::site_version();

        if ($reported_version != $wp_version) {
            SucuriScanEvent::report_info_event('WordPress version detected ' . $wp_version);
            SucuriScanOption::update_option($option_name, $wp_version);

            return true;
        }

        return false;
    }

    /**
     * Gather all the checksums (aka. file hashes) of this site, send them, and
     * analyze them using the Sucuri Monitoring service, this will generate the
     * audit logs for this site and be part of the integrity checks.
     *
     * @param  boolean $force_scan Whether the filesystem scan was forced by an administrator user or not.
     * @return boolean             TRUE if the filesystem scan was successful, FALSE otherwise.
     */
    public static function filesystem_scan($force_scan = false)
    {
        $minimum_runtime = SUCURISCAN_MINIMUM_RUNTIME;

        if (self::verify_run($minimum_runtime, $force_scan)
            && class_exists('SucuriScanFileInfo')
            && SucuriScanAPI::getPluginKey()
        ) {
            self::report_site_version();

            $file_info = new SucuriScanFileInfo();
            $file_info->scan_interface = SucuriScanOption::get_option(':scan_interface');
            $signatures = $file_info->get_directory_tree_md5(ABSPATH);

            if ($signatures) {
                $hashes_sent = SucuriScanAPI::sendHashes($signatures);

                if ($hashes_sent) {
                    SucuriScanOption::update_option(':runtime', time());
                    return true;
                } else {
                    SucuriScanInterface::error('The file hashes could not be stored.');
                }
            } else {
                SucuriScanInterface::error('The file hashes could not be retrieved, the filesystem scan failed.');
            }
        }

        return false;
    }

    /**
     * Generates an audit event log (to be sent later).
     *
     * @param  integer $severity Importance of the event that will be reported, values from one to five.
     * @param  string  $message  The explanation of the event.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           TRUE if the event was logged in the monitoring service, FALSE otherwise.
     */
    private static function report_event($severity = 0, $message = '', $internal = false)
    {
        $user = wp_get_current_user();
        $username = false;
        $remote_ip = self::get_remote_addr();

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

        // Fixing severity value.
        $severity = (int) $severity;

        // Convert the severity number into a readable string.
        switch ($severity) {
            case 0:
                $severity_name = 'Debug';
                break;
            case 1:
                $severity_name = 'Notice';
                break;
            case 2:
                $severity_name = 'Info';
                break;
            case 3:
                $severity_name = 'Warning';
                break;
            case 4:
                $severity_name = 'Error';
                break;
            case 5:
                $severity_name = 'Critical';
                break;
            default:
                $severity_name = 'Info';
                break;
        }

        // Mark the event as internal if necessary.
        if ($internal === true) {
            $severity_name = '@' . $severity_name;
        }

        // Clear event message.
        $message = strip_tags($message);
        $message = str_replace("\r", '', $message);
        $message = str_replace("\n", '', $message);
        $message = str_replace("\t", '', $message);

        $event_message = sprintf(
            '%s:%s %s; %s',
            $severity_name,
            $username,
            $remote_ip,
            $message
        );

        return self::sendEventLog($event_message);
    }

    public static function sendEventLog($event_message = '')
    {
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
                    SucuriScan::get_top_level_domain(),
                    SucuriScanOption::get_option(':account'),
                    $event_message
                );
                @file_put_contents(
                    $monitor_fpath,
                    $local_event,
                    FILE_APPEND
                );
            }
        }

        if (SucuriScanOption::is_enabled(':api_service')) {
            SucuriScanAPI::sendLogsFromQueue();

            return SucuriScanAPI::sendLog($event_message);
        }

        return true;
    }

    /**
     * Reports a debug event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_debug_event($message = '', $internal = false)
    {
        return self::report_event(0, $message, $internal);
    }

    /**
     * Reports a notice event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_notice_event($message = '', $internal = false)
    {
        return self::report_event(1, $message, $internal);
    }

    /**
     * Reports a info event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_info_event($message = '', $internal = false)
    {
        return self::report_event(2, $message, $internal);
    }

    /**
     * Reports a warning event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_warning_event($message = '', $internal = false)
    {
        return self::report_event(3, $message, $internal);
    }

    /**
     * Reports a error event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_error_event($message = '', $internal = false)
    {
        return self::report_event(4, $message, $internal);
    }

    /**
     * Reports a critical event on the website.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_critical_event($message = '', $internal = false)
    {
        return self::report_event(5, $message, $internal);
    }

    /**
     * Reports a notice or error event for enable and disable actions.
     *
     * @param  string  $message  Text witht the explanation of the event or action performed.
     * @param  string  $action   An optional text, hopefully either enabled or disabled.
     * @param  boolean $internal Whether the event will be publicly visible or not.
     * @return boolean           Either true or false depending on the success of the operation.
     */
    public static function report_auto_event($message = '', $action = '', $internal = false)
    {
        $message = strip_tags($message);

        // Auto-detect the action performed, either enabled or disabled.
        if (preg_match('/( was )?(enabled|disabled)$/', $message, $match)) {
            $action = $match[2];
        }

        // Report the correct event for the action performed.
        if ($action == 'enabled') {
            return self::report_notice_event($message, $internal);
        } elseif ($action == 'disabled') {
            return self::report_error_event($message, $internal);
        } else {
            return self::report_info_event($message, $internal);
        }
    }

    /**
     * Reports an esception on the code.
     *
     * @param  Exception $exception A valid exception object of any type.
     * @return boolean              Whether the report was filled correctly or not.
     */
    public static function report_exception($exception = false)
    {
        if ($exception) {
            $e_trace = $exception->getTrace();
            $multiple_entries = array();

            foreach ($e_trace as $e_child) {
                $e_file = array_key_exists('file', $e_child)
                    ? basename($e_child['file'])
                    : '[internal function]';
                $e_line = array_key_exists('line', $e_child)
                    ? basename($e_child['line'])
                    : '0';
                $e_function = array_key_exists('class', $e_child)
                    ? $e_child['class'] . $e_child['type'] . $e_child['function']
                    : $e_child['function'];
                $multiple_entries[] = sprintf(
                    '%s(%s): %s',
                    $e_file,
                    $e_line,
                    $e_function
                );
            }

            $report_message = sprintf(
                '%s: (multiple entries): %s',
                $exception->getMessage(),
                @implode(',', $multiple_entries)
            );

            return self::report_debug_event($report_message);
        }

        return false;
    }

    /**
     * Send a notification to the administrator of the specified events, only if
     * the administrator accepted to receive alerts for this type of events.
     *
     * @param  string $event   The name of the event that was triggered.
     * @param  string $content Body of the email that will be sent to the administrator.
     * @return void
     */
    public static function notify_event($event = '', $content = '')
    {
        $notify = SucuriScanOption::get_option(':notify_' . $event);
        $email = SucuriScanOption::get_option(':notify_to');
        $email_params = array();

        if (self::is_trusted_ip()) {
            $notify = 'disabled';
        }

        if ($notify == 'enabled') {
            if ($event == 'post_publication') {
                $event = 'post_update';
            } elseif ($event == 'failed_login') {
                $settings_url = SucuriScanTemplate::getUrl('settings');
                $content .= "<br>\n<br>\n<em>Explanation: Someone failed to login to your "
                    . "site. If you are getting too many of these messages, it is likely your "
                    . "site is under a password guessing brute-force attack [1]. You can disable "
                    . "the failed login alerts from here [2]. Alternatively, you can consider "
                    . "to install a firewall between your website and your visitors to filter "
                    . "out these and other attacks, take a look at Sucuri CloudProxy [3].</em>"
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
            $mail_sent = SucuriScanMail::send_mail(
                $email,
                $title,
                $content,
                $email_params
            );

            return $mail_sent;
        }

        return false;
    }

    /**
     * Check whether an IP address is being trusted or not.
     *
     * @param  string  $remote_addr The supposed ip address that will be checked.
     * @return boolean              TRUE if the IP address of the user is trusted, FALSE otherwise.
     */
    private static function is_trusted_ip($remote_addr = '')
    {
        $cache = new SucuriScanCache('trustip', false);
        $trusted_ips = $cache->getAll();

        if (!$remote_addr) {
            $remote_addr = SucuriScan::get_remote_addr();
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
     * @param  integer $user_id The user identifier that will be changed, this must be different than the user in session.
     * @return boolean          Either TRUE or FALSE in case of success or error respectively.
     */
    public static function set_new_password($user_id = 0)
    {
        $user_id = intval($user_id);

        if ($user_id > 0 && function_exists('wp_generate_password')) {
            $user = get_userdata($user_id);

            if ($user instanceof WP_User) {
                $website = SucuriScan::get_domain();
                $user_login = $user->user_login;
                $display_name = $user->display_name;
                $new_password = wp_generate_password(15, true, false);

                $message = SucuriScanTemplate::getSection('notification-resetpwd', array(
                    'ResetPassword.UserName' => $user_login,
                    'ResetPassword.DisplayName' => $display_name,
                    'ResetPassword.Password' => $new_password,
                    'ResetPassword.Website' => $website,
                ));

                $data_set = array( 'Force' => true ); // Skip limit for emails per hour.
                SucuriScanMail::send_mail($user->user_email, 'Password changed', $message, $data_set);

                wp_set_password($new_password, $user_id);

                return true;
            }
        }

        return false;
    }

    /**
     * Modify the WordPress configuration file and change the keys that were defined
     * by a new random-generated list of keys retrieved from the official WordPress
     * API. The result of the operation will be either FALSE in case of error, or an
     * array containing multiple indexes explaining the modification, among them you
     * will find the old and new keys.
     *
     * @return false|array Either FALSE in case of error, or an array with the old and new keys.
     */
    public static function set_new_config_keys()
    {
        $new_wpconfig = '';
        $config_path = self::get_wpconfig_path();

        if ($config_path) {
            $pattern = self::secret_key_pattern();
            $define_tpl = "define('%s',%s'%s');";
            $config_lines = SucuriScanFileInfo::file_lines($config_path);
            $new_keys = SucuriScanAPI::getNewSecretKeys();
            $old_keys = array();
            $old_keys_string = '';
            $new_keys_string = '';

            foreach ((array) $config_lines as $config_line) {
                if (preg_match($pattern, $config_line, $match)) {
                    $key_name = $match[1];

                    if (array_key_exists($key_name, $new_keys)) {
                        $white_spaces = $match[2];
                        $old_keys[ $key_name ] = $match[3];
                        $config_line = sprintf($define_tpl, $key_name, $white_spaces, $new_keys[ $key_name ]);
                        $old_keys_string .= sprintf($define_tpl, $key_name, $white_spaces, $old_keys[ $key_name ]) . "\n";
                        $new_keys_string .= $config_line . "\n";
                    }
                }

                $new_wpconfig .= $config_line . "\n";
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
                file_put_contents($config_path, $new_wpconfig, LOCK_EX);
            }

            return $response;
        }

        return false;
    }
}
