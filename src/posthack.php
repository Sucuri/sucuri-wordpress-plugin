<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Generate and print the HTML code for the Post-Hack page.
 *
 * @return void
 */
function sucuriscan_posthack_page()
{
    SucuriScanInterface::check_permissions();

    $process_form = sucuriscan_posthack_process_form();

    // Page pseudo-variables initialization.
    $params['PageTitle'] = 'Post-Hack';
    $params['UpdateSecretKeys'] = sucuriscan_update_secret_keys($process_form);
    $params['ResetPassword'] = sucuriscan_posthack_users($process_form);
    $params['ResetPlugins'] = sucuriscan_posthack_plugins($process_form);
    $params['AvailableUpdates'] = sucuriscan_posthack_updates();

    echo SucuriScanTemplate::getTemplate('posthack', $params);
}

/**
 * Handle an Ajax request for this specific page.
 *
 * @return mixed.
 */
function sucuriscan_posthack_ajax()
{
    SucuriScanInterface::check_permissions();

    if (SucuriScanInterface::check_nonce()) {
        sucuriscan_posthack_plugins_ajax();
        sucuriscan_posthack_updates_ajax();
    }

    wp_die();
}

/**
 * Check whether the "I understand this operation" checkbox was marked or not.
 *
 * @return boolean TRUE if a form submission should be processed, FALSE otherwise.
 */
function sucuriscan_posthack_process_form()
{
    $process_form = SucuriScanRequest::post(':process_form', '(0|1)');

    if (SucuriScanInterface::check_nonce()
        && $process_form !== false
    ) {
        if ($process_form === '1') {
            return true;
        } else {
            SucuriScanInterface::error('You need to confirm that you understand the risk of this operation.');
        }
    }

    return false;
}

/**
 * Update the WordPress secret keys.
 *
 * @param  $process_form Whether a form was submitted or not.
 * @return string        HTML code with the information of the process.
 */
function sucuriscan_update_secret_keys($process_form = false)
{
    $params = array(
        'WPConfigUpdate.Visibility' => 'hidden',
        'WPConfigUpdate.NewConfig' => '',
        'SecurityKeys.List' => '',
    );

    // Update all WordPress secret keys.
    if ($process_form && SucuriScanRequest::post(':update_wpconfig', '1')) {
        $wpconfig_process = SucuriScanEvent::set_new_config_keys();

        if ($wpconfig_process) {
            $params['WPConfigUpdate.Visibility'] = 'visible';
            SucuriScanEvent::report_notice_event('Generate new security keys');

            if ($wpconfig_process['updated'] === true) {
                SucuriScanInterface::info('Secret keys updated successfully (summary of the operation bellow).');
                $params['WPConfigUpdate.NewConfig'] .= "// Old Keys\n";
                $params['WPConfigUpdate.NewConfig'] .= $wpconfig_process['old_keys_string'];
                $params['WPConfigUpdate.NewConfig'] .= "//\n";
                $params['WPConfigUpdate.NewConfig'] .= "// New Keys\n";
                $params['WPConfigUpdate.NewConfig'] .= $wpconfig_process['new_keys_string'];
            } else {
                SucuriScanInterface::error(
                    '<code>wp-config.php</code> file is not writable, replace the '
                    . 'old configuration file with the new values shown bellow.'
                );
                $params['WPConfigUpdate.NewConfig'] = $wpconfig_process['new_wpconfig'];
            }
        } else {
            SucuriScanInterface::error('<code>wp-config.php</code> file was not found in the default location.');
        }
    }

    // Display the current status of the security keys.
    $current_keys = SucuriScanOption::get_security_keys();
    $counter = 0;

    foreach ($current_keys as $key_status => $key_list) {
        foreach ($key_list as $key_name => $key_value) {
            $css_class = ( $counter % 2 == 0 ) ? '' : 'alternate';
            $key_value = SucuriScan::excerpt($key_value, 50);

            switch ($key_status) {
                case 'good':
                    $key_status_text = 'good';
                    $key_status_css_class = 'success';
                    break;
                case 'bad':
                    $key_status_text = 'not randomized';
                    $key_status_css_class = 'warning';
                    break;
                case 'missing':
                    $key_value = '';
                    $key_status_text = 'not set';
                    $key_status_css_class = 'danger';
                    break;
            }

            if (isset($key_status_text)) {
                $params['SecurityKeys.List'] .= SucuriScanTemplate::getSnippet(
                    'posthack-updatesecretkeys',
                    array(
                        'SecurityKey.CssClass' => $css_class,
                        'SecurityKey.KeyName' => $key_name,
                        'SecurityKey.KeyValue' => $key_value,
                        'SecurityKey.KeyStatusText' => $key_status_text,
                        'SecurityKey.KeyStatusCssClass' => $key_status_css_class,
                    )
                );
                $counter++;
            }
        }
    }

    return SucuriScanTemplate::getSection('posthack-updatesecretkeys', $params);
}

/**
 * Display a list of users in a table that will be used to select the accounts
 * where a password reset action will be executed.
 *
 * @param  $process_form Whether a form was submitted or not.
 * @return string        HTML code for a table where a list of user accounts will be shown.
 */
function sucuriscan_posthack_users($process_form = false)
{
    $params = array(
        'ResetPassword.UserList' => '',
        'ResetPassword.PaginationLinks' => '',
        'ResetPassword.PaginationVisibility' => 'hidden',
    );

    // Process the form submission (if any).
    sucuriscan_reset_user_password($process_form);

    // Fill the user list for ResetPassword action.
    $user_list = false;
    $page_number = SucuriScanTemplate::pageNumber();
    $max_per_page = SUCURISCAN_MAX_PAGINATION_BUTTONS;
    $dbquery = new WP_User_Query(array(
        'number' => $max_per_page,
        'offset' => ($page_number - 1) * $max_per_page,
        'fields' => 'all_with_meta',
        'orderby' => 'ID',
    ));

    // Retrieve the results and build the pagination links.
    if ($dbquery) {
        $total_items = $dbquery->get_total();
        $user_list = $dbquery->get_results();

        $params['ResetPassword.PaginationLinks'] = SucuriScanTemplate::pagination(
            '%%SUCURI.URL.Posthack%%#reset-users-password',
            $total_items,
            $max_per_page
        );

        if ($total_items > $max_per_page) {
            $params['ResetPassword.PaginationVisibility'] = 'visible';
        }
    }

    if ($user_list !== false) {
        $counter = 0;

        foreach ($user_list as $user) {
            $user->user_registered_timestamp = strtotime($user->user_registered);
            $user->user_registered_formatted = SucuriScan::datetime($user->user_registered_timestamp);
            $css_class = ( $counter % 2 == 0 ) ? '' : 'alternate';
            $display_username = ( $user->user_login != $user->display_name )
                ? sprintf('%s (%s)', $user->user_login, $user->display_name)
                : $user->user_login;

            $params['ResetPassword.UserList'] .= SucuriScanTemplate::getSnippet(
                'posthack-resetpassword',
                array(
                    'ResetPassword.UserId' => $user->ID,
                    'ResetPassword.Username' => $user->user_login,
                    'ResetPassword.Displayname' => $user->display_name,
                    'ResetPassword.DisplayUsername' => $display_username,
                    'ResetPassword.Email' => $user->user_email,
                    'ResetPassword.Registered' => $user->user_registered_formatted,
                    'ResetPassword.Roles' => @implode(', ', $user->roles),
                    'ResetPassword.CssClass' => $css_class,
                )
            );
            $counter++;
        }
    }

    return SucuriScanTemplate::getSection('posthack-resetpassword', $params);
}

/**
 * Update the password of the user accounts specified.
 *
 * @param  $process_form Whether a form was submitted or not.
 * @return void
 */
function sucuriscan_reset_user_password($process_form = false)
{
    if ($process_form && SucuriScanRequest::post(':reset_password')) {
        $user_identifiers = SucuriScanRequest::post('user_ids', '_array');
        $pwd_changed = array();
        $pwd_not_changed = array();

        if (is_array($user_identifiers) && !empty($user_identifiers)) {
            arsort($user_identifiers);

            foreach ($user_identifiers as $user_id) {
                $user_id = intval($user_id);

                if (SucuriScanEvent::set_new_password($user_id)) {
                    $pwd_changed[] = $user_id;
                } else {
                    $pwd_not_changed[] = $user_id;
                }
            }

            if (!empty($pwd_changed)) {
                $message = 'Password changed for user identifiers <code>' . @implode(', ', $pwd_changed) . '</code>';

                SucuriScanEvent::report_notice_event($message);
                SucuriScanInterface::info($message);
            }

            if (!empty($pwd_not_changed)) {
                SucuriScanInterface::error('Password change failed for users: ' . implode(', ', $pwd_not_changed));
            }
        } else {
            SucuriScanInterface::error('You did not select a user from the list.');
        }
    }
}

/**
 * Reset all the FREE plugins, even if they are not activated.
 *
 * @param  boolean $process_form Whether a form was submitted or not.
 * @return void
 */
function sucuriscan_posthack_plugins($process_form = false)
{
    $params = array(
        'ResetPlugin.PluginList' => '',
        'ResetPlugin.CacheLifeTime' => 'unknown',
    );

    if (defined('SUCURISCAN_GET_PLUGINS_LIFETIME')) {
        $params['ResetPlugin.CacheLifeTime'] = SUCURISCAN_GET_PLUGINS_LIFETIME;
    }

    sucuriscan_posthack_reinstall_plugins($process_form);

    return SucuriScanTemplate::getSection('posthack-resetplugins', $params);
}

/**
 * Process the Ajax request to retrieve the plugins metadata.
 *
 * @return string HTML code for a table with the plugins metadata.
 */
function sucuriscan_posthack_plugins_ajax()
{
    if (SucuriScanRequest::post('form_action') == 'get_plugins_data') {
        $all_plugins = SucuriScanAPI::getPlugins();
        $response = '';
        $counter = 0;

        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $css_class = ( $counter % 2 == 0 ) ? '' : 'alternate';
            $plugin_type_class = ( $plugin_data['PluginType'] == 'free' ) ? 'primary' : 'warning';
            $input_disabled = ( $plugin_data['PluginType'] == 'free' ) ? '' : 'disabled="disabled"';
            $plugin_status = $plugin_data['IsPluginActive'] ? 'active' : 'not active';
            $plugin_status_class = $plugin_data['IsPluginActive'] ? 'success' : 'default';

            $response .= SucuriScanTemplate::getSnippet(
                'posthack-resetplugins',
                array(
                    'ResetPlugin.CssClass' => $css_class,
                    'ResetPlugin.Disabled' => $input_disabled,
                    'ResetPlugin.PluginPath' => $plugin_path,
                    'ResetPlugin.Repository' => $plugin_data['Repository'],
                    'ResetPlugin.Plugin' => SucuriScan::excerpt($plugin_data['Name'], 35),
                    'ResetPlugin.Version' => $plugin_data['Version'],
                    'ResetPlugin.Type' => $plugin_data['PluginType'],
                    'ResetPlugin.TypeClass' => $plugin_type_class,
                    'ResetPlugin.Status' => $plugin_status,
                    'ResetPlugin.StatusClass' => $plugin_status_class,
                )
            );
            $counter++;
        }

        print( $response );
        exit(0);
    }
}

/**
 * Find and list available updates for plugins and themes.
 *
 * @return void
 */
function sucuriscan_posthack_updates()
{
    $params = array();

    return SucuriScanTemplate::getSection('posthack-updates', $params);
}

/**
 * Retrieve the information for the available updates.
 *
 * @return string HTML code for a table with the updates information.
 */
function sucuriscan_posthack_updates_content($send_email = false)
{
    if (!function_exists('wp_update_plugins')
        || !function_exists('get_plugin_updates')
        || !function_exists('wp_update_themes')
        || !function_exists('get_theme_updates')
    ) {
        return false;
    }

    $response = '';
    $result = wp_update_plugins();
    $updates = get_plugin_updates();

    if (is_array($updates) && !empty($updates)) {
        $counter = 0;

        foreach ($updates as $data) {
            $css_class = ($counter % 2 == 0) ? '' : 'alternate';
            $params = array(
                'Update.CssClass' => $css_class,
                'Update.IconType' => 'plugins',
                'Update.Extension' => SucuriScan::excerpt($data->Name, 35),
                'Update.Version' => $data->Version,
                'Update.NewVersion' => 'Unknown',
                'Update.TestedWith' => 'Unknown',
                'Update.ArchiveUrl' => 'Unknown',
                'Update.MarketUrl' => 'Unknown',
            );

            if (property_exists($data->update, 'new_version')) {
                $params['Update.NewVersion'] = $data->update->new_version;
            }

            if (property_exists($data->update, 'tested')) {
                $params['Update.TestedWith'] = "WordPress\x20" . $data->update->tested;
            }

            if (property_exists($data->update, 'package')) {
                $params['Update.ArchiveUrl'] = $data->update->package;
            }

            if (property_exists($data->update, 'url')) {
                $params['Update.MarketUrl'] = $data->update->url;
            }

            $response .= SucuriScanTemplate::getSnippet('posthack-updates', $params);
            $counter++;
        }
    }

    // Check for available theme updates.
    $result = wp_update_themes();
    $updates = get_theme_updates();

    if (is_array($updates) && !empty($updates)) {
        $counter = 0;

        foreach ($updates as $data) {
            $css_class = ($counter % 2 == 0) ? '' : 'alternate';
            $response .= SucuriScanTemplate::getSnippet(
                'posthack-updates',
                array(
                    'Update.CssClass' => $css_class,
                    'Update.IconType' => 'appearance',
                    'Update.Extension' => SucuriScan::excerpt($data->Name, 35),
                    'Update.Version' => $data->Version,
                    'Update.NewVersion' => $data->update['new_version'],
                    'Update.TestedWith' => 'Newest WordPress',
                    'Update.ArchiveUrl' => $data->update['package'],
                    'Update.MarketUrl' => $data->update['url'],
                )
            );
            $counter++;
        }
    }

    if (!is_string($response) || empty($response)) {
        return false;
    }

    // Send an email notification with the affected files.
    if ($send_email === true) {
        $params = array('AvailableUpdates.Content' => $response);
        $content = SucuriScanTemplate::getSection('posthack-updates-notification', $params);
        $sent = SucuriScanEvent::notify_event('available_updates', $content);

        return $sent;
    }

    return $response;
}

/**
 * Process the Ajax request to retrieve the available updates.
 *
 * @return string HTML code for a table with the updates information.
 */
function sucuriscan_posthack_updates_ajax()
{
    if (SucuriScanRequest::post('form_action') == 'get_available_updates') {
        $response = sucuriscan_posthack_updates_content();

        if (!$response) {
            $response = '<tr><td colspan="5">No updates available.</td></tr>';
        }

        header('Content-Type: text/html; charset=UTF-8');
        print($response);
        exit(0);
    }
}

/**
 * Process the request that will start the execution of the plugin
 * reinstallation, it will check if the plugins submitted are (in fact)
 * installed in the system, then check if they are free download from the
 * WordPress market place, and finally download and install them.
 *
 * @param  boolean $process_form Whether a form was submitted or not.
 * @return void
 */
function sucuriscan_posthack_reinstall_plugins($process_form = false)
{
    if ($process_form && isset($_POST['sucuriscan_reset_plugins'])) {
        include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        include_once(ABSPATH . 'wp-admin/includes/plugin-install.php'); // For plugins_api.

        if ($plugin_list = SucuriScanRequest::post('plugin_path', '_array')) {
            // Create an instance of the FileInfo interface.
            $file_info = new SucuriScanFileInfo();
            $file_info->ignore_files = false;
            $file_info->ignore_directories = false;
            $file_info->skip_directories = false;

            // Get (possible) cached information from the installed plugins.
            $all_plugins = SucuriScanAPI::getPlugins();

            // Loop through all the installed plugins.
            foreach ($plugin_list as $plugin_path) {
                if (!array_key_exists($plugin_path, $all_plugins)) {
                    // Ignore non-installed plugins.
                    continue;
                }

                // Get data associated to the plugin.
                $plugin_data = $all_plugins[$plugin_path];

                // Ignore plugins not listed in the WordPress repository.
                // This usually applies to premium plugins. They cannot be downloaded from
                // a reliable source because we can't check the checksum of the files nor
                // we can verify if the installation of the new code will work or not.
                if ($plugin_data['IsFreePlugin'] !== true) {
                    continue;
                }

                $plugin_info = SucuriScanAPI::getRemotePluginData($plugin_data['RepositoryName']);

                if ($plugin_info) {
                    // First, remove all files/sub-folders from the plugin's directory.
                    if (substr_count($plugin_path, '/') >= 1) {
                        $plugin_directory = dirname(WP_PLUGIN_DIR . '/' . $plugin_path);
                        $file_info->remove_directory_tree($plugin_directory);
                    }

                    // Install a fresh copy of the plugin's files.
                    try {
                        $upgrader_skin = new Plugin_Installer_Skin();
                        $upgrader = new Plugin_Upgrader($upgrader_skin);
                        $upgrader->install($plugin_info['download_link']);

                        SucuriScanEvent::report_notice_event('Plugin re-installed: ' . $plugin_path);
                    } catch (Exception $exception) {
                        SucuriScanEvent::report_exception($exception);
                    }
                } else {
                    SucuriScanInterface::error('Connection with the WordPress plugin market failed.');
                }
            }
        } else {
            SucuriScanInterface::error('You did not select a free plugin to reinstall.');
        }
    }
}
