<?php

/**
 * Code related to the settings-alerts.php interface.
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
 * Returns the HTML to configure who receives the email alerts.
 *
 * By default the plugin sends the email notifications about the security events
 * to the first email address used during the installation of the website. This
 * is usually the email of the website owner. The plugin allows to add more
 * emails to the list so the alerts are sent to other people.
 *
 * @param  bool $nonce True if the CSRF protection worked, false otherwise.
 * @return string      HTML for the email alert recipients.
 */
function sucuriscan_settings_alerts_recipients($nonce)
{
    $params = array();
    $params['Alerts.Recipients'] = '';
    $notify_to = SucuriScanOption::getOption(':notify_to');
    $emails = array();

    // If the recipient list is not empty, explode.
    if (is_string($notify_to)) {
        $emails = explode(',', $notify_to);
    }

    // Process form submission.
    if ($nonce) {
        // Add new email address to the alert recipient list.
        if (SucuriScanRequest::post(':save_recipient') !== false) {
            $new_email = SucuriScanRequest::post(':recipient');

            if (SucuriScan::isValidEmail($new_email)) {
                $emails[] = $new_email;
                $message = sprintf(__('The email alerts will be sent to: <code>%s</code>', 'sucuri-scanner'), $new_email);

                SucuriScanOption::updateOption(':notify_to', implode(',', $emails));
                SucuriScanEvent::reportInfoEvent(sprintf(__('The email alerts will be sent to: %s', 'sucuri-scanner'), $new_email));
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error(__('Email format not supported.', 'sucuri-scanner'));
            }
        }

        // Delete one or more recipients from the list.
        if (SucuriScanRequest::post(':delete_recipients') !== false) {
            $deleted_emails = array();
            $recipients = SucuriScanRequest::post(':recipients', '_array');

            foreach ($recipients as $address) {
                if (in_array($address, $emails)) {
                    $deleted_emails[] = $address;
                    $index = array_search($address, $emails);
                    unset($emails[$index]);
                }
            }

            if (!empty($deleted_emails)) {
                $deleted_emails_str = implode(",\x20", $deleted_emails);
                $message = sprintf(__('These emails will stop receiving alerts: <code>%s</code>', 'sucuri-scanner'), $deleted_emails_str);

                SucuriScanOption::updateOption(':notify_to', implode(',', $emails));
                SucuriScanEvent::reportInfoEvent(sprintf(__('These emails will stop receiving alerts: %s', 'sucuri-scanner'), $deleted_emails_str));
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }

        // Debug ability of the plugin to send email alerts correctly.
        if (SucuriScanRequest::post(':debug_email')) {
            $recipients = SucuriScanOption::getOption(':notify_to');
            $mailSentSuccess = SucuriScanMail::sendMail(
                $recipients,
                __('Test Email Alert', 'sucuri-scanner'),
                sprintf(__('Test email alert sent at %s', 'sucuri-scanner'), SucuriScan::datetime()),
                array('Force' => true)
            );

            if ($mailSentSuccess) {
                SucuriScanInterface::info(__('A test alert was sent to your email, check your inbox', 'sucuri-scanner'));
            }else {
                SucuriScanInterface::error(__('An error occurred sending email - please check your server mail configuration.', 'sucuri-scanner'));
            }

        }
    }


    foreach ($emails as $email) {
        if (!empty($email)) {
            $params['Alerts.Recipients'] .= SucuriScanTemplate::getSnippet(
                'settings-alerts-recipients',
                array('Recipient.Email' => $email)
            );
        }
    }

    return SucuriScanTemplate::getSection('settings-alerts-recipients', $params);
}

/**
 * Returns the HTML to configure the list of trusted IPs.
 *
 * The plugin will not report security events coming from these IP addresses. If
 * the users are all from the same network, like in an office, they can include
 * the IP of the entire LAN as a valid CIDR format.
 *
 * @return string HTML for the trusted IP addresses.
 */
function sucuriscan_settings_alerts_trustedips()
{
    $params = array();
    $params['TrustedIPs.List'] = '';
    $params['TrustedIPs.NoItems.Visibility'] = 'visible';

    $cache = new SucuriScanCache('trustip');

    if (SucuriScanInterface::checkNonce()) {
        // Trust and IP address to ignore alerts for a subnet.
        $trust_ip = SucuriScanRequest::post(':trust_ip');

        if ($trust_ip) {
            if (SucuriScan::isValidIP($trust_ip) || SucuriScan::isValidCIDR($trust_ip)) {
                $ip_info = SucuriScan::getIPInfo($trust_ip);
                $cache_key = md5($ip_info['remote_addr']);
                $ip_info['added_at'] = time();

                if ($cache->exists($cache_key)) {
                    SucuriScanInterface::error(__('The IP specified address was already added.', 'sucuri-scanner'));
                } elseif ($cache->add($cache_key, $ip_info)) {
                    SucuriScanEvent::reportWarningEvent(sprintf(__('IP has been trusted: %s', 'sucuri-scanner'), $trust_ip));
                    SucuriScanInterface::info(sprintf(__('Events generated from this IP will be ignored: <code>%s</code>', 'sucuri-scanner'), $trust_ip));
                } else {
                    SucuriScanInterface::error(__('The IP address could not be added to the trusted list', 'sucuri-scanner'));
                }
            }
        }

        // Trust and IP address to ignore alerts for a subnet.
        $del_trust_ip = SucuriScanRequest::post(':del_trust_ip', '_array');

        if ($del_trust_ip) {
            foreach ($del_trust_ip as $cache_key) {
                $cache->delete($cache_key);
            }

            SucuriScanInterface::info(__('The selected IP addresses were successfully deleted.', 'sucuri-scanner'));
        }
    }

    $trusted_ips = $cache->getAll();

    if ($trusted_ips) {
        foreach ($trusted_ips as $cache_key => $ip_info) {
            if ($ip_info->cidr_range == 32) {
                $ip_info->cidr_format = __('n/a', 'sucuri-scanner');
            }

            $params['TrustedIPs.List'] .= SucuriScanTemplate::getSnippet(
                'settings-alerts-trustedips',
                array(
                    'TrustIP.CacheKey' => $cache_key,
                    'TrustIP.RemoteAddr' => SucuriScan::escape($ip_info->remote_addr),
                    'TrustIP.CIDRFormat' => SucuriScan::escape($ip_info->cidr_format),
                    'TrustIP.AddedAt' => SucuriScan::datetime($ip_info->added_at),
                )
            );
        }

        $params['TrustedIPs.NoItems.Visibility'] = 'hidden';
    }

    return SucuriScanTemplate::getSection('settings-alerts-trustedips', $params);
}

/**
 * Returns the HTML to configure the subject for the email alerts.
 *
 * @param bool $nonce True if the CSRF protection worked, false otherwise.
 * @return string HTML for the email alert subject option.
 */
function sucuriscan_settings_alerts_subject($nonce)
{
    $params = array(
        'Alerts.Subject' => '',
        'Alerts.CustomChecked' => '',
        'Alerts.CustomValue' => '',
    );

    $subjects = array(
        sprintf(__('Sucuri Alert, %s, %s', 'sucuri-scanner'), ':domain', ':event'),
        sprintf(__('Sucuri Alert, %s, %s, %s', 'sucuri-scanner'), ':domain', ':event', ':remoteaddr'),
        sprintf(__('Sucuri Alert, %s, %s, %s', 'sucuri-scanner'), ':domain', ':event', ':username'),
        sprintf(__('Sucuri Alert, %s, %s, %s', 'sucuri-scanner'), ':domain', ':event', ':email'),
        sprintf(__('Sucuri Alert, %s, %s', 'sucuri-scanner'), ':event', ':remoteaddr'),
        sprintf(__('Sucuri Alert, %s, %s', 'sucuri-scanner'), ':event', ':hostname'),
        sprintf(__('Sucuri Alert, %s', 'sucuri-scanner'), ':event'),
    );

    // Process form submission to change the alert settings.
    if ($nonce) {
        $email_subject = SucuriScanRequest::post(':email_subject');

        if ($email_subject) {
            $current_value = SucuriScanOption::getOption(':email_subject');
            $new_subject = false;

            /**
             * Validate the format of the email subject format.
             *
             * If the user chooses the option to build the subject of the email alerts
             * manually we will need to validate the characters. Otherwise we will need to
             * check if the pseudo-tags selected by the user are allowed and supported.
             */
            if ($email_subject === 'custom') {
                $format_pattern = '/^[0-9a-zA-Z:,\s]+$/';
                $custom_subject = SucuriScanRequest::post(':custom_email_subject');

                if ($custom_subject !== false
                    && !empty($custom_subject)
                    && @preg_match($format_pattern, $custom_subject)
                ) {
                    $new_subject = trim($custom_subject);
                } else {
                    SucuriScanInterface::error(__('Invalid characters in the email subject.', 'sucuri-scanner'));
                }
            } elseif (is_array($subjects) && in_array($email_subject, $subjects)) {
                $new_subject = trim($email_subject);
            }

            // Proceed with the operation saving the new subject.
            if ($new_subject !== false && $current_value !== $new_subject) {
                $message = sprintf(__('Email subject set to <code>%s</code>', 'sucuri-scanner'), $new_subject);

                SucuriScanOption::updateOption(':email_subject', $new_subject);
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info(__('The email subject has been successfully updated', 'sucuri-scanner'));
            }
        }
    }

    // Build the HTML code for the interface.
    if (is_array($subjects)) {
        $email_subject = SucuriScanOption::getOption(':email_subject');
        $is_official_subject = false;

        foreach ($subjects as $subject_format) {
            if ($email_subject === $subject_format) {
                $is_official_subject = true;
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }

            $params['Alerts.Subject'] .= SucuriScanTemplate::getSnippet(
                'settings-alerts-subject',
                array(
                    'EmailSubject.Name' => $subject_format,
                    'EmailSubject.Value' => $subject_format,
                    'EmailSubject.Checked' => $checked,
                )
            );
        }

        if ($is_official_subject === false) {
            $params['Alerts.CustomChecked'] = 'checked="checked"';
            $params['Alerts.CustomValue'] = $email_subject;
        }
    }

    return SucuriScanTemplate::getSection('settings-alerts-subject', $params);
}

/**
 * Returns the HTML to configure the maximum number of alerts per hour.
 *
 * @param bool $nonce True if the CSRF protection worked, false otherwise.
 * @return string HTML for the maximum number of alerts per hour.
 */
function sucuriscan_settings_alerts_perhour($nonce)
{
    $params = array();
    $params['Alerts.PerHour'] = '';

    $emails_per_hour = array(
        '5' => __('Maximum 5 per hour', 'sucuri-scanner'),
        '10' => __('Maximum 10 per hour', 'sucuri-scanner'),
        '20' => __('Maximum 20 per hour', 'sucuri-scanner'),
        '40' => __('Maximum 40 per hour', 'sucuri-scanner'),
        '80' => __('Maximum 80 per hour', 'sucuri-scanner'),
        '160' => __('Maximum 160 per hour', 'sucuri-scanner'),
        'unlimited' => __('Unlimited alerts per hour', 'sucuri-scanner'),
    );

    if ($nonce) {
        // Update the value for the maximum emails per hour.
        $per_hour = SucuriScanRequest::post(':emails_per_hour');

        if ($per_hour) {
            if (array_key_exists($per_hour, $emails_per_hour)) {
                $per_hour_label = strtolower($emails_per_hour[$per_hour]);
                $message = sprintf(__('Maximum alerts per hour set to <code>%s</code>', 'sucuri-scanner'), $per_hour_label);

                SucuriScanOption::updateOption(':emails_per_hour', $per_hour);
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info(__('The maximum number of alerts per hour has been updated', 'sucuri-scanner'));
            } else {
                SucuriScanInterface::error(__('Error updating the maximum number of alerts per hour', 'sucuri-scanner'));
            }
        }
    }

    $per_hour = (int) SucuriScanOption::getOption(':emails_per_hour');
    $per_hour_options = SucuriScanTemplate::selectOptions($emails_per_hour, $per_hour);
    $params['Alerts.PerHour'] = $per_hour_options;

    return SucuriScanTemplate::getSection('settings-alerts-perhour', $params);
}

/**
 * Returns the HTML to configure the trigger for the brute-force alerts.
 *
 * @param bool $nonce True if the CSRF protection worked, false otherwise.
 * @return string HTML for the trigger for the brute-force alerts.
 */
function sucuriscan_settings_alerts_bruteforce($nonce)
{
    $params = array();
    $params['Alerts.BruteForce'] = '';

    $max_failed_logins = array(
        '30' => __('30 failed logins per hour', 'sucuri-scanner'),
        '60' => __('60 failed logins per hour', 'sucuri-scanner'),
        '120' => __('120 failed logins per hour', 'sucuri-scanner'),
        '240' => __('240 failed logins per hour', 'sucuri-scanner'),
        '480' => __('480 failed logins per hour', 'sucuri-scanner'),
    );

    if ($nonce) {
        // Update the maximum failed logins per hour before consider it a brute-force attack.
        $maximum = SucuriScanRequest::post(':maximum_failed_logins');

        if ($maximum) {
            if (array_key_exists($maximum, $max_failed_logins)) {
                $message = sprintf(__('Consider brute-force attack after <code>%s</code> failed logins per hour', 'sucuri-scanner'), $maximum);

                SucuriScanOption::updateOption(':maximum_failed_logins', $maximum);
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info(sprintf(__('The plugin will assume that your website is under a brute-force attack after %s failed logins are detected during the same hour', 'sucuri-scanner'), $maximum));
            } else {
                SucuriScanInterface::error(__('Invalid number of failed logins per hour', 'sucuri-scanner'));
            }
        }
    }

    $maximum = (int) SucuriScanOption::getOption(':maximum_failed_logins');
    $maximum_options = SucuriScanTemplate::selectOptions($max_failed_logins, $maximum);
    $params['Alerts.BruteForce'] = $maximum_options;

    return SucuriScanTemplate::getSection('settings-alerts-bruteforce', $params);
}

/**
 * Returns the HTML to configure which alerts will be sent.
 *
 * @param bool $nonce True if the CSRF protection worked, false otherwise.
 * @return string HTML for the alerts that will be sent.
 */
function sucuriscan_settings_alerts_events($nonce)
{
    $params = array();
    $params['Alerts.Events'] = '';
    $params['Alerts.NoAlertsVisibility'] = 'hidden';

    $notify_options = array(
        'sucuriscan_notify_plugin_change' => 'setting:' . __('Receive email alerts for changes in the settings of the plugin', 'sucuri-scanner'),
        'sucuriscan_prettify_mails' => 'setting:' . __('Receive email alerts in HTML <em>(there may be issues with some mail services)</em>', 'sucuri-scanner'),
        'sucuriscan_use_wpmail' => 'setting:' . __('Use WordPress functions to send mails <em>(uncheck to use native PHP functions)</em>', 'sucuri-scanner'),
        'sucuriscan_lastlogin_redirection' => 'setting:' . __('Allow redirection after login to report the last-login information', 'sucuri-scanner'),
        'sucuriscan_notify_scan_checksums' => 'setting:' . __('Receive email alerts for core integrity checks', 'sucuri-scanner'),
        'sucuriscan_notify_available_updates' => 'setting:' . __('Receive email alerts for available updates', 'sucuri-scanner'),
        'sucuriscan_notify_user_registration' => 'user:' . __('Receive email alerts for new user registration', 'sucuri-scanner'),
        'sucuriscan_notify_success_login' => 'user:' . __('Receive email alerts for successful login attempts', 'sucuri-scanner'),
        'sucuriscan_notify_failed_login' => 'user:' . __('Receive email alerts for failed login attempts <em>(you may receive tons of emails)</em>', 'sucuri-scanner'),
        'sucuriscan_notify_bruteforce_attack' => 'user:' . __('Receive email alerts for password guessing attacks <em>(summary of failed logins per hour)</em>', 'sucuri-scanner'),
        'sucuriscan_notify_post_publication' => 'setting:' . __('Receive email alerts for changes in the post status <em>(configure from Ignore Posts Changes)</em>', 'sucuri-scanner'),
        'sucuriscan_notify_website_updated' => 'setting:' . __('Receive email alerts when the WordPress version is updated', 'sucuri-scanner'),
        'sucuriscan_notify_settings_updated' => 'setting:' . __('Receive email alerts when your website settings are updated', 'sucuri-scanner'),
        'sucuriscan_notify_theme_editor' => 'setting:' . __('Receive email alerts when a file is modified with theme/plugin editor', 'sucuri-scanner'),
        'sucuriscan_notify_plugin_installed' => 'plugin:' . __('Receive email alerts when a <b>plugin is installed</b>', 'sucuri-scanner'),
        'sucuriscan_notify_plugin_activated' => 'plugin:' . __('Receive email alerts when a <b>plugin is activated</b>', 'sucuri-scanner'),
        'sucuriscan_notify_plugin_deactivated' => 'plugin:' . __('Receive email alerts when a <b>plugin is deactivated</b>', 'sucuri-scanner'),
        'sucuriscan_notify_plugin_updated' => 'plugin:' . __('Receive email alerts when a <b>plugin is updated</b>', 'sucuri-scanner'),
        'sucuriscan_notify_plugin_deleted' => 'plugin:' . __('Receive email alerts when a <b>plugin is deleted</b>', 'sucuri-scanner'),
        'sucuriscan_notify_widget_added' => 'widget:' . __('Receive email alerts when a <b>widget is added</b> to a sidebar', 'sucuri-scanner'),
        'sucuriscan_notify_widget_deleted' => 'widget:' . __('Receive email alerts when a <b>widget is deleted</b> from a sidebar', 'sucuri-scanner'),
        'sucuriscan_notify_theme_installed' => 'theme:' . __('Receive email alerts when a <b>theme is installed</b>', 'sucuri-scanner'),
        'sucuriscan_notify_theme_activated' => 'theme:' . __('Receive email alerts when a <b>theme is activated</b>', 'sucuri-scanner'),
        'sucuriscan_notify_theme_updated' => 'theme:' . __('Receive email alerts when a <b>theme is updated</b>', 'sucuri-scanner'),
        'sucuriscan_notify_theme_deleted' => 'theme:' . __('Receive email alerts when a <b>theme is deleted</b>', 'sucuri-scanner'),
    );

    /**
     * Hide successful and failed logins option.
     *
     * Due to an incompatibility with the Postman-SMTP plugin we cannot sent
     * email alerts when a successful or failed user authentication happens, the
     * result is an infinite loop while our plugin tries to notify about changes
     * in the posts and the other plugin creates temporary post objects to track
     * the emails.
     *
     * @date 30 June, 2017
     * @see https://wordpress.org/plugins/postman-smtp/
     * @see https://wordpress.org/support/topic/unable-to-access-wordpress-dashboard-after-update-to-1-8-7/
     */
    if (is_plugin_active('postman-smtp/postman-smtp.php')) {
        $params['Alerts.NoAlertsVisibility'] = 'visible';
        unset($notify_options['sucuriscan_notify_success_login']);
        unset($notify_options['sucuriscan_notify_failed_login']);
    }

    // Process form submission to change the alert settings.
    if ($nonce) {
        // Update the notification settings.
        if (SucuriScanRequest::post(':save_alert_events') !== false) {
            $ucounter = 0;

            foreach ($notify_options as $alert_type => $alert_label) {
                $option_value = SucuriScanRequest::post($alert_type, '(1|0)');

                if ($option_value !== false) {
                    $current_value = SucuriScanOption::getOption($alert_type);
                    $option_value = ($option_value == 1) ? 'enabled' : 'disabled';

                    // Check that the option value was actually changed.
                    if ($current_value !== $option_value) {
                        $written = SucuriScanOption::updateOption($alert_type, $option_value);
                        $ucounter += ($written === true) ? 1 : 0;
                    }
                }
            }

            if ($ucounter > 0) {
                $message = sprintf(__('A total of %s alert events were changed', 'sucuri-scanner'), $ucounter );

                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info(__('The alert settings have been updated', 'sucuri-scanner'));
            }
        }
    }

    /* build the HTML code for the checkbox input fields */
    foreach ($notify_options as $alert_type => $alert_label) {
        $alert_value = SucuriScanOption::getOption($alert_type);
        $checked = ($alert_value == 'enabled') ? 'checked="checked"' : '';
        $alert_icon = '';

        /* identify the optional icon */
        $offset = strpos($alert_label, ':');
        $alert_group = substr($alert_label, 0, $offset);
        $alert_label = substr($alert_label, $offset + 1);

        switch ($alert_group) {
            case 'user':
                $alert_icon = 'dashicons-before dashicons-admin-users';
                break;

            case 'plugin':
                $alert_icon = 'dashicons-before dashicons-admin-plugins';
                break;

            case 'theme':
                $alert_icon = 'dashicons-before dashicons-admin-appearance';
                break;

            case 'setting':
                $alert_icon = 'dashicons-before dashicons-admin-tools';
                break;

            case 'widget':
                $alert_icon = 'dashicons-before dashicons-admin-post';
                break;
        }

        $params['Alerts.Events'] .= SucuriScanTemplate::getSnippet(
            'settings-alerts-events',
            array(
                'Event.Name' => $alert_type,
                'Event.Checked' => $checked,
                'Event.Label' => $alert_label,
                'Event.LabelIcon' => $alert_icon,
            )
        );
    }

    return SucuriScanTemplate::getSection('settings-alerts-events', $params);
}

/**
 * Returns the HTML to configure the post-types that will be ignored.
 *
 * @return string HTML for the ignored post-types.
 */
function sucuriscan_settings_alerts_ignore_posts()
{
    $params = array();
    $post_types = SucuriScanOption::getPostTypes();
    $ignored_events = SucuriScanOption::getIgnoredEvents();

    $params['PostTypes.List'] = '';
    $params['PostTypes.ErrorVisibility'] = 'hidden';

    if (SucuriScanInterface::checkNonce()) {
        // Ignore a new event for email alerts.
        $action = SucuriScanRequest::post(':ignorerule_action');
        $ignore_rule = SucuriScanRequest::post(':ignorerule');
        $selected = SucuriScanRequest::post(':posttypes', '_array');

        if ($action === 'add') {
            if (!preg_match('/^[a-z0-9_\-]{1,20}+$/', $ignore_rule)) {
                SucuriScanInterface::error(__('Only lowercase letters, numbers, underscores and hyphens are allowed. Post Types cannot exceed 20 characters as well.', 'sucuri-scanner'));
            } elseif (array_key_exists($ignore_rule, $ignored_events)) {
                SucuriScanInterface::error(__('The post-type is already being ignored (duplicate).', 'sucuri-scanner'));
            } else {
                $ignored_events[$ignore_rule] = time();

                SucuriScanInterface::info(__('Post-type has been successfully ignored.', 'sucuri-scanner'));
                SucuriScanOption::updateOption(':ignored_events', $ignored_events);
                SucuriScanEvent::reportWarningEvent(sprintf(__('Changes in <code>%s</code> post-type will be ignored', 'sucuri-scanner'), $ignore_rule));
            }
        }

        if ($action === 'batch') {
            /* reset current data to start all over again */
            $ignored_events = array();
            $timestamp = time();

            foreach ($post_types as $post_type) {
                if (is_array($selected) && !in_array($post_type, $selected)) {
                    $ignored_events[$post_type] = $timestamp;
                }
            }

            SucuriScanInterface::info(__('List of monitored post-types has been updated.', 'sucuri-scanner'));
            SucuriScanOption::updateOption(':ignored_events', $ignored_events);
            SucuriScanEvent::reportWarningEvent(__('List of monitored post-types has been updated', 'sucuri-scanner'));
        }
    }

    /* notifications are post updates are disabled; print error */
    if (SucuriScanOption::isDisabled(':notify_post_publication')) {
        $params['PostTypes.ErrorVisibility'] = 'visible';
        $params['PostTypes.List'] = sprintf('<tr><td colspan="4">%s</td></tr>', __('no data available', 'sucuri-scanner'));

        return SucuriScanTemplate::getSection('settings-alerts-ignore-posts', $params);
    }

    /* Check which post-types are being ignored */
    foreach ($post_types as $post_type) {
        $was_ignored_at = '--';
        $selected = 'checked="checked"';
        $post_type_title = ucwords(str_replace('_', "\x20", $post_type));

        if (array_key_exists($post_type, $ignored_events)) {
            $was_ignored_at = SucuriScan::datetime($ignored_events[$post_type]);
            $selected = ''; /* uncheck the HTML checkbox */
        }

        $params['PostTypes.List'] .= SucuriScanTemplate::getSnippet(
            'settings-alerts-ignore-posts',
            array(
                'PostTypes.Selected' => $selected,
                'PostTypes.UniqueID' => $post_type,
                'PostTypes.Title' => $post_type_title,
                'PostTypes.IgnoredAt' => $was_ignored_at,
            )
        );
    }

    return SucuriScanTemplate::getSection('settings-alerts-ignore-posts', $params);
}
