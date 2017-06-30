<?php

/**
 * Code related to the lastlogins.php interface.
 *
 * @package Sucuri Security
 * @subpackage lastlogins.php
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
 * Placeholder for the last logins interface.
 */
class SucuriScanLastLogins extends SucuriScan
{
}

/**
 * List all the user administrator accounts.
 *
 * @see https://codex.wordpress.org/Class_Reference/WP_User_Query
 */
function sucuriscan_lastlogins_admins()
{
    // Page pseudo-variables initialization.
    $params = array(
        'AdminUsers.List' => '',
    );

    $user_query = new WP_User_Query(array('role' => 'Administrator'));
    $admins = $user_query->get_results();

    foreach ((array) $admins as $admin) {
        $last_logins = sucuriscan_get_logins(5, 0, $admin->ID);
        $user_snippet = array(
            'AdminUsers.Username' => $admin->user_login,
            'AdminUsers.Email' => $admin->user_email,
            'AdminUsers.LastLogins' => '',
            'AdminUsers.RegisteredAt' => __('Unknown', SUCURISCAN_TEXTDOMAIN),
            'AdminUsers.UserURL' => SucuriScan::adminURL('user-edit.php?user_id=' . $admin->ID),
            'AdminUsers.NoLastLogins' => 'visible',
            'AdminUsers.NoLastLoginsTable' => 'hidden',
        );

        if ($last_logins
            && isset($last_logins['entries'])
            && !empty($last_logins['entries'])
        ) {
            $user_snippet['AdminUsers.NoLastLogins'] = 'hidden';
            $user_snippet['AdminUsers.NoLastLoginsTable'] = 'visible';
            $user_snippet['AdminUsers.RegisteredAt'] = __('Unknown', SUCURISCAN_TEXTDOMAIN);

            foreach ($last_logins['entries'] as $i => $lastlogin) {
                if ($i === 0) {
                    $user_snippet['AdminUsers.RegisteredAt'] = SucuriScan::datetime(
                        $lastlogin->user_registered_timestamp
                    );
                }

                $user_snippet['AdminUsers.LastLogins'] .=
                SucuriScanTemplate::getSnippet('lastlogins-admins-lastlogin', array(
                    'AdminUsers.RemoteAddr' => $lastlogin->user_remoteaddr,
                    'AdminUsers.Datetime' => SucuriScan::datetime($lastlogin->user_lastlogin_timestamp),
                ));
            }
        }

        $params['AdminUsers.List'] .= SucuriScanTemplate::getSnippet('lastlogins-admins', $user_snippet);
    }

    return SucuriScanTemplate::getSection('lastlogins-admins', $params);
}

/**
 * List the last-logins for all user accounts in the site.
 *
 * This page will contains information of all the logins of the registered users.
 *
 * @return string Last-logings for all user accounts.
 */
function sucuriscan_lastlogins_all()
{
    $max_per_page = SUCURISCAN_LASTLOGINS_USERSLIMIT;
    $page_number = SucuriScanTemplate::pageNumber();
    $offset = ($max_per_page * $page_number) - $max_per_page;

    $params = array(
        'UserList' => '',
        'UserList.Limit' => $max_per_page,
        'UserList.Total' => 0,
        'UserList.Pagination' => '',
        'UserList.PaginationVisibility' => 'hidden',
        'UserList.NoItemsVisibility' => 'visible',
    );

    if (!sucuriscan_lastlogins_datastore_is_writable()) {
        $fpath = SucuriScan::escape(sucuriscan_lastlogins_datastore_filepath());
        SucuriScanInterface::error(sprintf(__('LastLoginsNotWritable'), $fpath));
    }

    if ($last_logins = sucuriscan_get_logins($max_per_page, $offset)) {
        $params['UserList.Total'] = $last_logins['total'];

        if ($last_logins['total'] > $max_per_page) {
            $params['UserList.PaginationVisibility'] = 'visible';
        }

        if ($last_logins['total'] > 0) {
            $params['UserList.NoItemsVisibility'] = 'hidden';
        }

        foreach ($last_logins['entries'] as $user) {
            $user_dataset = array(
                'UserList.Number' => $user->line_num,
                'UserList.UserId' => $user->user_id,
                'UserList.Username' => __('Unknown', SUCURISCAN_TEXTDOMAIN),
                'UserList.Displayname' => '',
                'UserList.Email' => '',
                'UserList.Registered' => '',
                'UserList.RemoteAddr' => $user->user_remoteaddr,
                'UserList.Hostname' => $user->user_hostname,
                'UserList.Datetime' => $user->user_lastlogin,
                'UserList.TimeAgo' => SucuriScan::timeAgo($user->user_lastlogin),
                'UserList.UserURL' => SucuriScan::adminURL('user-edit.php?user_id=' . $user->user_id),
            );

            if ($user->user_exists) {
                $user_dataset['UserList.Username'] = $user->user_login;
                $user_dataset['UserList.Displayname'] = $user->display_name;
                $user_dataset['UserList.Email'] = $user->user_email;
                $user_dataset['UserList.Registered'] = $user->user_registered;
            }

            $params['UserList'] .= SucuriScanTemplate::getSnippet('lastlogins-all', $user_dataset);
        }

        // Generate the pagination for the list.
        $params['UserList.Pagination'] = SucuriScanTemplate::pagination(
            '%%SUCURI.URL.Lastlogins%%',
            $last_logins['total'],
            $max_per_page
        );
    }

    return SucuriScanTemplate::getSection('lastlogins-all', $params);
}

/**
 * Get the filepath where the information of the last logins of all users is stored.
 *
 * @return string Absolute filepath where the user's last login information is stored.
 */
function sucuriscan_lastlogins_datastore_filepath()
{
    return SucuriScan::dataStorePath('sucuri-lastlogins.php');
}

/**
 * Check whether the user's last login datastore file exists or not, if not then
 * we try to create the file and check again the success of the operation.
 *
 * @return string|bool Path to the storage file if exists, false otherwise.
 */
function sucuriscan_lastlogins_datastore_exists()
{
    $fpath = sucuriscan_lastlogins_datastore_filepath();

    if (!file_exists($fpath)) {
        @file_put_contents($fpath, "<?php exit(0); ?>\n", LOCK_EX);
    }

    if (file_exists($fpath)) {
        return $fpath;
    }

    return false;
}

/**
 * Check whether the user's last login datastore file is writable or not, if not
 * we try to set the right permissions and check again the success of the operation.
 *
 * @return string|bool Path to the storage file if writable, false otherwise.
 */
function sucuriscan_lastlogins_datastore_is_writable()
{
    $datastore_filepath = sucuriscan_lastlogins_datastore_exists();

    if ($datastore_filepath) {
        if (!is_writable($datastore_filepath)) {
            @chmod($datastore_filepath, 0644);
        }

        if (is_writable($datastore_filepath)) {
            return $datastore_filepath;
        }
    }

    return false;
}

/**
 * Check whether the user's last login datastore file is readable or not, if not
 * we try to set the right permissions and check again the success of the operation.
 *
 * @return string|bool Path to the storage file if readable, false otherwise.
 */
function sucuriscan_lastlogins_datastore_is_readable()
{
    $datastore_filepath = sucuriscan_lastlogins_datastore_exists();

    if ($datastore_filepath && is_readable($datastore_filepath)) {
        return $datastore_filepath;
    }

    return false;
}

if (!function_exists('sucuri_set_lastlogin')) {
    /**
     * Add a new user session to the list of last user logins.
     *
     * @param string $user_login The name of the user account involved in the operation.
     */
    function sucuriscan_set_lastlogin($user_login = '')
    {
        if ($filename = sucuriscan_lastlogins_datastore_is_writable()) {
            $current_user = get_user_by('login', $user_login);
            $remote_addr = SucuriScan::getRemoteAddr();

            $login_info = array(
                'user_id' => $current_user->ID,
                'user_login' => $current_user->user_login,
                'user_remoteaddr' => $remote_addr,
                'user_hostname' => @gethostbyaddr($remote_addr),
                'user_lastlogin' => current_time('mysql')
            );

            @file_put_contents(
                $filename,
                json_encode($login_info) . "\n",
                FILE_APPEND
            );
        }
    }
    add_action('wp_login', 'sucuriscan_set_lastlogin', 50);
}

/**
 * Retrieve the list of all the user logins from the datastore file.
 *
 * The results of this operation can be filtered by specific user identifiers,
 * or limiting the quantity of entries.
 *
 * @param int $limit How many entries will be returned from the operation.
 * @param int $offset Initial point where the logs will be start counting.
 * @param int $user_id Optional user identifier to filter the results.
 * @return array|bool All user logins, false on failure.
 */
function sucuriscan_get_logins($limit = 10, $offset = 0, $user_id = 0)
{
    $limit = intval($limit); /* prevent arbitrary user input */
    $datastore_filepath = sucuriscan_lastlogins_datastore_is_readable();
    $last_logins = array(
        'total' => 0,
        'entries' => array(),
    );

    if (!$datastore_filepath) {
        return SucuriScan::throwException('Invalid last-logins storage file');
    }

    $parsed_lines = 0;
    $data_lines = SucuriScanFileInfo::fileLines($datastore_filepath);

    if (!$data_lines) {
        return SucuriScan::throwException('No last-logins data is available');
    }

    /**
     * This count will not be 100% accurate considering that we are checking the
     * syntax of each line in the loop bellow, there may be some lines without the
     * right syntax which will differ from the total entries returned, but there's
     * not other EASY way to do this without affect the performance of the code.
     *
     * @var integer
     */
    $total_lines = count($data_lines);
    $last_logins['total'] = $total_lines;

    // Get a list with the latest entries in the first positions.
    $reversed_lines = array_reverse($data_lines);

    /**
     * Only the user accounts with administrative privileges can see the logs of all
     * the users, for the rest of the accounts they will only see their own logins.
     *
     * @var object
     */
    $current_user = wp_get_current_user();
    $is_admin_user = (bool) current_user_can('manage_options');

    for ($i = $offset; $i < $total_lines; $i++) {
        $line = $reversed_lines[$i] ? trim($reversed_lines[$i]) : '';

        // Check if the data is serialized (which we will consider as insecure).
        $last_login = @json_decode($line, true);

        if (!$last_login) {
            $last_logins['total'] -= 1;
            continue;
        }

        $last_login['user_lastlogin_timestamp'] = strtotime($last_login['user_lastlogin']);
        $last_login['user_registered_timestamp'] = 0;

        // Only administrators can see all login stats.
        if (!$is_admin_user && $current_user->user_login != $last_login['user_login']) {
            continue;
        }

        // Filter the user identifiers using the value passed tot his function.
        if ($user_id > 0 && $last_login['user_id'] != $user_id) {
            continue;
        }

        // Get the WP_User object and add extra information from the last-login data.
        $last_login['user_exists'] = false;
        $user_account = get_userdata($last_login['user_id']);

        if ($user_account) {
            $last_login['user_exists'] = true;

            foreach ($user_account->data as $var_name => $var_value) {
                $last_login[ $var_name ] = $var_value;

                if ($var_name == 'user_registered') {
                    $last_login['user_registered_timestamp'] = strtotime($var_value);
                }
            }
        }

        $last_login['line_num'] = $i + 1;
        $last_logins['entries'][] = (object) $last_login;
        $parsed_lines += 1;

        if ($limit > 0 && $parsed_lines >= $limit) {
            break;
        }
    }

    return $last_logins;
}

if (!function_exists('sucuri_login_redirect')) {
    /**
     * Hook for the wp-login action to redirect the user to a specific URL after
     * his successfully login to the administrator interface.
     *
     * @param string $redirect_to The redirect destination URL.
     * @param object $request The requested redirect destination URL passed as a parameter.
     * @param bool $user WP_User object if login was successful, WP_Error object otherwise.
     * @return string URL where the browser must be redirected to.
     */
    function sucuriscan_login_redirect($redirect_to = '', $request = null, $user = false)
    {
        $login_url = !empty($redirect_to) ? $redirect_to : SucuriScan::adminURL();

        if ($user instanceof WP_User
            && in_array('administrator', $user->roles)
            && SucuriScanOption::isEnabled(':lastlogin_redirection')
        ) {
            $login_url = add_query_arg('sucuriscan_lastlogin', 1, $login_url);
        }

        return $login_url;
    }

    if (SucuriScanOption::isEnabled(':lastlogin_redirection')) {
        add_filter('login_redirect', 'sucuriscan_login_redirect', 10, 3);
    }
}

if (!function_exists('sucuri_get_user_lastlogin')) {
    /**
     * Display the last user login at the top of the admin interface.
     */
    function sucuriscan_get_user_lastlogin()
    {
        if (current_user_can('manage_options')
            && SucuriScanRequest::get(':lastlogin', '1')
        ) {
            $current_user = wp_get_current_user();

            // Select the penultimate entry, not the last one.
            $last_logins = sucuriscan_get_logins(2, 0, $current_user->ID);

            if ($last_logins
                && isset($last_logins['entries'])
                && isset($last_logins['entries'][1])
            ) {
                $row = $last_logins['entries'][1];
                $page_url = SucuriScanTemplate::getUrl('lastlogins');
                $message = sprintf(
                    __('LastLoginMessage', SUCURISCAN_TEXTDOMAIN),
                    SucuriScan::datetime($row->user_lastlogin_timestamp),
                    SucuriScan::escape($row->user_remoteaddr),
                    SucuriScan::escape($row->user_hostname),
                    SucuriScan::escape($page_url)
                );
                SucuriScanInterface::info($message);
            }
        }
    }

    add_action('admin_notices', 'sucuriscan_get_user_lastlogin');
}
