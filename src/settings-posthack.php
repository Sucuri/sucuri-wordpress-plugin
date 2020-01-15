<?php

/**
 * Code related to the settings-posthack.php interface.
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
 * Tools to execute after a hack attempt.
 *
 * The plugin allows to execute some tools that will clear up the site after a
 * suspicious activity. This includes the ability to reset the secret security
 * keys, the password for each user account, and the installed plugins.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
 */
class SucuriScanSettingsPosthack extends SucuriScanSettings
{
    /**
     * Update the WordPress secret keys.
     *
     * @return string HTML code with the information of the process.
     */
    public static function securityKeys()
    {
        $params = array();

        $params['SecurityKeys.List'] = '';
        $params['WPConfigUpdate.NewConfig'] = '';
        $params['WPConfigUpdate.Visibility'] = 'hidden';

        // Update all WordPress secret keys.
        if (SucuriScanInterface::checkNonce() && SucuriScanRequest::post(':update_wpconfig')) {
            if (SucuriScanRequest::post(':process_form') != 1) {
                SucuriScanInterface::error(__('You need to confirm that you understand the risk of this operation.', 'sucuri-scanner'));
            } else {
                $wpconfig_process = SucuriScanEvent::setNewConfigKeys();

                if (!$wpconfig_process) {
                    SucuriScanInterface::error(__('WordPress configuration file was not found.', 'sucuri-scanner'));
                } elseif ($wpconfig_process['updated']) {
                    SucuriScanEvent::reportNoticeEvent(__('Generate new security keys (success)', 'sucuri-scanner'));
                    SucuriScanInterface::info(__('Secret keys updated successfully (summary of the operation bellow).', 'sucuri-scanner'));

                    $params['WPConfigUpdate.Visibility'] = 'visible';
                    $params['WPConfigUpdate.NewConfig'] .= sprintf("/* %s */\n", __('Old Security Keys', 'sucuri-scanner'));
                    $params['WPConfigUpdate.NewConfig'] .= $wpconfig_process['old_keys_string'];
                    $params['WPConfigUpdate.NewConfig'] .= "\n";
                    $params['WPConfigUpdate.NewConfig'] .= sprintf("/* %s */\n", __('New Security Keys', 'sucuri-scanner'));
                    $params['WPConfigUpdate.NewConfig'] .= $wpconfig_process['new_keys_string'];
                } else {
                    SucuriScanEvent::reportNoticeEvent(__('Generate new security keys (failure)', 'sucuri-scanner'));
                    SucuriScanInterface::error(__('WordPress configuration file is not writable.', 'sucuri-scanner'));

                    $params['WPConfigUpdate.Visibility'] = 'visible';
                    $params['WPConfigUpdate.NewConfig'] = $wpconfig_process['new_wpconfig'];
                }
            }
        }

        // Display the current status of the security keys.
        $current_keys = SucuriScanOption::getSecurityKeys();

        foreach ($current_keys as $key_status => $key_list) {
            foreach ($key_list as $key_name => $key_value) {
                switch ($key_status) {
                    case 'good':
                        $key_status_text = 'good';
                        break;

                    case 'bad':
                        $key_status_text = 'not randomized';
                        break;

                    case 'missing':
                        $key_value = '';
                        $key_status_text = '(not set)';
                        break;
                }

                if (isset($key_status_text)) {
                    $params['SecurityKeys.List'] .= SucuriScanTemplate::getSnippet(
                        'settings-posthack-security-keys',
                        array(
                            'SecurityKey.KeyName' => $key_name,
                            'SecurityKey.KeyValue' => $key_value,
                            'SecurityKey.KeyStatusText' => $key_status_text,
                        )
                    );
                }
            }
        }

        // Automatic Secret Keys Updater
        $cronName = 'sucuriscan_autoseckeyupdater';
        $params['SecurityKeys.AutoStatusNum'] = 0;
        $params['SecurityKeys.AutoStatus'] = "Disabled";
        $params['SecurityKeys.Schedules'] = '';
        $availableSchedules = array('disabled', 'daily', 'weekly', 'monthly', 'quarterly');

        // Populate frequency selection box
        foreach ($availableSchedules as $freq) {
            $params['SecurityKeys.Schedules'] .= sprintf('<option value="%s">%s</option>', $freq, ucfirst($freq));
        }
        // Set to enabled if cron is found
        if (wp_next_scheduled($cronName)) {
            $params['SecurityKeys.AutoStatusNum'] = 1;
            $params['SecurityKeys.AutoStatus'] = "Enabled";
        }

        // Activate/Deactivate the Automatic Secret Keys Updater
        if (SucuriScanInterface::checkNonce() && SucuriScanRequest::post(':autoseckeyupdater')) {
            $cronFrequency = SucuriScanRequest::post(':autoseckeyupdater_frequency');

            // Deny action if cron frequency is invalid
            if (empty($cronFrequency) || !in_array($cronFrequency, array_keys($availableSchedules))) {
                SucuriScanInterface::error(__('No frequency selected for the automatic secret key updater.', 'sucuri-scanner'));
            } elseif ($cronFrequency === "disabled") {
                // Disable Automatic Secret Keys Updater
                if (SucuriScanEvent::deleteScheduledTask($cronName)) {
                    $params['SecurityKeys.Schedules'] = str_replace('option value="'.$cronFrequency.'"', 'option value="'.$cronFrequency.'" selected', $params['SecurityKeys.Schedules']);
                    $params['SecurityKeys.AutoStatusNum'] = 0;
                    $params['SecurityKeys.AutoStatus'] = "Disabled";
                    SucuriScanInterface::info(__('Automatic Secret Keys Updater disabled.', 'sucuri-scanner'));
                    SucuriScanEvent::reportNoticeEvent(__('Automatic Secret Keys Updater disabled.', 'sucuri-scanner'));
                } else {
                    SucuriScanInterface::error(__('Something went wrong.', 'sucuri-scanner'));
                }
            } else {
                // Enable Automatic Secret Keys Updater
                if (SucuriScanEvent::addScheduledTask($cronName, $cronFrequency)) {
                    $params['SecurityKeys.Schedules'] = str_replace('option value="'.$cronFrequency.'"', 'option value="'.$cronFrequency.'" selected', $params['SecurityKeys.Schedules']);
                    $params['SecurityKeys.AutoStatusNum'] = 1;
                    $params['SecurityKeys.AutoStatus'] = "Enabled";
                    SucuriScanInterface::info(__('Automatic Secret Keys Updater enabled.', 'sucuri-scanner'));
                    SucuriScanEvent::reportNoticeEvent(__('Automatic Secret Keys Updater enabled.', 'sucuri-scanner'));
                } else {
                    SucuriScanInterface::error(__('Something went wrong.', 'sucuri-scanner'));
                }
            }
        } else {
            // Re-order selection box with the current cron frequency
            if (wp_next_scheduled($cronName)) {
                $currentCronFrequency = SucuriScanEvent::activeSchedules()[$cronName]['schedule'];
                $params['SecurityKeys.Schedules'] = str_replace('option value="'.$currentCronFrequency.'"', 'option value="'.$currentCronFrequency.'" selected', $params['SecurityKeys.Schedules']);
            }
        }

        return SucuriScanTemplate::getSection('settings-posthack-security-keys', $params);
    }

    /**
     * Display a list of users in a table that will be used to select the accounts
     * where a password reset action will be executed.
     *
     * @return string HTML code for a table where a list of user accounts will be shown.
     */
    public static function resetPassword()
    {
        $params = array();
        $session = wp_get_current_user();

        $params['ResetPassword.UserList'] = '';
        $params['ResetPassword.PaginationLinks'] = '';
        $params['ResetPassword.PaginationVisibility'] = 'hidden';

        // Fill the user list for ResetPassword action.
        $user_list = array();
        $page_number = SucuriScanTemplate::pageNumber();
        $max_per_page = SUCURISCAN_MAX_PAGINATION_BUTTONS;
        $dbquery = new WP_User_Query(
            array(
                'number' => $max_per_page,
                'offset' => ($page_number - 1) * $max_per_page,
                'fields' => 'all_with_meta',
                'orderby' => 'ID',
            )
        );

        // Retrieve the results and build the pagination links.
        if ($dbquery) {
            $total_items = $dbquery->get_total();
            $user_list = $dbquery->get_results();

            $params['ResetPassword.PaginationLinks'] = SucuriScanTemplate::pagination(
                '%%SUCURI.URL.Settings%%#posthack',
                $total_items,
                $max_per_page
            );

            if ($total_items > $max_per_page) {
                $params['ResetPassword.PaginationVisibility'] = 'visible';
            }
        }

        if ($user_list) {
            foreach ($user_list as $user) {
                $user->user_registered_timestamp = strtotime($user->user_registered);
                $user->user_registered_formatted = SucuriScan::datetime($user->user_registered_timestamp);
                $disabled = ($user->user_login == $session->user_login) ? 'disabled' : '';

                $params['ResetPassword.UserList'] .= SucuriScanTemplate::getSnippet(
                    'settings-posthack-reset-password',
                    array(
                        'ResetPassword.UserID' => $user->ID,
                        'ResetPassword.Username' => $user->user_login,
                        'ResetPassword.Email' => $user->user_email,
                        'ResetPassword.Registered' => $user->user_registered_formatted,
                        'ResetPassword.Roles' => @implode(', ', $user->roles),
                        'ResetPassword.Disabled' => $disabled,
                    )
                );
            }
        }

        return SucuriScanTemplate::getSection('settings-posthack-reset-password', $params);
    }

    /**
     * Sets a new password for the specified user account.
     *
     * @return void
     */
    public static function resetPasswordAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'reset_user_password') {
            return;
        }

        $response = 'Error';
        $user_id = intval(SucuriScanRequest::post('user_id'));

        if (SucuriScanEvent::setNewPassword($user_id)) {
            $response = 'Done';
            SucuriScanEvent::reportNoticeEvent(sprintf(__('Password changed for user #%d', 'sucuri-scanner'), $user_id));
        }

        wp_send_json($response, 200);
    }

    /**
     * Reset all the FREE plugins, even if they are not activated.
     *
     * @return void
     */
    public static function resetPlugins()
    {
        $params = array(
            'ResetPlugin.PluginList' => '',
            'ResetPlugin.CacheLifeTime' => 'unknown',
        );

        if (defined('SUCURISCAN_GET_PLUGINS_LIFETIME')) {
            $params['ResetPlugin.CacheLifeTime'] = SUCURISCAN_GET_PLUGINS_LIFETIME;
        }

        return SucuriScanTemplate::getSection('settings-posthack-reset-plugins', $params);
    }

    /**
     * Find and list available updates for plugins and themes.
     *
     * @return void
     */
    public static function availableUpdates()
    {
        $params = array();

        return SucuriScanTemplate::getSection('settings-posthack-available-updates', $params);
    }

    /**
     * Process the Ajax request to retrieve the plugins metadata.
     *
     * @return void
     */
    public static function getPluginsAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'get_plugins_data') {
            return;
        }

        $response = '';
        $allPlugins = SucuriScanAPI::getPlugins();

        foreach ($allPlugins as $plugin_path => $plugin_data) {
            $plugin_type_class = ($plugin_data['PluginType'] == 'free') ? 'primary' : 'warning';
            $input_disabled = ($plugin_data['PluginType'] == 'free') ? '' : 'disabled="disabled"';
            $plugin_status_class = $plugin_data['IsPluginActive'] ? 'success' : 'default';
            $plugin_status = $plugin_data['IsPluginActive'] ? 'active' : 'not active';

            $response .= SucuriScanTemplate::getSnippet(
                'settings-posthack-reset-plugins',
                array(
                    'ResetPlugin.Disabled' => $input_disabled,
                    'ResetPlugin.Path' => $plugin_path,
                    'ResetPlugin.Unique' => crc32($plugin_path),
                    'ResetPlugin.Repository' => $plugin_data['Repository'],
                    'ResetPlugin.Plugin' => SucuriScan::excerpt($plugin_data['Name'], 60),
                    'ResetPlugin.Version' => $plugin_data['Version'],
                    'ResetPlugin.Type' => $plugin_data['PluginType'],
                    'ResetPlugin.TypeClass' => $plugin_type_class,
                    'ResetPlugin.Status' => $plugin_status,
                    'ResetPlugin.StatusClass' => $plugin_status_class,
                )
            );
        }

        wp_send_json($response, true);
    }

    /**
     * Process the Ajax request to reset one free plugin.
     *
     * @return void
     */
    public static function resetPluginAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'reset_plugin') {
            return;
        }

        $response = ''; /* request response */
        $plugin = SucuriScanRequest::post(':plugin_name');
        $allPlugins = SucuriScanAPI::getPlugins();

        /* Check if the plugin actually exists */
        if (!array_key_exists($plugin, $allPlugins)) {
            $response = '<span class="sucuriscan-label-default">'
            . __('not installed', 'sucuri-scanner') . '</span>';
        } elseif ($allPlugins[$plugin]['IsFreePlugin'] !== true) {
            // Ignore plugins not listed in the WordPress repository.
            // This usually applies to premium plugins. They cannot be downloaded from
            // a reliable source because we can't check the checksum of the files nor
            // we can verify if the installation of the new code will work or not.
            $response = '<span class="sucuriscan-label-danger">'
            . __('Plugin is Premium', 'sucuri-scanner') . '</span>';
        } elseif (!is_writable($allPlugins[$plugin]['InstallationPath'])) {
            $response = '<span class="sucuriscan-label-danger">'
            . __('Not Writable', 'sucuri-scanner') . '</span>';
        } elseif (!class_exists('SucuriScanPluginInstallerSkin')) {
            $response = '<span class="sucuriscan-label-danger">'
            . __('Missing Library', 'sucuri-scanner') . '</span>';
        } else {
            // Get data associated to the plugin.
            $data = $allPlugins[$plugin];
            $info = SucuriScanAPI::getRemotePluginData($data['RepositoryName']);
            $hash = substr(md5(microtime(true)), 0, 8);
            $newpath = $data['InstallationPath'] . '_' . $hash;

            if (!$info) {
                $response = '<span class="sucuriscan-label-danger">'
                . __('Cannot Download', 'sucuri-scanner') . '</span>';
            } elseif (!rename($data['InstallationPath'], $newpath)) {
                $response = '<span class="sucuriscan-label-danger">'
                . __('Cannot Backup', 'sucuri-scanner') . '</span>';
            } else {
                ob_start();
                $upgrader_skin = new SucuriScanPluginInstallerSkin();
                $upgrader = new Plugin_Upgrader($upgrader_skin);
                $upgrader->install($info['download_link']);
                $output = ob_get_contents();
                ob_end_clean();

                if (!file_exists($data['InstallationPath'])) {
                    /* Revert backup to its original location */
                    @rename($newpath, $data['InstallationPath']);
                    $response = '<span class="sucuriscan-label-danger">'
                    . __('Cannot Install', 'sucuri-scanner') . '</span>';
                } else {
                    /* Destroy the backup of the plugin */
                    $fifo = new SucuriScanFileInfo();
                    $fifo->ignore_files = false;
                    $fifo->ignore_directories = false;
                    $fifo->skip_directories = false;
                    $fifo->removeDirectoryTree($newpath);

                    $installed = sprintf(__('Installed v%s', 'sucuri-scanner'), SucuriScan::escape($info['version']));
                    $response = '<span class="sucuriscan-label-success">' . $installed . '</span>';
                }
            }
        }

        wp_send_json($response, 200);
    }

    /**
     * Retrieve the information for the available updates.
     *
     * @param  bool $send_email Sends the available updates via email.
     * @return string|bool      HTML code for a table with the updates information.
     */
    public static function availableUpdatesContent($send_email = false)
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
            foreach ($updates as $data) {
                $params = array(
                    'Update.IconType' => 'plugins',
                    'Update.Extension' => SucuriScan::excerpt($data->Name, 35),
                    'Update.Version' => $data->Version,
                    'Update.NewVersion' => 'unknown',
                    'Update.TestedWith' => 'unknown',
                    'Update.ArchiveUrl' => 'unknown',
                    'Update.MarketUrl' => 'unknown',
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

                $response .= SucuriScanTemplate::getSnippet('settings-posthack-available-updates', $params);
            }
        }

        // Check for available theme updates.
        $result = wp_update_themes();
        $updates = get_theme_updates();

        if (is_array($updates) && !empty($updates)) {
            foreach ($updates as $data) {
                $response .= SucuriScanTemplate::getSnippet(
                    'settings-posthack-available-updates',
                    array(
                        'Update.IconType' => 'appearance',
                        'Update.Extension' => SucuriScan::excerpt($data->Name, 35),
                        'Update.Version' => $data->Version,
                        'Update.NewVersion' => $data->update['new_version'],
                        'Update.TestedWith' => __('Newest WordPress', 'sucuri-scanner'),
                        'Update.ArchiveUrl' => $data->update['package'],
                        'Update.MarketUrl' => $data->update['url'],
                    )
                );
            }
        }

        if (!is_string($response) || empty($response)) {
            return false;
        }

        // Send an email notification with the affected files.
        if ($send_email === true) {
            $params = array('AvailableUpdates.Content' => $response);
            $content = SucuriScanTemplate::getSection('settings-posthack-available-updates-alert', $params);
            $sent = SucuriScanEvent::notifyEvent('available_updates', $content);

            return $sent;
        }

        return $response;
    }

    /**
     * Process the Ajax request to retrieve the available updates.
     *
     * @return void
     */
    public static function availableUpdatesAjax()
    {
        if (SucuriScanRequest::post('form_action') !== 'get_available_updates') {
            return;
        }

        $response = SucuriScanSettingsPosthack::availableUpdatesContent();

        if (!$response) {
            $response = '<tr><td colspan="5">' . __('There are no updates available.', 'sucuri-scanner') . '</td></tr>';
        }

        wp_send_json($response, 200);
    }
}
