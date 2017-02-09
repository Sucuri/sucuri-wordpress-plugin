<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Read and parse the content of the notification settings template.
 *
 * @return string Parsed HTML code for the notification settings panel.
 */
function sucuriscan_settings_alert($nonce)
{
    $params = array();

    $params['AlertSettings.Recipients'] = sucuriscan_settings_alert_recipients($nonce);
    $params['AlertSettings.Subject'] = sucuriscan_settings_alert_subject($nonce);
    $params['AlertSettings.PerHour'] = sucuriscan_settings_alert_perhour($nonce);
    $params['AlertSettings.BruteForce'] = sucuriscan_settings_alert_bruteforce($nonce);
    $params['AlertSettings.Events'] = sucuriscan_settings_alert_events($nonce);

    return SucuriScanTemplate::getSection('settings-alert', $params);
}

function sucuriscan_settings_alert_recipients($nonce)
{
    $params = array();
    $params['AlertSettings.Recipients'] = '';
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
                $message = 'Sucuri will send email alerts to: <code>' . $new_email . '</code>';

                SucuriScanOption::updateOption(':notify_to', implode(',', $emails));
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Email format not supported.');
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
                $message = 'Sucuri will not send email alerts to: <code>' . $deleted_emails_str . '</code>';

                SucuriScanOption::updateOption(':notify_to', implode(',', $emails));
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }

        // Debug ability of the plugin to send email alerts correctly.
        if (SucuriScanRequest::post(':debug_email')) {
            $recipients = SucuriScanOption::getOption(':notify_to');
            SucuriScanMail::sendMail(
                $recipients,
                'Test Email Alert',
                sprintf('Test email alert sent at %s', date('r')),
                array('Force' => true)
            );
            SucuriScanInterface::info('Test email alert sent, check your inbox.');
        }
    }

    $counter = 0;

    foreach ($emails as $email) {
        if (!empty($email)) {
            $css_class = ($counter % 2 === 0) ? '' : 'alternate';
            $params['AlertSettings.Recipients'] .= SucuriScanTemplate::getSnippet(
                'settings-alert-recipients',
                array(
                    'Recipient.CssClass' => $css_class,
                    'Recipient.Email' => $email,
                )
            );
            $counter++;
        }
    }

    return SucuriScanTemplate::getSection('settings-alert-recipients', $params);
}

function sucuriscan_settings_alert_subject($nonce)
{
    global $sucuriscan_email_subjects;

    $params = array(
        'AlertSettings.Subject' => '',
        'AlertSettings.CustomChecked' => '',
        'AlertSettings.CustomValue' => '',
    );

    // Process form submission to change the alert settings.
    if ($nonce) {
        if ($email_subject = SucuriScanRequest::post(':email_subject')) {
            $current_value = SucuriScanOption::getOption(':email_subject');
            $new_email_subject = false;

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
                    $new_email_subject = trim($custom_subject);
                } else {
                    SucuriScanInterface::error('Invalid characters in the email subject.');
                }
            } elseif (is_array($sucuriscan_email_subjects)
                && in_array($email_subject, $sucuriscan_email_subjects)
            ) {
                $new_email_subject = trim($email_subject);
            }

            // Proceed with the operation saving the new subject.
            if ($new_email_subject !== false
                && $current_value !== $new_email_subject
            ) {
                $message = 'Email subject set to <code>' . $new_email_subject . '</code>';

                SucuriScanOption::updateOption(':email_subject', $new_email_subject);
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }
    }

    // Build the HTML code for the interface.
    if (is_array($sucuriscan_email_subjects)) {
        $email_subject = SucuriScanOption::getOption(':email_subject');
        $is_official_subject = false;

        foreach ($sucuriscan_email_subjects as $subject_format) {
            if ($email_subject === $subject_format) {
                $is_official_subject = true;
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }

            $params['AlertSettings.Subject'] .= SucuriScanTemplate::getSnippet(
                'settings-alert-subject',
                array(
                    'EmailSubject.Name' => $subject_format,
                    'EmailSubject.Value' => $subject_format,
                    'EmailSubject.Checked' => $checked,
                )
            );
        }

        if ($is_official_subject === false) {
            $params['AlertSettings.CustomChecked'] = 'checked="checked"';
            $params['AlertSettings.CustomValue'] = $email_subject;
        }
    }

    return SucuriScanTemplate::getSection('settings-alert-subject', $params);
}

function sucuriscan_settings_alert_perhour($nonce)
{
    global $sucuriscan_emails_per_hour;

    $params = array();
    $params['AlertSettings.PerHour'] = '';

    if ($nonce) {
        // Update the value for the maximum emails per hour.
        if ($per_hour = SucuriScanRequest::post(':emails_per_hour')) {
            if (array_key_exists($per_hour, $sucuriscan_emails_per_hour)) {
                $per_hour_label = strtolower($sucuriscan_emails_per_hour[$per_hour]);
                $message = 'Maximum alerts per hour set to <code>' . $per_hour_label . '</code>';

                SucuriScanOption::updateOption(':emails_per_hour', $per_hour);
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Invalid value for the maximum emails per hour.');
            }
        }
    }

    $per_hour = SucuriScanOption::getOption(':emails_per_hour');
    $per_hour_options = SucuriScanTemplate::selectOptions($sucuriscan_emails_per_hour, $per_hour);
    $params['AlertSettings.PerHour'] = $per_hour_options;

    return SucuriScanTemplate::getSection('settings-alert-perhour', $params);
}

function sucuriscan_settings_alert_bruteforce($nonce)
{
    global $sucuriscan_maximum_failed_logins;

    $params = array();
    $params['AlertSettings.BruteForce'] = '';

    if ($nonce) {
        // Update the maximum failed logins per hour before consider it a brute-force attack.
        if ($maximum = SucuriScanRequest::post(':maximum_failed_logins')) {
            if (array_key_exists($maximum, $sucuriscan_maximum_failed_logins)) {
                $message = 'Consider brute-force attack after <code>' . $maximum . '</code> failed logins per hour';

                SucuriScanOption::updateOption(':maximum_failed_logins', $maximum);
                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            } else {
                SucuriScanInterface::error('Invalid value for the brute-force alerts.');
            }
        }
    }

    $maximum = SucuriScanOption::getOption(':maximum_failed_logins');
    $maximum_options = SucuriScanTemplate::selectOptions($sucuriscan_maximum_failed_logins, $maximum);
    $params['AlertSettings.BruteForce'] = $maximum_options;

    return SucuriScanTemplate::getSection('settings-alert-bruteforce', $params);
}

function sucuriscan_settings_alert_events($nonce)
{
    global $sucuriscan_notify_options;

    $params = array();
    $params['AlertSettings.Events'] = '';

    // Process form submission to change the alert settings.
    if ($nonce) {
        // Update the notification settings.
        if (SucuriScanRequest::post(':save_alert_events') !== false) {
            $ucounter = 0;

            foreach ($sucuriscan_notify_options as $alert_type => $alert_label) {
                $option_value = SucuriScanRequest::post($alert_type, '(1|0)');

                if ($option_value !== false) {
                    $current_value = SucuriScanOption::getOption($alert_type);
                    $option_value = ($option_value == 1) ? 'enabled' : 'disabled';

                    // Check that the option value was actually changed.
                    if ($current_value !== $option_value) {
                        $written = SucuriScanOption::updateOption($alert_type, $option_value);

                        if ($written === true) {
                            $ucounter += 1;
                        }
                    }
                }
            }

            if ($ucounter > 0) {
                $message = 'A total of ' . $ucounter . ' alert events were changed';

                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info($message);
            }
        }
    }

    // Build the HTML code for the interface.
    if (is_array($sucuriscan_notify_options)) {
        $pattern = '/^([a-z]+:)?(.+)/';
        $counter = 0;

        foreach ($sucuriscan_notify_options as $alert_type => $alert_label) {
            $alert_value = SucuriScanOption::getOption($alert_type);
            $checked = ($alert_value == 'enabled') ? 'checked="checked"' : '';
            $css_class = ($counter % 2 === 0) ? 'alternate' : '';
            $alert_icon = '';

            if (@preg_match($pattern, $alert_label, $match)) {
                $alert_group = str_replace(':', '', $match[1]);
                $alert_label = $match[2];

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
                }
            }

            $params['AlertSettings.Events'] .= SucuriScanTemplate::getSnippet(
                'settings-alert-events',
                array(
                    'Event.CssClass' => $css_class,
                    'Event.Name' => $alert_type,
                    'Event.Checked' => $checked,
                    'Event.Label' => $alert_label,
                    'Event.LabelIcon' => $alert_icon,
                )
            );
            $counter++;
        }
    }

    return SucuriScanTemplate::getSection('settings-alert-events', $params);
}
