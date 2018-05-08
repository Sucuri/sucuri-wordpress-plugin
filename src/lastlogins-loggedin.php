<?php

/**
 * Code related to the lastlogins-loggedin.php interface.
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
 * Print a list of all the registered users that are currently in session.
 *
 * @return string The HTML code displaying a list of all the users logged in at the moment.
 */
function sucuriscan_loggedin_users_panel()
{
    // Get user logged in list.
    $params = array(
        'LoggedInUsers.List' => '',
        'LoggedInUsers.Total' => 0,
    );

    $logged_in_users = sucuriscan_get_online_users(true);

    if (is_array($logged_in_users) && !empty($logged_in_users)) {
        $params['LoggedInUsers.Total'] = count($logged_in_users);

        foreach ((array) $logged_in_users as $logged_in_user) {
            $logged_in_user['last_activity_datetime'] = SucuriScan::datetime($logged_in_user['last_activity']);
            $logged_in_user['user_registered_datetime'] = SucuriScan::datetime(strtotime($logged_in_user['user_registered']));

            $params['LoggedInUsers.List'] .= SucuriScanTemplate::getSnippet(
                'lastlogins-loggedin',
                array(
                    'LoggedInUsers.Id' => $logged_in_user['user_id'],
                    'LoggedInUsers.UserURL' => SucuriScan::adminURL('user-edit.php?user_id=' . $logged_in_user['user_id']),
                    'LoggedInUsers.UserLogin' => $logged_in_user['user_login'],
                    'LoggedInUsers.UserEmail' => $logged_in_user['user_email'],
                    'LoggedInUsers.LastActivity' => $logged_in_user['last_activity_datetime'],
                    'LoggedInUsers.Registered' => $logged_in_user['user_registered_datetime'],
                    'LoggedInUsers.RemoteAddr' => $logged_in_user['remote_addr'],
                )
            );
        }
    }

    return SucuriScanTemplate::getSection('lastlogins-loggedin', $params);
}

/**
 * Get a list of all the registered users that are currently in session.
 *
 * @param bool $add_current_user Whether the current user should be added to the list or not.
 * @return array List of registered users currently in session.
 */
function sucuriscan_get_online_users($add_current_user = false)
{
    $users = array();

    if (SucuriScan::isMultiSite()) {
        $users = get_site_transient('online_users');
    } else {
        $users = get_transient('online_users');
    }

    // If not online users but current user is logged in, add it to the list.
    if (empty($users) && $add_current_user) {
        $current_user = wp_get_current_user();

        if ($current_user->ID > 0) {
            sucuriscan_set_online_user($current_user->user_login, $current_user);

            return sucuriscan_get_online_users();
        }
    }

    return $users;
}

/**
 * Update the list of the registered users currently in session.
 *
 * Useful when you are removing users and need the list of the remaining users.
 *
 * @param array $logged_in_users List of registered users currently in session.
 * @return bool Either TRUE or FALSE representing the success or fail of the operation.
 */
function sucuriscan_save_online_users($logged_in_users = array())
{
    $expiration = 30 * 60;

    if (SucuriScan::isMultiSite()) {
        return set_site_transient('online_users', $logged_in_users, $expiration);
    } else {
        return set_transient('online_users', $logged_in_users, $expiration);
    }
}

if (!function_exists('sucuriscan_unset_online_user_on_logout')) {
    /**
     * Remove a logged in user from the list.
     *
     * @return void
     */
    function sucuriscan_unset_online_user_on_logout()
    {
        $remote_addr = SucuriScan::getRemoteAddr();
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        sucuriscan_unset_online_user($user_id, $remote_addr);
    }

    add_action('wp_logout', 'sucuriscan_unset_online_user_on_logout');
}

/**
 * Remove a logged in user from the list of registered users in session using
 * the user identifier and the ip address of the last computer used to login.
 *
 * @param  int    $user_id     User ID of the account that will be logged out.
 * @param  string $remote_addr IP address of the computer where the user logged in.
 * @return bool                True on success, false otherwise.
 */
function sucuriscan_unset_online_user($user_id = 0, $remote_addr = '')
{
    $logged_in_users = sucuriscan_get_online_users();

    // Remove the specified user identifier from the list.
    if (is_array($logged_in_users) && !empty($logged_in_users)) {
        foreach ($logged_in_users as $i => $user) {
            if ($user['user_id'] == $user_id
                && strcmp($user['remote_addr'], $remote_addr) == 0
            ) {
                unset($logged_in_users[ $i ]);
                break;
            }
        }
    }

    return sucuriscan_save_online_users($logged_in_users);
}

if (!function_exists('sucuriscan_set_online_user')) {
    /**
     * Add an user account to the list of registered users in session.
     *
     * @param  string $user_login The name of the user account that just logged in the site.
     * @param  bool   $user       The WordPress object containing all the information associated to the user.
     * @return void
     */
    function sucuriscan_set_online_user($user_login = '', $user = false)
    {
        if (!$user) {
            return;
        }

        /* get logged in user information */
        $current_user = ($user instanceof WP_User) ? $user : wp_get_current_user();
        $current_user_id = $current_user->ID;
        $remote_addr = SucuriScan::getRemoteAddr();
        $current_time = current_time('timestamp');
        $logged_in_users = sucuriscan_get_online_users();

        /* build the dataset for the transient variable */
        $current_user_info = array(
            'user_id' => $current_user_id,
            'user_login' => $current_user->user_login,
            'user_email' => $current_user->user_email,
            'user_registered' => $current_user->user_registered,
            'last_activity' => $current_time,
            'remote_addr' => $remote_addr,
        );

        /* no previous data, no need to merge, override */
        if (!is_array($logged_in_users) || empty($logged_in_users)) {
            $logged_in_users = array( $current_user_info );
            sucuriscan_save_online_users($logged_in_users);
            return;
        }

        $item_index = 0;
        $do_nothing = false;
        $update_existing = false;

        /* update user metadata if the session already exists */
        foreach ($logged_in_users as $i => $user) {
            if ($user['user_id'] == $current_user_id
                && strcmp($user['remote_addr'], $remote_addr) == 0
            ) {
                if ($user['last_activity'] < ($current_time - (15 * 60))) {
                    $update_existing = true;
                    $item_index = $i;
                    break;
                } else {
                    $do_nothing = true;
                    break;
                }
            }
        }

        if ($do_nothing) {
            return;
        }

        if ($update_existing) {
            $logged_in_users[ $item_index ] = $current_user_info;
            sucuriscan_save_online_users($logged_in_users);
            return;
        }

        $logged_in_users[] = $current_user_info;
        sucuriscan_save_online_users($logged_in_users);
    }

    add_action('wp_login', 'sucuriscan_set_online_user', 50, 2);
}
