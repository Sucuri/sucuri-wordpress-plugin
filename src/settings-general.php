<?php

/**
 * Read and parse the content of the general settings template.
 *
 * @return string Parsed HTML code for the general settings panel.
 */
function sucuriscan_settings_general($nonce)
{
    // Process all form submissions.
    sucuriscan_settings_form_submissions($nonce);

    $params = array();

    // Keep the reset options panel and form submission processor before anything else.
    $params['SettingsSection.ResetOptions'] = sucuriscan_settings_general_resetoptions($nonce);

    // Build HTML code for the additional general settings panels.
    $params['SettingsSection.ApiKey'] = sucuriscan_settings_general_apikey($nonce);
    $params['SettingsSection.DataStorage'] = sucuriscan_settings_general_datastorage($nonce);
    $params['SettingsSection.ReverseProxy'] = sucuriscan_settings_general_reverseproxy($nonce);
    $params['SettingsSection.PasswordCollector'] = sucuriscan_settings_general_pwdcollector($nonce);
    $params['SettingsSection.IPDiscoverer'] = sucuriscan_settings_general_ipdiscoverer($nonce);
    $params['SettingsSection.CommentMonitor'] = sucuriscan_settings_general_commentmonitor($nonce);
    $params['SettingsSection.XhrMonitor'] = sucuriscan_settings_general_xhrmonitor($nonce);
    $params['SettingsSection.AuditLogStats'] = sucuriscan_settings_general_auditlogstats($nonce);
    $params['SettingsSection.Datetime'] = sucuriscan_settings_general_datetime($nonce);

    return SucuriScanTemplate::getSection('settings-general', $params);
}

function sucuriscan_settings_general_resetoptions($nonce)
{
    // Reset all the plugin's options.
    if ($nonce && SucuriScanRequest::post(':reset_options') !== false) {
        $process = SucuriScanRequest::post(':process_form');

        if (intval($process) === 1) {
            // Notify the event before the API key is removed.
            $message = 'Sucuri plugin options were reset';
            SucuriScanEvent::report_critical_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);

            // Remove all plugin options from the database.
            SucuriScanOption::delete_plugin_options();

            // Remove the scheduled tasks.
            wp_clear_scheduled_hook('sucuriscan_scheduled_scan');

            // Remove all the local security logs.
            @unlink(SucuriScan::datastore_folder_path('.htaccess'));
            @unlink(SucuriScan::datastore_folder_path('index.html'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-failedlogins.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-integrity.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-lastlogins.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-oldfailedlogins.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-plugindata.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-sitecheck.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-settings.php'));
            @unlink(SucuriScan::datastore_folder_path('sucuri-trustip.php'));
            @rmdir(SucuriScan::datastore_folder_path());

            // Revert hardening of core directories (includes, content, uploads).
            SucuriScanHardening::dewhitelist('ms-files.php', 'wp-includes');
            SucuriScanHardening::dewhitelist('wp-tinymce.php', 'wp-includes');
            SucuriScanHardening::unharden_directory(ABSPATH . '/wp-includes');
            SucuriScanHardening::unharden_directory(WP_CONTENT_DIR . '/uploads');
            SucuriScanHardening::unharden_directory(WP_CONTENT_DIR);

            SucuriScanInterface::info('Plugin options, core directory hardening, and security logs were reset');
        } else {
            SucuriScanInterface::error('You need to confirm that you understand the risk of this operation.');
        }
    }

    return SucuriScanTemplate::getSection('settings-general-resetoptions');
}

function sucuriscan_settings_general_apikey($nonce)
{
    $params = array();
    $invalid_domain = false;
    $api_recovery_modal = '';
    $api_registered_modal = '';

    // Whether the form to manually add the API key should be shown or not.
    $display_manual_key_form = (bool) (SucuriScanRequest::post(':recover_key') !== false);

    if ($nonce) {
        if (!empty($_POST) && SucuriScanOption::settingsInTextFile()) {
            $fpath = SucuriScanOption::optionsFilePath();

            if (!is_writable($fpath)) {
                SucuriScanInterface::error(
                    'Storage is not writable: <code>'
                    . $fpath . '</code>'
                );
            }
        }

        // Remove API key from the local storage.
        if (SucuriScanRequest::post(':remove_api_key') !== false) {
            SucuriScanAPI::setPluginKey('');
            wp_clear_scheduled_hook('sucuriscan_scheduled_scan');
            SucuriScanEvent::report_critical_event('Sucuri API key was deleted.');
            SucuriScanEvent::notify_event('plugin_change', 'Sucuri API key removed');
        }

        // Save API key after it was recovered by the administrator.
        if ($api_key = SucuriScanRequest::post(':manual_api_key')) {
            SucuriScanAPI::setPluginKey($api_key, true);
            SucuriScanEvent::schedule_task();
            SucuriScanEvent::report_info_event('Sucuri API key was added manually.');
        }

        // Generate new API key from the API service.
        if (SucuriScanRequest::post(':plugin_api_key') !== false) {
            $user_id = SucuriScanRequest::post(':setup_user');
            $user_obj = SucuriScan::get_user_by_id($user_id);

            if ($user_obj !== false && user_can($user_obj, 'administrator')) {
                // Send request to generate new API key or display form to set manually.
                if (SucuriScanAPI::registerSite($user_obj->user_email)) {
                    $api_registered_modal = SucuriScanTemplate::getModal(
                        'settings-apiregistered',
                        array(
                            'Title' => 'Site registered successfully',
                            'CssClass' => 'sucuriscan-apikey-registered',
                        )
                    );
                } else {
                    $display_manual_key_form = true;
                }
            }
        }

        // Recover API key through the email registered previously.
        if (SucuriScanRequest::post(':recover_key') !== false) {
            $_GET['recover'] = 'true';
            SucuriScanAPI::recoverKey();
            SucuriScanEvent::report_info_event('Recovery of the Sucuri API key was requested.');
        }
    }

    $api_key = SucuriScanAPI::getPluginKey();

    if (SucuriScanRequest::get('recover') !== false) {
        $api_recovery_modal = SucuriScanTemplate::getModal(
            'settings-apirecovery',
            array(
                'Title' => 'Plugin API Key Recovery',
                'CssClass' => 'sucuriscan-apirecovery',
            )
        );
    }

    // Check whether the domain name is valid or not.
    if (!$api_key) {
        $clean_domain = SucuriScan::get_top_level_domain();
        $domain_address = @gethostbyname($clean_domain);
        $invalid_domain = (bool) ($domain_address === $clean_domain);
    }

    $params['APIKey'] = (!$api_key ? '(not set)' : $api_key);
    $params['APIKey.RecoverVisibility'] = SucuriScanTemplate::visibility(!$api_key && !$display_manual_key_form);
    $params['APIKey.ManualKeyFormVisibility'] = SucuriScanTemplate::visibility($display_manual_key_form);
    $params['APIKey.RemoveVisibility'] = SucuriScanTemplate::visibility((bool) $api_key);
    $params['InvalidDomainVisibility'] = SucuriScanTemplate::visibility($invalid_domain);
    $params['ModalWhenAPIRegistered'] = $api_registered_modal;
    $params['ModalForApiKeyRecovery'] = $api_recovery_modal;

    return SucuriScanTemplate::getSection('settings-general-apikey', $params);
}

function sucuriscan_settings_general_datastorage($nonce)
{
    $params = array();
    $files = array(
        '', /* <root> */
        'auditqueue',
        'blockedusers',
        'failedlogins',
        'ignorescanning',
        'integrity',
        'lastlogins',
        'oldfailedlogins',
        'plugindata',
        'settings',
        'sitecheck',
        'trustip',
    );

    $counter = 0;
    $params['DataStorage.Files'] = '';
    $params['DatastorePath'] = SucuriScanOption::get_option(':datastore_path');

    foreach ($files as $name) {
        $counter++;
        $fname = ($name ? sprintf('sucuri-%s.php', $name) : '');
        $fpath = SucuriScan::datastore_folder_path($fname);
        $exists = (file_exists($fpath) ? 'Yes' : 'No');
        $iswritable = (is_writable($fpath) ? 'Yes' : 'No');
        $css_class = ($counter % 2 === 0) ? 'alternate' : '';
        $disabled = 'disabled="disabled"';

        if ($exists === 'Yes' && $iswritable === 'Yes') {
            $disabled = ''; /* Allow file deletion */
        }

        // Remove unnecessary parts from the file path.
        $fpath = str_replace(ABSPATH, '/', $fpath);

        $params['DataStorage.Files'] .= SucuriScanTemplate::getSnippet(
            'settings-datastorage-files',
            array(
                'DataStorage.CssClass' => $css_class,
                'DataStorage.Fname' => $fname,
                'DataStorage.Fpath' => $fpath,
                'DataStorage.Exists' => $exists,
                'DataStorage.IsWritable' => $iswritable,
                'DataStorage.DisabledInput' => $disabled,
            )
        );
    }

    return SucuriScanTemplate::getSection('settings-general-datastorage', $params);
}

function sucuriscan_settings_general_reverseproxy($nonce)
{
    $params = array(
        'ReverseProxyStatus' => 'Enabled',
        'ReverseProxySwitchText' => 'Disable',
        'ReverseProxySwitchValue' => 'disable',
        'ReverseProxySwitchCssClass' => 'button-danger',
    );

    // Enable or disable the reverse proxy support.
    if ($nonce) {
        $revproxy = SucuriScanRequest::post(':revproxy', '(en|dis)able');

        if ($revproxy) {
            if ($revproxy === 'enable') {
                SucuriScanOption::setRevProxy('enable');
                SucuriScanOption::setAddrHeader('HTTP_X_SUCURI_CLIENTIP');
            } else {
                SucuriScanOption::setRevProxy('disable');
                SucuriScanOption::setAddrHeader('REMOTE_ADDR');
            }
        }
    }

    if (SucuriScanOption::is_disabled(':revproxy')) {
        $params['ReverseProxyStatus'] = 'Disabled';
        $params['ReverseProxySwitchText'] = 'Enable';
        $params['ReverseProxySwitchValue'] = 'enable';
        $params['ReverseProxySwitchCssClass'] = 'button-success';
    }

    return SucuriScanTemplate::getSection('settings-general-reverseproxy', $params);
}

function sucuriscan_settings_general_pwdcollector($nonce)
{
    $params = array(
        'PwdCollectorStatus' => 'Disabled',
        'PwdCollectorSwitchText' => 'Enable',
        'PwdCollectorSwitchValue' => 'enable',
        'PwdCollectorSwitchCssClass' => 'button-success',
    );

    // Update the collection of failed passwords settings.
    if ($nonce) {
        $collector = SucuriScanRequest::post(':collect_wrong_passwords');

        if ($collector) {
            $collector = strtolower($collector);
            $message = 'Collect failed login passwords set to <code>%s</code>';

            if ($collector == 'enable') {
                $collect_action = 'enabled';
                $message = sprintf($message, $collect_action);
                SucuriScanEvent::report_critical_event($message);
            } else {
                $collect_action = 'disabled';
                $message = sprintf($message, $collect_action);
                SucuriScanEvent::report_info_event($message);
            }

            SucuriScanOption::update_option(':collect_wrong_passwords', $collect_action);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }
    }

    if (sucuriscan_collect_wrong_passwords() === true) {
        $params['PwdCollectorStatus'] = 'Enabled';
        $params['PwdCollectorSwitchText'] = 'Disable';
        $params['PwdCollectorSwitchValue'] = 'disable';
        $params['PwdCollectorSwitchCssClass'] = 'button-danger';
    }

    return SucuriScanTemplate::getSection('settings-general-pwdcollector', $params);
}

function sucuriscan_settings_general_ipdiscoverer($nonce)
{
    $params = array(
        'TopLevelDomain' => 'Unknown',
        'WebsiteHostName' => 'Unknown',
        'WebsiteHostAddress' => 'Unknown',
        'IsUsingCloudProxy' => 'Unknown',
        'WebsiteURL' => 'Unknown',
        'RemoteAddress' => '127.0.0.1',
        'RemoteAddressHeader' => 'INVALID',
        'AddrHeaderOptions' => '',
        /* Switch form information. */
        'DnsLookupsStatus' => 'Enabled',
        'DnsLookupsSwitchText' => 'Disable',
        'DnsLookupsSwitchValue' => 'disable',
        'DnsLookupsSwitchCssClass' => 'button-danger',
    );

    // Get main HTTP header for IP retrieval.
    $allowed_headers = SucuriScan::allowedHttpHeaders(true);

    // Configure the DNS lookups option for reverse proxy detection.
    if ($nonce) {
        $dns_lookups = SucuriScanRequest::post(':dns_lookups', '(en|dis)able');
        $addr_header = SucuriScanRequest::post(':addr_header');

        if ($dns_lookups) {
            $action_d = $dns_lookups . 'd';
            $message = 'DNS lookups for reverse proxy detection <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':dns_lookups', $action_d);
            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        if ($addr_header) {
            if ($addr_header === 'REMOTE_ADDR') {
                SucuriScanOption::setAddrHeader('REMOTE_ADDR');
                SucuriScanOption::setRevProxy('disable');
            } else {
                SucuriScanOption::setAddrHeader($addr_header);
                SucuriScanOption::setRevProxy('enable');
            }
        }
    }

    if (SucuriScanOption::is_disabled(':dns_lookups')) {
        $params['DnsLookupsStatus'] = 'Disabled';
        $params['DnsLookupsSwitchText'] = 'Enable';
        $params['DnsLookupsSwitchValue'] = 'enable';
        $params['DnsLookupsSwitchCssClass'] = 'button-success';
    }

    $proxy_info = SucuriScan::is_behind_cloudproxy(true);
    $base_domain = SucuriScan::get_domain(true);

    $params['TopLevelDomain'] = $proxy_info['http_host'];
    $params['WebsiteHostName'] = $proxy_info['host_name'];
    $params['WebsiteHostAddress'] = $proxy_info['host_addr'];
    $params['IsUsingCloudProxy'] = ($proxy_info['status'] ? 'Active' : 'Not Active');
    $params['RemoteAddressHeader'] = SucuriScan::get_remote_addr_header();
    $params['RemoteAddress'] = SucuriScan::get_remote_addr();
    $params['WebsiteURL'] = SucuriScan::get_domain();
    $params['AddrHeaderOptions'] = SucuriScanTemplate::selectOptions(
        $allowed_headers,
        SucuriScanOption::get_option(':addr_header')
    );

    if ($base_domain !== $proxy_info['http_host']) {
        $params['TopLevelDomain'] = sprintf('%s (%s)', $params['TopLevelDomain'], $base_domain);
    }

    return SucuriScanTemplate::getSection('settings-general-ipdiscoverer', $params);
}

function sucuriscan_settings_general_commentmonitor($nonce)
{
    $params = array(
        'CommentMonitorStatus' => 'Enabled',
        'CommentMonitorSwitchText' => 'Disable',
        'CommentMonitorSwitchValue' => 'disable',
        'CommentMonitorSwitchCssClass' => 'button-danger',
    );

    // Configure the comment monitor option.
    if ($nonce) {
        $monitor = SucuriScanRequest::post(':comment_monitor', '(en|dis)able');

        if ($monitor) {
            $action_d = $monitor . 'd';
            $message = 'Comment monitor was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':comment_monitor', $action_d);
            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }
    }

    if (SucuriScanOption::is_disabled(':comment_monitor')) {
        $params['CommentMonitorStatus'] = 'Disabled';
        $params['CommentMonitorSwitchText'] = 'Enable';
        $params['CommentMonitorSwitchValue'] = 'enable';
        $params['CommentMonitorSwitchCssClass'] = 'button-success';
    }

    return SucuriScanTemplate::getSection('settings-general-commentmonitor', $params);
}

function sucuriscan_settings_general_xhrmonitor($nonce)
{
    $params = array(
        'XhrMonitorStatus' => 'Enabled',
        'XhrMonitorSwitchText' => 'Disable',
        'XhrMonitorSwitchValue' => 'disable',
        'XhrMonitorSwitchCssClass' => 'button-danger',
    );

    // Configure the XHR monitor option.
    if ($nonce) {
        $monitor = SucuriScanRequest::post(':xhr_monitor', '(en|dis)able');

        if ($monitor) {
            $action_d = $monitor . 'd';
            $message = 'XHR (XML HTTP Request) monitor was <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':xhr_monitor', $action_d);
            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }
    }

    if (SucuriScanOption::is_disabled(':xhr_monitor')) {
        $params['XhrMonitorStatus'] = 'Disabled';
        $params['XhrMonitorSwitchText'] = 'Enable';
        $params['XhrMonitorSwitchValue'] = 'enable';
        $params['XhrMonitorSwitchCssClass'] = 'button-success';
    }

    return SucuriScanTemplate::getSection('settings-general-xhrmonitor', $params);
}

function sucuriscan_settings_general_auditlogstats($nonce)
{
    $params = array();
    $params['AuditLogStats.StatusNum'] = '1';
    $params['AuditLogStats.Status'] = 'Enabled';
    $params['AuditLogStats.SwitchText'] = 'Disable';
    $params['AuditLogStats.SwitchValue'] = 'disable';
    $params['AuditLogStats.SwitchCssClass'] = 'button-danger';
    $params['AuditLogStats.Limit'] = 0;

    if ($nonce) {
        // Update the limit for audit logs report.
        if ($logs4report = SucuriScanRequest::post(':logs4report', '[0-9]{1,4}')) {
            $_POST['sucuriscan_audit_report'] = 'enable';
            $message = 'Audit log statistics limit set to <code>' . $logs4report . '</code>';

            SucuriScanOption::update_option(':logs4report', $logs4report);
            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }

        // Enable or disable the audit logs report.
        if ($audit_report = SucuriScanRequest::post(':audit_report', '(en|dis)able')) {
            $action_d = $audit_report . 'd';
            $message = 'Audit log statistics were <code>' . $action_d . '</code>';

            SucuriScanOption::update_option(':audit_report', $action_d);
            SucuriScanEvent::report_info_event($message);
            SucuriScanEvent::notify_event('plugin_change', $message);
            SucuriScanInterface::info($message);
        }
    }

    $logs4report = SucuriScanOption::get_option(':logs4report');
    $audit_report = SucuriScanOption::get_option(':audit_report');
    $params['AuditLogStats.Limit'] = SucuriScan::escape($logs4report);

    if ($audit_report === 'disabled') {
        $params['AuditLogStats.StatusNum'] = '0';
        $params['AuditLogStats.Status'] = 'Disabled';
        $params['AuditLogStats.SwitchText'] = 'Enable';
        $params['AuditLogStats.SwitchValue'] = 'enable';
        $params['AuditLogStats.SwitchCssClass'] = 'button-success';
    }

    return SucuriScanTemplate::getSection('settings-general-auditlogstats', $params);
}

function sucuriscan_settings_general_datetime($nonce)
{
    $params = array();
    $params['Datetime.AdminURL'] = SucuriScan::admin_url('options-general.php');
    $params['Datetime.HumanReadable'] = SucuriScan::current_datetime();
    $params['Datetime.Timestamp'] = SucuriScan::local_time();
    $params['Datetime.Timezone'] = 'Unknown';

    if (function_exists('wp_timezone_choice')) {
        $gmt_offset = SucuriScanOption::get_option('gmt_offset');
        $tzstring = SucuriScanOption::get_option('timezone_string');

        $params['Datetime.Timezone'] = empty($tzstring) ? 'UTC' . $gmt_offset : $tzstring;
    }

    return SucuriScanTemplate::getSection('settings-general-datetime', $params);
}
