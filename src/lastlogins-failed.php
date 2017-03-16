<?php

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
        'FailedLogins.CollectPasswordsVisibility' => 'visible',
        'FailedLogins.PaginationLinks' => '',
        'FailedLogins.PaginationVisibility' => 'hidden',
    );

    if (SucuriScanInterface::check_nonce()) {
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

    $max_failed_logins = SucuriScanOption::get_option(':maximum_failed_logins');
    $notify_bruteforce_attack = SucuriScanOption::get_option(':notify_bruteforce_attack');
    $collect_passwords = sucuriscan_collect_wrong_passwords();
    $failed_logins = sucuriscan_get_all_failed_logins();

    if ($failed_logins) {
        $counter = 0;

        for ($key = $page_offset; $key < $page_limit; $key++) {
            if (array_key_exists($key, $failed_logins['entries'])) {
                $login_data = $failed_logins['entries'][ $key ];
                $css_class = ( $counter % 2 == 0 ) ? '' : 'alternate';
                $wrong_user_password = 'hidden';
                $wrong_user_password_color = 'default';

                if ($collect_passwords === true) {
                    if (isset($login_data['user_password']) && !empty($login_data['user_password'])) {
                        $wrong_user_password = $login_data['user_password'];
                        $wrong_user_password_color = 'none';
                    } else {
                        $wrong_user_password = 'empty';
                        $wrong_user_password_color = 'info';
                    }
                }

                $template_variables['FailedLogins.List'] .= SucuriScanTemplate::getSnippet(
                    'lastlogins-failedlogins',
                    array(
                        'FailedLogins.CssClass' => $css_class,
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
            '%%SUCURI.URL.Lastlogins%%#failed-logins',
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

    if ($collect_passwords !== true) {
        $template_variables['FailedLogins.CollectPasswordsVisibility'] = 'hidden';
    }

    return SucuriScanTemplate::getSection('lastlogins-failedlogins', $template_variables);
}

/**
 * Whether or not to collect the password of failed logins.
 *
 * @return boolean TRUE if the password must be collected, FALSE otherwise.
 */
function sucuriscan_collect_wrong_passwords()
{
    return SucuriScanOption::is_enabled(':collect_wrong_passwords');
}

/**
 * Find the full path of the file where the information of the failed logins
 * will be stored, it will be created automatically if does not exists (and if
 * the destination folder has permissions to write). This function can also be
 * used to reset the content of the datastore file.
 *
 * @see sucuriscan_reset_failed_logins()
 *
 * @param  boolean $get_old_logs Whether the old logs will be retrieved or not.
 * @param  boolean $reset        Whether the file will be resetted or not.
 * @return string                The full (relative) path where the file is located.
 */
function sucuriscan_failed_logins_datastore_path($get_old_logs = false, $reset = false)
{
    $file_name = $get_old_logs ? 'sucuri-oldfailedlogins.php' : 'sucuri-failedlogins.php';
    $datastore_path = SucuriScan::datastore_folder_path($file_name);
    $default_content = sucuriscan_failed_logins_default_content();

    // Create the file if it does not exists.
    if (!file_exists($datastore_path) || $reset) {
        @file_put_contents($datastore_path, $default_content, LOCK_EX);
    }

    // Return the datastore path if the file exists (or was created).
    if (file_exists($datastore_path) && is_readable($datastore_path)) {
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
 * @return array Failed logins data.
 */
function sucuriscan_get_all_failed_logins()
{
    $all = array();
    $new = sucuriscan_get_failed_logins();
    $old = sucuriscan_get_failed_logins(true);

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
 * being kept. This function will also calculate the difference in time between
 * the first and last login attempt registered in the file to later decide if
 * there is a brute-force attack in progress (and send an email notification
 * with the report) or reset the file after considering it a normal behavior of
 * the site.
 *
 * @param  boolean $get_old_logs Whether the old logs will be retrieved or not.
 * @return array                 Information and entries gathered from the failed logins datastore file.
 */
function sucuriscan_get_failed_logins($get_old_logs = false)
{
    $datastore_path = sucuriscan_failed_logins_datastore_path($get_old_logs);

    if ($datastore_path) {
        $lines = SucuriScanFileInfo::file_lines($datastore_path);

        if ($lines) {
            $failed_logins = array(
                'count' => 0,
                'first_attempt' => 0,
                'last_attempt' => 0,
                'diff_time' => 0,
                'entries' => array(),
            );

            // Read and parse all the entries found in the datastore file.
            $offset = count($lines) - 1;

            for ($key = $offset; $key >= 0; $key--) {
                $line = trim($lines[ $key ]);
                $login_data = @json_decode($line, true);

                if (is_array($login_data)) {
                    $login_data['attempt_date'] = date('r', $login_data['attempt_time']);
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

            // Calculate the different time between the first and last attempt.
            if ($failed_logins['count'] > 0) {
                $z = abs($failed_logins['count'] - 1);
                $failed_logins['last_attempt'] = $failed_logins['entries'][ $z ]['attempt_time'];
                $failed_logins['first_attempt'] = $failed_logins['entries'][0]['attempt_time'];
                $failed_logins['diff_time'] = abs($failed_logins['last_attempt'] - $failed_logins['first_attempt']);

                return $failed_logins;
            }
        }
    }

    return false;
}

/**
 * Add a new entry in the datastore file where the failed logins are being kept,
 * this entry will contain the username, timestamp of the login attempt, remote
 * address of the computer sending the request, and the user-agent.
 *
 * @param  string  $user_login     Information from the current failed login event.
 * @param  string  $wrong_password Wrong password used during the supposed attack.
 * @return boolean                 Whether the information of the current failed login event was stored or not.
 */
function sucuriscan_log_failed_login($user_login = '', $wrong_password = '')
{
    $datastore_path = sucuriscan_failed_logins_datastore_path();

    // Do not collect wrong passwords if it is not necessary.
    if (sucuriscan_collect_wrong_passwords() !== true) {
        $wrong_password = '';
    }

    if ($datastore_path) {
        $login_data = json_encode(array(
            'user_login' => $user_login,
            'user_password' => $wrong_password,
            'attempt_time' => time(),
            'remote_addr' => SucuriScan::get_remote_addr(),
            'user_agent' => SucuriScan::get_user_agent(),
        ));

        $written = @file_put_contents(
            $datastore_path,
            $login_data . "\n",
            FILE_APPEND
        );

        if ($written > 0) {
            return true;
        }
    }

    return false;
}

/**
 * Read and parse all the entries in the datastore file where the failed logins
 * are being kept, this will loop through all these items and generate a table
 * in HTML code to send as a report via email according to the plugin settings
 * for the email notifications.
 *
 * @param  array   $failed_logins Information and entries gathered from the failed logins datastore file.
 * @return boolean                Whether the report was sent via email or not.
 */
function sucuriscan_report_failed_logins($failed_logins = array())
{
    if ($failed_logins && $failed_logins['count'] > 0) {
        $prettify_mails = SucuriScanMail::prettify_mails();
        $collect_wrong_passwords = sucuriscan_collect_wrong_passwords();
        $mail_content = '';

        if ($prettify_mails) {
            $table_html  = '<table border="1" cellspacing="0" cellpadding="0">';

            // Add the table headers.
            $table_html .= '<thead>';
            $table_html .= '<tr>';
            $table_html .= '<th>Username</th>';

            if ($collect_wrong_passwords === true) {
                $table_html .= '<th>Password</th>';
            }

            $table_html .= '<th>IP Address</th>';
            $table_html .= '<th>Attempt Timestamp</th>';
            $table_html .= '<th>Attempt Date/Time</th>';
            $table_html .= '</tr>';
            $table_html .= '</thead>';

            $table_html .= '<tbody>';
        }

        foreach ($failed_logins['entries'] as $login_data) {
            if ($prettify_mails) {
                $table_html .= '<tr>';
                $table_html .= '<td>' . esc_attr($login_data['user_login']) . '</td>';

                if ($collect_wrong_passwords === true) {
                    $table_html .= '<td>' . esc_attr($login_data['user_password']) . '</td>';
                }

                $table_html .= '<td>' . esc_attr($login_data['remote_addr']) . '</td>';
                $table_html .= '<td>' . $login_data['attempt_time'] . '</td>';
                $table_html .= '<td>' . $login_data['attempt_date'] . '</td>';
                $table_html .= '</tr>';
            } else {
                $mail_content .= "\n";
                $mail_content .= 'Username: ' . $login_data['user_login'] . "\n";

                if ($collect_wrong_passwords === true) {
                    $mail_content .= 'Password: ' . $login_data['user_password'] . "\n";
                }

                $mail_content .= 'IP Address: ' . $login_data['remote_addr'] . "\n";
                $mail_content .= 'Attempt Timestamp: ' . $login_data['attempt_time'] . "\n";
                $mail_content .= 'Attempt Date/Time: ' . $login_data['attempt_date'] . "\n";
            }
        }

        if ($prettify_mails) {
            $table_html .= '</tbody>';
            $table_html .= '</table>';
            $mail_content = $table_html;
        }

        if (SucuriScanEvent::notify_event('bruteforce_attack', $mail_content)) {
            sucuriscan_reset_failed_logins();

            return true;
        }
    }

    return false;
}

/**
 * Remove all the entries in the datastore file where the failed logins are
 * being kept. The execution of this function will not delete the file (which is
 * likely the best move) but rather will clean its content and append the
 * default code defined by another function above.
 *
 * @return boolean Whether the datastore file was resetted or not.
 */
function sucuriscan_reset_failed_logins()
{
    $datastore_path = SucuriScan::datastore_folder_path('sucuri-failedlogins.php');
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
