<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Process the requests sent by the form submissions originated in the settings
 * page, all forms must have a nonce field that will be checked against the one
 * generated in the template render function.
 *
 * @param  boolean $page_nonce True if the nonce is valid, False otherwise.
 * @return void
 */
function sucuriscan_settings_form_submissions($page_nonce = null)
{
    global $sucuriscan_schedule_allowed,
        $sucuriscan_interface_allowed;

    // Use this conditional to avoid double checking.
    if (is_null($page_nonce)) {
        $page_nonce = SucuriScanInterface::checkNonce();
    }

    if ($page_nonce) {
        // Enable or disable the filesystem scanner.
        if ($fs_scanner = SucuriScanRequest::post(':fs_scanner', '(en|dis)able')) {
            $action_d = $fs_scanner . 'd';
            $message = 'Main file system scanner was <code>' . $action_d . '</code>';

            SucuriScanOption::updateOption(':fs_scanner', $action_d);
            SucuriScanEvent::reportAutoEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        // Enable or disable the filesystem scanner for error logs.
        if ($scan_errorlogs = SucuriScanRequest::post(':scan_errorlogs', '(en|dis)able')) {
            $action_d = $scan_errorlogs . 'd';
            $message = 'File system scanner for error logs was <code>' . $action_d . '</code>';

            SucuriScanOption::updateOption(':scan_errorlogs', $action_d);
            SucuriScanEvent::reportAutoEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        // Modify the schedule of the filesystem scanner.
        if ($frequency = SucuriScanRequest::post(':scan_frequency')) {
            if (array_key_exists($frequency, $sucuriscan_schedule_allowed)) {
                SucuriScanOption::updateOption(':scan_frequency', $frequency);

                // Remove all the scheduled tasks associated to the plugin.
                wp_clear_scheduled_hook('sucuriscan_scheduled_scan');

                // Install new cronjob unless the user has selected "Never".
                if ($frequency !== '_oneoff') {
                    wp_schedule_event(time() + 10, $frequency, 'sucuriscan_scheduled_scan');
                }

                $frequency_title = strtolower($sucuriscan_schedule_allowed[ $frequency ]);
                $message = 'File system scanning frequency set to <code>' . $frequency_title . '</code>';

                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }

        // Set the method (aka. interface) that will be used to scan the site.
        if ($interface = SucuriScanRequest::post(':scan_interface')) {
            $allowed_values = array_keys($sucuriscan_interface_allowed);

            if (in_array($interface, $allowed_values)) {
                $message = 'File system scanning interface set to <code>' . $interface . '</code>';

                SucuriScanOption::updateOption(':scan_interface', $interface);
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }

        // Reset the plugin security logs.
        $allowed_log_files = '(lastlogins|failedlogins)';
        if ($reset_logfile = SucuriScanRequest::post(':reset_logfile', $allowed_log_files)) {
            $files_to_delete = array(
                'sucuri-' . $reset_logfile . '.php',
                'sucuri-old' . $reset_logfile . '.php',
            );

            foreach ($files_to_delete as $log_filename) {
                $log_filepath = SucuriScan::dataStorePath($log_filename);

                if (@unlink($log_filepath)) {
                    $log_filename_simple = str_replace('.php', '', $log_filename);
                    $message = 'Deleted security log <code>' . $log_filename_simple . '</code>';

                    SucuriScanEvent::reportDebugEvent($message);
                    SucuriScanInterface::info($message);
                }
            }
        }

        // Ignore a new event for email notifications.
        if ($action = SucuriScanRequest::post(':ignorerule_action', '(add|remove)')) {
            $ignore_rule = SucuriScanRequest::post(':ignorerule');

            if ($action == 'add') {
                if (SucuriScanOption::addIgnoredEvent($ignore_rule)) {
                    SucuriScanInterface::info('Post-type ignored successfully.');
                    SucuriScanEvent::reportWarningEvent('Changes in <code>' . $ignore_rule . '</code> post-type will be ignored');
                } else {
                    SucuriScanInterface::error('The post-type is invalid or it may be already ignored.');
                }
            } elseif ($action == 'remove') {
                SucuriScanOption::removeIgnoredEvent($ignore_rule);
                SucuriScanInterface::info('Post-type removed from the list successfully.');
                SucuriScanEvent::reportNoticeEvent('Changes in <code>' . $ignore_rule . '</code> post-type will not be ignored');
            }
        }

        // Trust and IP address to ignore notifications for a subnet.
        if ($trust_ip = SucuriScanRequest::post(':trust_ip')) {
            if (SucuriScan::isValidIP($trust_ip)
                || SucuriScan::isValidCIDR($trust_ip)
            ) {
                $cache = new SucuriScanCache('trustip');
                $ip_info = SucuriScan::getIPInfo($trust_ip);
                $ip_info['added_at'] = SucuriScan::localTime();
                $cache_key = md5($ip_info['remote_addr']);

                if ($cache->exists($cache_key)) {
                    SucuriScanInterface::error('The IP address specified was already trusted.');
                } elseif ($cache->add($cache_key, $ip_info)) {
                    $message = 'Changes from <code>' . $trust_ip . '</code> will be ignored';

                    SucuriScanEvent::reportWarningEvent($message);
                    SucuriScanInterface::info($message);
                } else {
                    SucuriScanInterface::error('The new entry was not saved in the datastore file.');
                }
            }
        }

        // Trust and IP address to ignore notifications for a subnet.
        if ($del_trust_ip = SucuriScanRequest::post(':del_trust_ip', '_array')) {
            $cache = new SucuriScanCache('trustip');

            foreach ($del_trust_ip as $cache_key) {
                $cache->delete($cache_key);
            }

            SucuriScanInterface::info('The IP addresses selected were deleted successfully.');
        }

        // Update the settings for the heartbeat API.
        if ($heartbeat_status = SucuriScanRequest::post(':heartbeat_status')) {
            $statuses_allowed = SucuriScanHeartbeat::statusesAllowed();

            if (array_key_exists($heartbeat_status, $statuses_allowed)) {
                $message = 'Heartbeat status set to <code>' . $heartbeat_status . '</code>';

                SucuriScanOption::updateOption(':heartbeat', $heartbeat_status);
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Heartbeat status not allowed.');
            }
        }

        // Update the value of the heartbeat pulse.
        if ($heartbeat_pulse = SucuriScanRequest::post(':heartbeat_pulse')) {
            $pulses_allowed = SucuriScanHeartbeat::pulsesAllowed();

            if (array_key_exists($heartbeat_pulse, $pulses_allowed)) {
                $message = 'Heartbeat pulse set to <code>' . $heartbeat_pulse . '</code> seconds.';

                SucuriScanOption::updateOption(':heartbeat_pulse', $heartbeat_pulse);
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Heartbeat pulse not allowed.');
            }
        }

        // Update the value of the heartbeat interval.
        if ($heartbeat_interval = SucuriScanRequest::post(':heartbeat_interval')) {
            $intervals_allowed = SucuriScanHeartbeat::intervalsAllowed();

            if (array_key_exists($heartbeat_interval, $intervals_allowed)) {
                $message = 'Heartbeat interval set to <code>' . $heartbeat_interval . '</code>';

                SucuriScanOption::updateOption(':heartbeat_interval', $heartbeat_interval);
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Heartbeat interval not allowed.');
            }
        }

        // Enable or disable the auto-start execution of heartbeat.
        if ($heartbeat_autostart = SucuriScanRequest::post(':heartbeat_autostart', '(en|dis)able')) {
            $action_d = $heartbeat_autostart . 'd';
            $message = 'Heartbeat auto-start was <code>' . $action_d . '</code>';

            SucuriScanOption::updateOption(':heartbeat_autostart', $action_d);
            SucuriScanEvent::reportInfoEvent($message);
            SucuriScanInterface::info($message);
        }
    }
}
