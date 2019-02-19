<?php

/**
 * Code related to the mail.lib.php interface.
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
 * Process and send emails.
 *
 * One of the core features of the plugin is the event alerts, a list of rules
 * will check if the site is being compromised, in which case a notification
 * will be sent to the site email address (an address that can be configured in
 * the settings page).
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanMail extends SucuriScanOption
{
    /**
     * Check whether the email alerts will be sent in HTML or Plain/Text.
     *
     * @return bool Whether the emails will be in HTML or Plain/Text.
     */
    public static function prettifyMails()
    {
        return self::isEnabled(':prettify_mails');
    }

    /**
     * Send a message to a specific email address.
     *
     * @param  string $email    The email address of the recipient that will receive the message.
     * @param  string $subject  The reason of the message that will be sent.
     * @param  string $message  Body of the message that will be sent.
     * @param  array  $data_set Optional parameter to add more information to the notification.
     * @return bool             Whether the email contents were sent successfully.
     */
    public static function sendMail($email = '', $subject = '', $message = '', $data_set = array())
    {
        $headers = array();
        $subject = ucwords(strtolower($subject));
        $force = (bool) (isset($data_set['Force']) && $data_set['Force']);
        unset($data_set['Force']); /* remove to prevent inheritance */

        /* check whether the email alerts will be sent in HTML or plain/text */
        if (self::prettifyMails() || (isset($data_set['ForceHTML']) && $data_set['ForceHTML'])) {
            $headers = array('Content-Type: text/html');
            $data_set['PrettifyType'] = 'pretty';
            unset($data_set['ForceHTML']);
        } else {
            $headers = array('Content-Type: text/plain');
            $message = strip_tags($message);
        }

        if (self::emailsPerHourReached() && !$force) {
            return self::throwException(__('Maximum number of emails per hour reached', 'sucuri-scanner'));
        }

        /* check if we need to load a template file to wrap the message */
        if (!array_key_exists('UseRawHTML', $data_set) || !$data_set['UseRawHTML']) {
            $message = self::prettifyMail($subject, $message, $data_set);
        }

        $subject = self::getEmailSubject($subject);

        /**
         * WordPress uses a library named PHPMailer to send emails through the
         * provided method wp_mail, unfortunately the debug information is
         * completely removed and this makes it difficult to troubleshoots
         * issues reported by users when the SMTP server in their sites is
         * misconfigured. To reduce the number of tickets related with this
         * issue we will provide an option to allow the users to choose which
         * technique will be used to send the alerts.
         *
         * @see https://github.com/PHPMailer/PHPMailer
         * @see https://developer.wordpress.org/reference/functions/wp_mail/
         */
        if (SucuriScanOption::isEnabled(':use_wpmail')) {
            wp_mail($email, $subject, $message, $headers);
        } else {
            @mail($email, $subject, $message, implode("\r\n", $headers));
        }

        $mails_sent = (int) self::getOption(':emails_sent');
        self::updateOption(':emails_sent', $mails_sent + 1);
        self::updateOption(':last_email_at', time());

        return true; /* assume mail delivery */
    }

    /**
     * Generate a subject for the email alerts.
     *
     * @param  string $event The reason of the message that will be sent.
     * @return string        A text with the subject for the email alert.
     */
    private static function getEmailSubject($event = '')
    {
        $subject = self::getOption(':email_subject');
        $subject = strip_tags((string) $subject);
        $ip = self::getRemoteAddr();

        $subject = str_replace(':event', $event, $subject);
        $subject = str_replace(':domain', self::getDomain(), $subject);
        $subject = str_replace(':remoteaddr', $ip, $subject);

        if (strpos($subject, ':hostname') !== false) {
            /* expensive operation; reverse user ip address if requested */
            $subject = str_replace(':hostname', gethostbyaddr($ip), $subject);
        }

        /* include data from the user in session, if necessary */
        if (strpos($subject, ':username') !== false
            || strpos($subject, ':email') !== false
        ) {
            $user = wp_get_current_user();
            $username = 'unknown';
            $eaddress = 'unknown';

            if ($user instanceof WP_User
                && isset($user->user_login)
                && isset($user->user_email)
            ) {
                $username = $user->user_login;
                $eaddress = $user->user_email;
            }

            $subject = str_replace(':username', $user->user_login, $subject);
            $subject = str_replace(':email', $user->user_email, $subject);
        }

        return $subject;
    }

    /**
     * Generate a HTML version of the message that will be sent through an email.
     *
     * @param  string $subject  The reason of the message that will be sent.
     * @param  string $message  Body of the message that will be sent.
     * @param  array  $data_set Optional parameter to add more information to the notification.
     * @return string           The message formatted in a HTML template.
     */
    private static function prettifyMail($subject = '', $message = '', $data_set = array())
    {
        $params = array();
        $display_name = '';
        $prettify_type = 'simple';
        $user = wp_get_current_user();
        $website = self::getDomain();

        if (isset($data_set['PrettifyType'])) {
            $prettify_type = $data_set['PrettifyType'];
        }

        if ($user instanceof WP_User
            && isset($user->user_login)
            && !empty($user->user_login)
        ) {
            $display_name = sprintf(
                __('User: %s (%s)', 'sucuri-scanner'),
                $user->display_name,
                $user->user_login
            );
        }

        // Format list of items when the event has multiple entries.
        if (strpos($message, 'multiple') !== false) {
            $message_parts = SucuriScanAPI::parseMultipleEntries($message);

            if (is_array($message_parts)) {
                $message = ( $prettify_type == 'pretty' ) ? $message_parts[0] . '<ul>' : $message_parts[0];
                unset($message_parts[0]);

                foreach ($message_parts as $msg_part) {
                    if ($prettify_type == 'pretty') {
                        $message .= sprintf("<li>%s</li>\n", $msg_part);
                    } else {
                        $message .= sprintf("\n- %s", $msg_part);
                    }
                }

                $message .= ( $prettify_type == 'pretty' ) ? '</ul>' : '';
            }
        }

        $params['TemplateTitle'] = __('Sucuri Alert', 'sucuri-scanner');
        $params['Subject'] = $subject;
        $params['Website'] = $website;
        $params['RemoteAddress'] = self::getRemoteAddr();
        $params['ReverseAddress'] = gethostbyaddr($params['RemoteAddress']);
        $params['Message'] = $message;
        $params['User'] = $display_name;
        $params['Time'] = SucuriScan::datetime();

        foreach ($data_set as $var_key => $var_value) {
            $params[ $var_key ] = $var_value;
        }

        /* SucuriScanTemplate::notification-pretty */
        /* SucuriScanTemplate::notification-simple */
        return SucuriScanTemplate::getSection('notification-' . $prettify_type, $params);
    }

    /**
     * Check whether the maximum quantity of emails per hour was reached.
     *
     * @return bool Whether the quota emails per hour was reached.
     */
    private static function emailsPerHourReached()
    {
        $max_per_hour = self::getOption(':emails_per_hour');

        if ($max_per_hour != 'unlimited') {
            // Check if we are still in that sixty minutes.
            $current_time = time();
            $last_email_at = self::getOption(':last_email_at');
            $diff_time = abs($current_time - $last_email_at);

            if ($diff_time <= 3600) {
                // Check if the quantity of emails sent is bigger than the configured.
                $emails_sent = (int) self::getOption(':emails_sent');
                $max_per_hour = intval($max_per_hour);

                if ($emails_sent >= $max_per_hour) {
                    return true;
                }
            } else {
                // Reset the counter of emails sent.
                self::updateOption(':emails_sent', 0);
            }
        }

        return false;
    }
}
