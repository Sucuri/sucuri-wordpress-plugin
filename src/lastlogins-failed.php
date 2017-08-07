<?php

/**
 * Code related to the lastlogins-failed.php interface.
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
 * Print a list with the failed logins occurred during the last hour.
 *
 * @return string A list with the failed logins occurred during the last hour.
 */
function sucuriscan_failed_logins_panel()
{
    $template_variables = array(
        'FailedLogins.List' => '',
        'FailedLogins.Total' => '',
        'FailedLogins.MaxFailedLogins' => 0,
        'FailedLogins.NoItemsVisibility' => 'visible',
        'FailedLogins.WarningVisibility' => 'visible',
        'FailedLogins.PaginationLinks' => '',
        'FailedLogins.PaginationVisibility' => 'hidden',
    );

    if (SucuriScanInterface::checkNonce()) {
        $blockUsers = SucuriScanRequest::post(':block_user', '_array');

        if (is_array($blockUsers) && !empty($blockUsers)) {
            SucuriScanBlockedUsers::block($blockUsers);
            SucuriScanInterface::info('Selected user accounts were blocked');
        }
    }

    // Define variables for the pagination.
    $page_number = SucuriScanTemplate::pageNumber();
    $max_per_page = SUCURISCAN_MAX_PAGINATION_BUTTONS;
    $page_offset = ($page_number - 1) * $max_per_page;
    $page_limit = ($page_offset + $max_per_page);

    $max_failed_logins = SucuriScanOption::getOption(':maximum_failed_logins');
    $notify_bruteforce_attack = SucuriScanOption::getOption(':notify_bruteforce_attack');
    $failed_logins = sucuriscan_get_all_failed_logins($page_offset, $max_per_page);
    $show_password = SucuriScanOption::isEnabled(':notify_failed_password');

    if ($failed_logins) {
        $counter = 0;

        for ($key = $page_offset; $key < $page_limit; $key++) {
            if (array_key_exists($key, $failed_logins['entries'])) {
                $login_data = $failed_logins['entries'][ $key ];

                if (!is_array($login_data)) {
                    continue;
                }

                $wrong_user_password = 'hidden';
                $wrong_user_password_color = 'default';

                if (isset($login_data['user_password']) && !empty($login_data['user_password'])) {
                    $wrong_user_password = $login_data['user_password'];
                    $wrong_user_password_color = 'danger';
                } else {
                    $wrong_user_password = 'empty';
                    $wrong_user_password_color = 'info';
                }

                if (!$show_password) {
                    $wrong_user_password = 'hidden';
                }

                $template_variables['FailedLogins.List'] .= SucuriScanTemplate::getSnippet(
                    'lastlogins-failedlogins',
                    array(
                        'FailedLogins.Num' => $login_data['attempt_count'],
                        'FailedLogins.Username' => $login_data['user_login'],
                        'FailedLogins.RemoteAddr' => $login_data['remote_addr'],
                        'FailedLogins.UserAgent' => $login_data['user_agent'],
                        'FailedLogins.Password' => $wrong_user_password,
                        'FailedLogins.PasswordColor' => $wrong_user_password_color,
                        'FailedLogins.Datetime' => SucuriScan::datetime($login_data['attempt_time']),
                    )
                );

                $counter++;
            }
        }

        if ($counter > 0) {
            $template_variables['FailedLogins.NoItemsVisibility'] = 'hidden';
        }

        $template_variables['FailedLogins.PaginationLinks'] = SucuriScanTemplate::pagination(
            '%%SUCURI.URL.Lastlogins%%#failed',
            $failed_logins['count'],
            $max_per_page
        );

        if ($failed_logins['count'] > $max_per_page) {
            $template_variables['FailedLogins.PaginationVisibility'] = 'visible';
        }
    }

    $template_variables['FailedLogins.MaxFailedLogins'] = $max_failed_logins;

    if ($notify_bruteforce_attack == 'enabled') {
        $template_variables['FailedLogins.WarningVisibility'] = 'hidden';
    }

    return SucuriScanTemplate::getSection('lastlogins-failedlogins', $template_variables);
}

/**
 * Find the full path of the file where the information of the failed logins
 * will be stored, it will be created automatically if does not exists (and if
 * the destination folder has permissions to write). This method can also be
 * used to reset the content of the datastore file.
 *
 * @see sucuriscan_reset_failed_logins()
 *
 * @param  bool $get_old_logs Whether the old logs will be retrieved or not.
 * @param  bool $reset        Whether the file will be resetted or not.
 * @return string|false       Absolute path to the file.
 */
function sucuriscan_failed_logins_datastore_path($get_old_logs = false, $reset = false)
{
    $file_name = $get_old_logs ? 'sucuri-oldfailedlogins.php' : 'sucuri-failedlogins.php';
    $datastore_path = SucuriScan::dataStorePath($file_name);
    $default_content = sucuriscan_failed_logins_default_content();

    // Create the file if it does not exists.
    if (!file_exists($datastore_path) || $reset) {
        @file_put_contents($datastore_path, $default_content, LOCK_EX);
    }

    // Return the datastore path if the file exists (or was created).
    if (is_readable($datastore_path)) {
        return $datastore_path;
    }

    return false;
}

/**
 * Default content of the datastore file where the failed logins are being kept.
 *
 * @return string Default content of the file.
 */
function sucuriscan_failed_logins_default_content()
{
    return "<?php exit(0); ?>\n";
}

/**
 * Returns failed logins data including old entries.
 *
 * @param  int $offset Initial index to start the array.
 * @param  int $limit  Number of items in the returned array.
 * @return array|false Failed logins data.
 */
function sucuriscan_get_all_failed_logins($offset = 0, $limit = -1)
{
    $all = array();
    $new = sucuriscan_get_failed_logins();
    $old = sucuriscan_get_failed_logins(true, $offset, $limit);

    if ($new && $old) {
        // Merge the new and old failed logins.
        $all = array();

        $all['first_attempt'] = $old['first_attempt'];
        $all['last_attempt'] = $new['last_attempt'];
        $all['count'] = $new['count'] + $old['count'];
        $all['diff_time'] = abs($all['last_attempt'] - $all['first_attempt']);
        $all['entries'] = array_merge($new['entries'], $old['entries']);

        return $all;
    } elseif ($new && !$old) {
        return $new;
    } elseif (!$new && $old) {
        return $old;
    }

    return false;
}

/**
 * Read and parse the content of the datastore file where the failed logins are
 * being kept. This method will also calculate the difference in time between
 * the first and last login attempt registered in the file to later decide if
 * there is a brute-force attack in progress (and send an email notification
 * with the report) or reset the file after considering it a normal behavior of
 * the site.
 *
 * @param  bool $get_old_logs Whether the old logs will be retrieved or not.
 * @param  int  $offset       Array index from where to start collecting the data.
 * @param  int  $limit        Number of items to insert into the returned array.
 * @return array|false        Information and entries gathered from the failed logins datastore file.
 */
function sucuriscan_get_failed_logins($get_old_logs = false, $offset = 0, $limit = -1)
{
    $datastore_path = sucuriscan_failed_logins_datastore_path($get_old_logs);

    if (!$datastore_path) {
        return false;
    }

    $lines = SucuriScanFileInfo::fileLines($datastore_path);

    if (!$lines) {
        return false;
    }

    $failed_logins = array(
        'count' => 0,
        'first_attempt' => 0,
        'last_attempt' => 0,
        'diff_time' => 0,
        'entries' => array(),
    );

    // Read and parse all the entries found in the datastore file.
    $initial = count($lines) - 1;
    $processed = 0;

    // Start from the newest entry in the file.
    for ($key = $initial; $key >= 0; $key--) {
        $line = trim($lines[ $key ]);

        // Skip lines that are clearly not JSON-encoded.
        if (substr($line, 0, 1) !== '{') {
            continue;
        }

        // Reduce the memory allocation by skipping unnecessary lines (LEFT).
        if ($limit > 0 && $failed_logins['count'] < $offset) {
            $failed_logins['entries'][] = $line;
            $failed_logins['count'] += 1;
            continue;
        }

        // Reduce the memory allocation by skipping unnecessary lines (RIGHT).
        if ($limit > 0 && $processed > $limit) {
            $failed_logins['entries'][] = $line;
            $failed_logins['count'] += 1;
            continue;
        }

        // Decode data only if necessary.
        $login_data = @json_decode($line, true);
        $processed++; /* count decoded data */

        if (is_array($login_data)) {
            $login_data['attempt_count'] = ( $key + 1 );

            if (!$login_data['user_agent']) {
                $login_data['user_agent'] = 'Unknown';
            }

            if (!isset($login_data['user_password'])) {
                $login_data['user_password'] = '';
            }

            $failed_logins['entries'][] = $login_data;
            $failed_logins['count'] += 1;
        }
    }

    // Stop if the there is no data.
    if ($failed_logins['count'] <= 0) {
        return false;
    }

    // Calculate the different time between the first and last attempt.
    $idx = abs($failed_logins['count'] - 1);
    $last = $failed_logins['entries'][$idx];
    $first = $failed_logins['entries'][0];

    if (!is_array($last)) {
        /* In case the JSON is not decoded yet */
        $last = @json_decode($last, true);
    }

    if (!is_array($first)) {
        /* In case the JSON is not decoded yet */
        $first = @json_decode($first, true);
    }

    $failed_logins['last_attempt'] = $last['attempt_time'];
    $failed_logins['first_attempt'] = $first['attempt_time'];
    $failed_logins['diff_time'] = abs($last['attempt_time'] - $first['attempt_time']);

    return $failed_logins;
}

/**
 * Add a new entry in the datastore file where the failed logins are being kept,
 * this entry will contain the username, timestamp of the login attempt, remote
 * address of the computer sending the request, and the user-agent.
 *
 * @param  string $user_login     Information from the current failed login event.
 * @param  string $wrong_password Wrong password used during the supposed attack.
 * @return bool                   Whether the information of the current failed login event was stored or not.
 */
function sucuriscan_log_failed_login($user_login = '', $wrong_password = '')
{
    $storage = sucuriscan_failed_logins_datastore_path();

    if (!$storage) {
        return false;
    }

    $login_data = json_encode(
        array(
            'user_login' => $user_login,
            'user_password' => $wrong_password,
            'attempt_time' => time(),
            'remote_addr' => SucuriScan::getRemoteAddr(),
            'user_agent' => SucuriScan::getUserAgent(),
        )
    );

    return (bool) @file_put_contents(
        $storage,
        $login_data . "\n",
        FILE_APPEND
    );
}

/**
 * Read and parse all the entries in the datastore file where the failed logins
 * are being kept, this will loop through all these items and generate a table
 * in HTML code to send as a report via email according to the plugin settings
 * for the email alerts.
 *
 * @param  array $failed_logins Information gathered from the failed logins.
 * @return bool                 Whether the report was sent via email or not.
 */
function sucuriscan_report_failed_logins($failed_logins = array())
{
    if (!$failed_logins
        || !isset($failed_logins['count'])
        || $failed_logins['count'] < 1
    ) {
        return false;
    }

    $mail_content = '';
    $prettify_mails = SucuriScanMail::prettifyMails();

    if ($prettify_mails) {
        $table_html  = '<table border="1" cellspacing="0" cellpadding="0">';

        // Add the table headers.
        $table_html .= '<thead>';
        $table_html .= '<tr>';
        $table_html .= '<th>' . 'Username' . '</th>';
        $table_html .= '<th>' . 'Password' . '</th>';
        $table_html .= '<th>' . 'IP Address' . '</th>';
        $table_html .= '<th>' . 'Attempt Timestamp' . '</th>';
        $table_html .= '<th>' . 'Attempt Date/Time' . '</th>';
        $table_html .= '</tr>';
        $table_html .= '</thead>';

        $table_html .= '<tbody>';
    }

    foreach ($failed_logins['entries'] as $login_data) {
        $login_data['attempt_date'] = SucuriScan::datetime($login_data['attempt_time']);

        if ($prettify_mails) {
            $table_html .= '<tr>';
            $table_html .= '<td>' . esc_attr($login_data['user_login']) . '</td>';
            $table_html .= '<td>' . esc_attr($login_data['user_password']) . '</td>';
            $table_html .= '<td>' . esc_attr($login_data['remote_addr']) . '</td>';
            $table_html .= '<td>' . esc_attr($login_data['attempt_time']) . '</td>';
            $table_html .= '<td>' . esc_attr($login_data['attempt_date']) . '</td>';
            $table_html .= '</tr>';
        } else {
            $mail_content .= "\n";
            $mail_content .= 'Username' . ":\x20" . $login_data['user_login'] . "\n";
            $mail_content .= 'Password' . ":\x20" . $login_data['user_password'] . "\n";
            $mail_content .= 'IP Address' . ":\x20" . $login_data['remote_addr'] . "\n";
            $mail_content .= 'Attempt Timestamp' . ":\x20" . $login_data['attempt_time'] . "\n";
            $mail_content .= 'Attempt Date/Time' . ":\x20" . $login_data['attempt_date'] . "\n";
        }
    }

    if ($prettify_mails) {
        $table_html .= '</tbody>';
        $table_html .= '</table>';
        $mail_content = $table_html;
    }

    if (SucuriScanEvent::notifyEvent('bruteforce_attack', $mail_content)) {
        sucuriscan_reset_failed_logins();
        return true;
    }

    return false;
}

/**
 * Remove all the entries in the datastore file where the failed logins are
 * being kept. The execution of this method will not delete the file (which is
 * likely the best move) but rather will clean its content and append the
 * default code defined by another method above.
 *
 * @return bool Whether the datastore file was resetted or not.
 */
function sucuriscan_reset_failed_logins()
{
    $datastore_path = SucuriScan::dataStorePath('sucuri-failedlogins.php');
    $datastore_backup_path = sucuriscan_failed_logins_datastore_path(true, false);
    $default_content = sucuriscan_failed_logins_default_content();
    $current_content = @file_get_contents($datastore_path);
    $current_content = str_replace($default_content, '', $current_content);

    @file_put_contents(
        $datastore_backup_path,
        $current_content,
        FILE_APPEND
    );

    return (bool) sucuriscan_failed_logins_datastore_path(false, true);
}
