<?php

/**
 * Code related to the settings-general.php interface.
 *
 * @package Sucuri Security
 * @subpackage settings-general.php
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
 * Renders a page with information about the reset options feature.
 *
 * @param bool $nonce True if the CSRF protection worked.
 * @return string Page with information about the reset options.
 */
function sucuriscan_settings_general_resetoptions($nonce)
{
    // Reset all the plugin's options.
    if ($nonce && SucuriScanRequest::post(':reset_options') !== false) {
        $process = SucuriScanRequest::post(':process_form');

        if (intval($process) === 1) {
            $message = 'Local security logs, hardening and settings were deleted';

            sucuriscan_deactivate(); /* simulate plugin deactivation */

            SucuriScanEvent::reportCriticalEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanInterface::info(__('PluginResetSuccess', SUCURISCAN_TEXTDOMAIN));
        } else {
            SucuriScanInterface::error(__('ConfirmOperation', SUCURISCAN_TEXTDOMAIN));
        }
    }

    return SucuriScanTemplate::getSection('settings-general-resetoptions');
}

/**
 * Renders a page with information about the API key feature.
 *
 * @param bool $nonce True if the CSRF protection worked.
 * @return string Page with information about the API key.
 */
function sucuriscan_settings_general_apikey($nonce)
{
    $params = array();
    $invalid_domain = false;
    $api_recovery_modal = '';
    $api_registered_modal = '';

    // Whether the form to manually add the API key should be shown or not.
    $display_manual_key_form = (bool) (SucuriScanRequest::post(':recover_key') !== false);

    if ($nonce) {
        if (!empty($_POST)) {
            $fpath = SucuriScanOption::optionsFilePath();

            if (!is_writable($fpath)) {
                SucuriScanInterface::error(sprintf(
                    __('StorageNotWritable', SUCURISCAN_TEXTDOMAIN),
                    $fpath /* absolute path of the data storage folder */
                ));
            }
        }

        // Remove API key from the local storage.
        if (SucuriScanRequest::post(':remove_api_key') !== false) {
            SucuriScanAPI::setPluginKey('');
            wp_clear_scheduled_hook('sucuriscan_scheduled_scan');
            SucuriScanEvent::reportCriticalEvent('Sucuri API key was deleted.');
            SucuriScanEvent::notifyEvent('plugin_change', 'Sucuri API key removed');
        }

        // Save API key after it was recovered by the administrator.
        if ($api_key = SucuriScanRequest::post(':manual_api_key')) {
            SucuriScanAPI::setPluginKey($api_key, true);
            SucuriScanEvent::installScheduledTask();
            SucuriScanEvent::reportInfoEvent('Sucuri API key was added manually.');
        }

        // Generate new API key from the API service.
        if (SucuriScanRequest::post(':plugin_api_key') !== false) {
            $user_id = (int) SucuriScanRequest::post(':setup_user');
            $user_obj = SucuriScan::getUserByID($user_id);

            if ($user_obj && user_can($user_obj, 'administrator')) {
                // Send request to generate new API key or display form to set manually.
                if (SucuriScanAPI::registerSite($user_obj->user_email)) {
                    $api_registered_modal = SucuriScanTemplate::getModal('settings-apiregistered', array(
                        'Title' => __('SiteWasRegistered', SUCURISCAN_TEXTDOMAIN),
                    ));
                } else {
                    $display_manual_key_form = true;
                }
            }
        }

        // Recover API key through the email registered previously.
        if (SucuriScanRequest::post(':recover_key') !== false) {
            if (SucuriScanAPI::recoverKey()) {
                $_GET['recover'] = 'true'; /* display modal window */
                SucuriScanEvent::reportInfoEvent('API key recovery (email sent)');
            } else {
                SucuriScanEvent::reportInfoEvent('API key recovery (failure)');
            }
        }
    }

    $api_key = SucuriScanAPI::getPluginKey();

    if (SucuriScanRequest::get('recover') !== false) {
        $api_recovery_modal = SucuriScanTemplate::getModal('settings-apirecovery', array(
            'Title' => __('APIKeyRecovery', SUCURISCAN_TEXTDOMAIN),
        ));
    }

    // Check whether the domain name is valid or not.
    if (!$api_key) {
        $clean_domain = SucuriScan::getTopLevelDomain();
        $domain_address = @gethostbyname($clean_domain);
        $invalid_domain = (bool) ($domain_address === $clean_domain);
    }

    $params['APIKey'] = (!$api_key ? __('NotSet', SUCURISCAN_TEXTDOMAIN) : $api_key);
    $params['APIKey.RecoverVisibility'] = SucuriScanTemplate::visibility(!$api_key);
    $params['APIKey.ManualKeyFormVisibility'] = SucuriScanTemplate::visibility($display_manual_key_form);
    $params['APIKey.RemoveVisibility'] = SucuriScanTemplate::visibility((bool) $api_key);
    $params['InvalidDomainVisibility'] = SucuriScanTemplate::visibility($invalid_domain);
    $params['ModalWhenAPIRegistered'] = $api_registered_modal;
    $params['ModalForApiKeyRecovery'] = $api_recovery_modal;

    return SucuriScanTemplate::getSection('settings-general-apikey', $params);
}

/**
 * Renders a page with information about the data storage feature.
 *
 * @param bool $nonce True if the CSRF protection worked.
 * @return string Page with information about the data storage.
 */
function sucuriscan_settings_general_datastorage()
{
    $params = array();
    $files = array(
        '', /* <root> */
        'auditlogs',
        'auditqueue',
        'blockedusers',
        'failedlogins',
        'hookdata',
        'ignorescanning',
        'integrity',
        'lastlogins',
        'oldfailedlogins',
        'plugindata',
        'settings',
        'sitecheck',
        'trustip',
    );

    $params['Storage.Files'] = '';
    $params['Storage.Path'] = SucuriScan::dataStorePath();

    if (SucuriScanInterface::checkNonce()) {
        if ($filenames = SucuriScanRequest::post(':filename', '_array')) {
            $deleted = 0;

            foreach ($filenames as $filename) {
                $short = substr($filename, 7); /* drop directroy path */
                $short = substr($short, 0, -4); /* drop file extension */

                if (!$short || empty($short) || !in_array($short, $files)) {
                    continue; /* prevent path traversal */
                }

                $filepath = SucuriScan::dataStorePath($filename);

                if (!file_exists($filepath) || is_dir($filepath)) {
                    continue; /* there is nothing to reset */
                }

                /* ignore write permissions */
                if (@unlink($filepath)) {
                    $deleted++;
                }
            }

            SucuriScanInterface::info(sprintf(
                __('NFilesWereDeleted', SUCURISCAN_TEXTDOMAIN),
                $deleted,
                count($filenames)
            ));
        }
    }

    foreach ($files as $name) {
        $fsize = 0;
        $fname = ($name ? sprintf('sucuri-%s.php', $name) : '');
        $fpath = SucuriScan::dataStorePath($fname);
        $disabled = 'disabled="disabled"';
        $iswritable = __('NotWritable', SUCURISCAN_TEXTDOMAIN);
        $exists = __('DoesNotExist', SUCURISCAN_TEXTDOMAIN);
        $labelExistence = 'danger';
        $labelWritability = 'default';

        if (file_exists($fpath)) {
            $fsize = @filesize($fpath);
            $exists = __('Exists', SUCURISCAN_TEXTDOMAIN);
            $labelExistence = 'success';
            $labelWritability = 'danger';

            if (is_writable($fpath)) {
                $disabled = ''; /* Allow file deletion */
                $iswritable = __('Writable', SUCURISCAN_TEXTDOMAIN);
                $labelWritability = 'success';
            }
        }

        $params['Storage.Filename'] = $fname;
        $params['Storage.Filepath'] = str_replace(ABSPATH, '', $fpath);
        $params['Storage.Filesize'] = SucuriScan::humanFileSize($fsize);
        $params['Storage.Exists'] = $exists;
        $params['Storage.IsWritable'] = $iswritable;
        $params['Storage.DisabledInput'] = $disabled;
        $params['Storage.Existence'] = $labelExistence;
        $params['Storage.Writability'] = $labelWritability;

        if (is_dir($fpath)) {
            $params['Storage.DisabledInput'] = 'disabled="disabled"';
            $params['Storage.Filesize'] = '' /* empty */;
        }

        $params['Storage.Files'] .= SucuriScanTemplate::getSnippet('settings-general-datastorage', $params);
    }

    return SucuriScanTemplate::getSection('settings-general-datastorage', $params);
}

/**
 * Returns the path to the local event monitoring file.
 *
 * The website owner can configure the plugin to send a copy of the security
 * events to a local file that can be integrated with other monitoring systems
 * like OSSEC, OpenVAS, NewRelic and similar.
 *
 * @return string|bool Path to the log file, false if disabled.
 */
function sucuriscan_selfhosting_fpath()
{
    $monitor = SucuriScanOption::getOption(':selfhosting_monitor');
    $monitor_fpath = SucuriScanOption::getOption(':selfhosting_fpath');
    $folder = dirname($monitor_fpath);

    if ($monitor === 'enabled'
        && !empty($monitor_fpath)
        && is_writable($folder)
    ) {
        return $monitor_fpath;
    }

    return false;
}

/**
 * Renders a page with information about the self-hosting feature.
 *
 * @param bool $nonce True if the CSRF protection worked.
 * @return string Page with information about the self-hosting.
 */
function sucuriscan_settings_general_selfhosting($nonce)
{
    $params = array();

    $params['SelfHosting.DisabledVisibility'] = 'visible';
    $params['SelfHosting.Status'] = __('Enabled', SUCURISCAN_TEXTDOMAIN);
    $params['SelfHosting.SwitchText'] = __('Disable', SUCURISCAN_TEXTDOMAIN);
    $params['SelfHosting.SwitchValue'] = 'disable';
    $params['SelfHosting.FpathVisibility'] = 'hidden';
    $params['SelfHosting.Fpath'] = '';

    if ($nonce) {
        // Set a file path for the self-hosted event monitor.
        $monitor_fpath = SucuriScanRequest::post(':selfhosting_fpath');

        if ($monitor_fpath !== false) {
            if (empty($monitor_fpath)) {
                $message = 'Log exporter was disabled.';

                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanOption::deleteOption(':selfhosting_fpath');
                SucuriScanOption::updateOption(':selfhosting_monitor', 'disabled');
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info(__('SelfHostingDisabled', SUCURISCAN_TEXTDOMAIN));
            } elseif (strpos($monitor_fpath, $_SERVER['DOCUMENT_ROOT']) !== false) {
                SucuriScanInterface::error(__('AvoidDocumentRoot', SUCURISCAN_TEXTDOMAIN));
            } elseif (file_exists($monitor_fpath)) {
                SucuriScanInterface::error(__('AvoidFileOverride', SUCURISCAN_TEXTDOMAIN));
            } elseif (!is_writable(dirname($monitor_fpath))) {
                SucuriScanInterface::error(__('ParentNotWritable', SUCURISCAN_TEXTDOMAIN));
            } else {
                @file_put_contents($monitor_fpath, '', LOCK_EX);

                $message = 'Log exporter file path was set correctly.';

                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanOption::updateOption(':selfhosting_monitor', 'enabled');
                SucuriScanOption::updateOption(':selfhosting_fpath', $monitor_fpath);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info(__('SelfHostingEnabled', SUCURISCAN_TEXTDOMAIN));
            }
        }
    }

    $monitor = SucuriScanOption::getOption(':selfhosting_monitor');
    $monitor_fpath = SucuriScanOption::getOption(':selfhosting_fpath');

    if ($monitor === 'disabled') {
        $params['SelfHosting.Status'] = __('Disabled', SUCURISCAN_TEXTDOMAIN);
        $params['SelfHosting.SwitchText'] = __('Enable', SUCURISCAN_TEXTDOMAIN);
        $params['SelfHosting.SwitchValue'] = 'enable';
    }

    if ($monitor === 'enabled' && $monitor_fpath) {
        $params['SelfHosting.DisabledVisibility'] = 'hidden';
        $params['SelfHosting.FpathVisibility'] = 'visible';
        $params['SelfHosting.Fpath'] = SucuriScan::escape($monitor_fpath);
    }

    return SucuriScanTemplate::getSection('settings-general-selfhosting', $params);
}

/**
 * Renders a page with information about the reverse proxy feature.
 *
 * @param bool $nonce True if the CSRF protection worked.
 * @return string Page with information about the reverse proxy.
 */
function sucuriscan_settings_general_reverseproxy($nonce)
{
    $params = array(
        'ReverseProxyStatus' => __('Enabled', SUCURISCAN_TEXTDOMAIN),
        'ReverseProxySwitchText' => __('Disable', SUCURISCAN_TEXTDOMAIN),
        'ReverseProxySwitchValue' => 'disable',
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

    if (SucuriScanOption::isDisabled(':revproxy')) {
        $params['ReverseProxyStatus'] = __('Disabled', SUCURISCAN_TEXTDOMAIN);
        $params['ReverseProxySwitchText'] = __('Enable', SUCURISCAN_TEXTDOMAIN);
        $params['ReverseProxySwitchValue'] = 'enable';
    }

    return SucuriScanTemplate::getSection('settings-general-reverseproxy', $params);
}

/**
 * Renders a page with information about the IP discoverer feature.
 *
 * @param bool $nonce True if the CSRF protection worked.
 * @return string Page with information about the IP discoverer.
 */
function sucuriscan_settings_general_ipdiscoverer($nonce)
{
    $params = array(
        'TopLevelDomain' => __('Unknown', SUCURISCAN_TEXTDOMAIN),
        'WebsiteHostName' => __('Unknown', SUCURISCAN_TEXTDOMAIN),
        'WebsiteHostAddress' => __('Unknown', SUCURISCAN_TEXTDOMAIN),
        'IsUsingFirewall' => __('Unknown', SUCURISCAN_TEXTDOMAIN),
        'WebsiteURL' => __('Unknown', SUCURISCAN_TEXTDOMAIN),
        'RemoteAddress' => '127.0.0.1',
        'RemoteAddressHeader' => 'INVALID',
        'AddrHeaderOptions' => '',
        /* Switch form information. */
        'DnsLookupsStatus' => __('Enabled', SUCURISCAN_TEXTDOMAIN),
        'DnsLookupsSwitchText' => __('Disable', SUCURISCAN_TEXTDOMAIN),
        'DnsLookupsSwitchValue' => 'disable',
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

            SucuriScanOption::updateOption(':dns_lookups', $action_d);
            SucuriScanEvent::reportInfoEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanInterface::info(__('DNSLookupStatus', SUCURISCAN_TEXTDOMAIN));
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

    if (SucuriScanOption::isDisabled(':dns_lookups')) {
        $params['DnsLookupsStatus'] = __('Disabled', SUCURISCAN_TEXTDOMAIN);
        $params['DnsLookupsSwitchText'] = __('Enable', SUCURISCAN_TEXTDOMAIN);
        $params['DnsLookupsSwitchValue'] = 'enable';
    }

    $proxy_info = SucuriScan::isBehindFirewall(true);
    $base_domain = SucuriScan::getDomain(true);

    $params['TopLevelDomain'] = $proxy_info['http_host'];
    $params['WebsiteHostName'] = $proxy_info['host_name'];
    $params['WebsiteHostAddress'] = $proxy_info['host_addr'];
    $params['RemoteAddressHeader'] = SucuriScan::getRemoteAddrHeader();
    $params['RemoteAddress'] = SucuriScan::getRemoteAddr();
    $params['WebsiteURL'] = SucuriScan::getDomain();
    $params['AddrHeaderOptions'] = SucuriScanTemplate::selectOptions(
        $allowed_headers, /* list is limited to a few options */
        SucuriScanOption::getOption(':addr_header')
    );
    $params['IsUsingFirewall'] = ($proxy_info['status']
    ? __('Active', SUCURISCAN_TEXTDOMAIN)
    : __('NotActive', SUCURISCAN_TEXTDOMAIN));

    if ($base_domain !== $proxy_info['http_host']) {
        $params['TopLevelDomain'] = sprintf('%s (%s)', $params['TopLevelDomain'], $base_domain);
    }

    return SucuriScanTemplate::getSection('settings-general-ipdiscoverer', $params);
}

/**
 * Renders a page with information about the auditlog stats feature.
 *
 * @param bool $nonce True if the CSRF protection worked.
 * @return string Page with information about the auditlog stats.
 */
function sucuriscan_settings_general_auditlogstats($nonce)
{
    $params = array();

    if ($nonce) {
        // Update the limit for audit logs report.
        if ($logs4report = SucuriScanRequest::post(':logs4report', '[0-9]{1,4}')) {
            $message = 'Audit log statistics limit set to <code>' . $logs4report . '</code>';

            SucuriScanOption::updateOption(':logs4report', $logs4report);
            SucuriScanEvent::reportInfoEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanInterface::info(__('LogsReportLimit', SUCURISCAN_TEXTDOMAIN));
        }
    }

    $logs4report = SucuriScanOption::getOption(':logs4report');
    $params['AuditLogStats.Limit'] = SucuriScan::escape($logs4report);

    return SucuriScanTemplate::getSection('settings-general-auditlogstats', $params);
}

/**
 * Renders a page with information about the import export feature.
 *
 * @param bool $nonce True if the CSRF protection worked.
 * @return string Page with information about the import export.
 */
function sucuriscan_settings_general_importexport($nonce)
{
    $settings = array();
    $params = array();
    $allowed = array(
        ':addr_header',
        ':api_key',
        ':api_protocol',
        ':api_service',
        ':cloudproxy_apikey',
        ':diff_utility',
        ':dns_lookups',
        ':email_subject',
        ':emails_per_hour',
        ':ignored_events',
        ':language',
        ':lastlogin_redirection',
        ':logs4report',
        ':maximum_failed_logins',
        ':notify_available_updates',
        ':notify_bruteforce_attack',
        ':notify_failed_login',
        ':notify_plugin_activated',
        ':notify_plugin_change',
        ':notify_plugin_deactivated',
        ':notify_plugin_deleted',
        ':notify_plugin_installed',
        ':notify_plugin_updated',
        ':notify_post_publication',
        ':notify_scan_checksums',
        ':notify_settings_updated',
        ':notify_success_login',
        ':notify_theme_activated',
        ':notify_theme_deleted',
        ':notify_theme_editor',
        ':notify_theme_installed',
        ':notify_theme_updated',
        ':notify_to',
        ':notify_user_registration',
        ':notify_website_updated',
        ':notify_widget_added',
        ':notify_widget_deleted',
        ':prettify_mails',
        ':revproxy',
        ':selfhosting_fpath',
        ':selfhosting_monitor',
        ':use_wpmail',
    );

    if ($nonce && SucuriScanRequest::post(':import') !== false) {
        $process = SucuriScanRequest::post(':process_form');

        if (intval($process) === 1) {
            $json = SucuriScanRequest::post(':settings');
            $json = str_replace('\&quot;', '"', $json);
            $data = @json_decode($json, true);

            if ($data) {
                $count = 0;
                $total = count($data);

                /* minimum length for option name */
                $minLength = strlen(SUCURISCAN . '_');

                foreach ($data as $option => $value) {
                    if (strlen($option) <= $minLength) {
                        continue;
                    }

                    $option_name = ':' . substr($option, $minLength);

                    /* check if the option can be imported */
                    if (!in_array($option_name, $allowed)) {
                        continue;
                    }

                    SucuriScanOption::updateOption($option_name, $value);

                    $count++;
                }

                SucuriScanInterface::info(sprintf(
                    __('ImportCount', SUCURISCAN_TEXTDOMAIN),
                    $count,
                    $total
                ));
            } else {
                SucuriScanInterface::error(__('IncorrectEncoding', SUCURISCAN_TEXTDOMAIN));
            }
        } else {
            SucuriScanInterface::error(__('ConfirmOperation', SUCURISCAN_TEXTDOMAIN));
        }
    }

    foreach ($allowed as $option) {
        $option_name = SucuriScan::varPrefix($option);
        $settings[$option_name] = SucuriScanOption::getOption($option);
    }

    $params['Export'] = @json_encode($settings);

    return SucuriScanTemplate::getSection('settings-general-importexport', $params);
}
