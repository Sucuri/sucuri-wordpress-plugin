<?php

/**
 * Code related to the settings-general.php interface.
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

            sucuriscanResetAndDeactivate(); /* simulate plugin deactivation */

            SucuriScanEvent::reportCriticalEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanInterface::info('Local security logs, hardening and settings were deleted');
        } else {
            SucuriScanInterface::error('You need to confirm that you understand the risk of this operation.');
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
        // Remove API key from the local storage.
        $api_key = SucuriScanAPI::getPluginKey();
        if (SucuriScanRequest::post(':remove_api_key') !== false
            && SucuriScanAPI::setPluginKey('') !== false
        ) {
            wp_clear_scheduled_hook('sucuriscan_scheduled_scan');

            $api_key = SucuriScan::escape($api_key);
            SucuriScanEvent::reportCriticalEvent('Sucuri API key has been deleted.');
            SucuriScanEvent::notifyEvent('plugin_change', 'Sucuri API key removed');
            SucuriScanInterface::info('Sucuri API key has been deleted <code>' . $api_key . '</code>');
        }

        // Save API key after it was recovered by the administrator.
        $api_key = SucuriScanRequest::post(':manual_api_key');

        if ($api_key) {
            SucuriScanAPI::setPluginKey($api_key, true);
            SucuriScanEvent::installScheduledTask();
            SucuriScanEvent::reportInfoEvent('Sucuri API key was added manually.');
        }

        // Generate new API key from the API service.
        if (SucuriScanRequest::post(':plugin_api_key') !== false) {
            $user_id = (int) SucuriScanRequest::post(':setup_user');
            $user_obj = SucuriScan::getUserByID($user_id);

            if ($user_obj && user_can($user_obj, 'administrator')) {
                // Check consent
                if (SucuriScanRequest::post(':consent_tos') != 1 || SucuriScanRequest::post(':consent_priv') != 1) {
                    SucuriScanInterface::error('You must accept the Terms of Service and Privacy Policy in order to request an API key.');
					unset($_POST['sucuriscan_dns_lookups']);
                } else {
                    // Send request to generate new API key or display form to set manually.
                    if (SucuriScanAPI::registerSite($user_obj->user_email)) {
                        $api_registered_modal = SucuriScanTemplate::getModal(
                            'settings-apiregistered',
                            array('Title' => 'Site registered successfully')
                        );
                    } else {
                        $display_manual_key_form = true;
                    }
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
        $api_recovery_modal = SucuriScanTemplate::getModal(
            'settings-apirecovery',
            array('Title' => 'Plugin API Key Recovery')
        );
    }

    // Check whether the domain name is valid or not.
    if (!$api_key) {
        $clean_domain = SucuriScan::getTopLevelDomain();
        $domain_address = @gethostbyname($clean_domain);
        $invalid_domain = (bool) ($domain_address === $clean_domain);
    }

    $params['APIKey'] = (!$api_key ? '(not set)' : $api_key);
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
function sucuriscan_settings_general_datastorage($nonce)
{
    $params = array();
    $files = array(
        '<root>' => 'Directory used to store the plugin settings, cache and system logs',
        'auditlogs' => 'Cache to store the system logs obtained from the API service; expires after ' . SUCURISCAN_AUDITLOGS_LIFETIME . ' seconds.',
        'auditqueue' => 'Local queue to store the most recent logs before they are sent to the remote API service.',
        'blockedusers' => 'Deprecated on 1.8.12; it was used to store a list of blocked user names.', /* TODO: deprecated on 1.8.12 */
        'failedlogins' => 'Stores the data for every failed login attempt. The data is moved to "oldfailedlogins" every hour during a brute force password attack.',
        'hookdata' => 'Temporarily stores data to complement the logs during destructive operations like deleting a post, page, comment, etc.',
        'ignorescanning' => 'Stores a list of files and folders chosen by the user to be ignored by the file system scanner.',
        'integrity' => 'Stores a list of files marked as fixed by the user via the WordPress Integrity tool.',
        'lastlogins' => 'Stores the data associated to every successful user login. The data never expires; manually delete if the file is too large.',
        'oldfailedlogins' => 'Stores the data for every failed login attempt after the plugin sends a report about a brute force password attack via email.',
        'plugindata' => 'Cache to store the data associated to the installed plugins listed in the Post-Hack page. Expires after ' . SUCURISCAN_GET_PLUGINS_LIFETIME . ' seconds.',
        'settings' => 'Stores all the options used to configure the functionality and behavior of the plugin.',
        'sitecheck' => 'Cache to store the result of the malware scanner. Expires after ' . SUCURISCAN_SITECHECK_LIFETIME . ' seconds, reset at any time to force a re-scan.',
        'trustip' => 'Stores a list of IP addresses trusted by the plugin, events triggered by one of these IPs will not be reported to the remote monitoring API service.',
    );

    $params['Storage.Files'] = '';
    $params['Storage.Path'] = SucuriScan::dataStorePath();

    if ($nonce) {
        $filenames = SucuriScanRequest::post(':filename', '_array');

        if ($filenames) {
            $deleted = 0;

            foreach ($filenames as $filename) {
                $short = substr($filename, 7); /* drop directroy path */
                $short = substr($short, 0, -4); /* drop file extension */

                if (!$short || empty($short) || !array_key_exists($short, $files)) {
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

            SucuriScanInterface::info(
                sprintf(
                    '%d out of %d files has been deleted',
                    $deleted,
                    count($filenames)
                )
            );
        }
    }

    foreach ($files as $name => $desc) {
        if ($name === '<root>') {
            /* convert to folder */
            $name = '';
        }

        $fsize = 0;
        $fname = ($name ? sprintf('sucuri-%s.php', $name) : '');
        $fpath = SucuriScan::dataStorePath($fname);
        $disabled = 'disabled="disabled"';
        $iswritable = 'Not Writable';
        $exists = 'Does Not Exist';
        $labelExistence = 'danger';
        $labelWritability = 'default';

        if (file_exists($fpath)) {
            $fsize = @filesize($fpath);
            $exists = 'Exists';
            $labelExistence = 'success';
            $labelWritability = 'danger';

            if (is_writable($fpath)) {
                $disabled = ''; /* Allow file deletion */
                $iswritable = 'Writable';
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
        $params['Storage.Description'] = $desc;

        if (is_dir($fpath)) {
            $params['Storage.Filesize'] = '';
            $params['Storage.DisabledInput'] = 'disabled="disabled"';
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
    $params['SelfHosting.Status'] = 'Enabled';
    $params['SelfHosting.SwitchText'] = 'Disable';
    $params['SelfHosting.SwitchValue'] = 'disable';
    $params['SelfHosting.FpathVisibility'] = 'hidden';
    $params['SelfHosting.Fpath'] = '';

    if ($nonce) {
        // Set a file path for the self-hosted event monitor.
        $monitor_fpath = SucuriScanRequest::post(':selfhosting_fpath');

        if ($monitor_fpath !== false) {
            if (empty($monitor_fpath)) {
                $message = 'Log exporter was disabled';

                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanOption::deleteOption(':selfhosting_fpath');
                SucuriScanOption::updateOption(':selfhosting_monitor', 'disabled');
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info('The log exporter feature has been disabled');
            } elseif (strpos($monitor_fpath, $_SERVER['DOCUMENT_ROOT']) !== false) {
                SucuriScanInterface::error('File should not be publicly accessible.');
            } elseif (file_exists($monitor_fpath)) {
                SucuriScanInterface::error('File already exists and will not be overwritten.');
            } elseif (!is_writable(dirname($monitor_fpath))) {
                SucuriScanInterface::error('File parent directory is not writable.');
            } else {
                @file_put_contents($monitor_fpath, '', LOCK_EX);

                $message = 'Log exporter file path was correctly set';

                SucuriScanEvent::reportInfoEvent($message);
                SucuriScanOption::updateOption(':selfhosting_monitor', 'enabled');
                SucuriScanOption::updateOption(':selfhosting_fpath', $monitor_fpath);
                SucuriScanEvent::notifyEvent('plugin_change', $message);
                SucuriScanInterface::info('The log exporter feature has been enabled and the data file was successfully set.');
            }
        }
    }

    $monitor = SucuriScanOption::getOption(':selfhosting_monitor');
    $monitor_fpath = SucuriScanOption::getOption(':selfhosting_fpath');

    if ($monitor === 'disabled') {
        $params['SelfHosting.Status'] = 'Disabled';
        $params['SelfHosting.SwitchText'] = 'Enable';
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
        'ReverseProxyStatus' => 'Enabled',
        'ReverseProxySwitchText' => 'Disable',
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
        $params['ReverseProxyStatus'] = 'Disabled';
        $params['ReverseProxySwitchText'] = 'Enable';
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
        'TopLevelDomain' => 'unknown',
        'WebsiteHostName' => 'unknown',
        'WebsiteHostAddress' => 'unknown',
        'IsUsingFirewall' => 'unknown',
        'WebsiteURL' => 'unknown',
        'RemoteAddress' => '127.0.0.1',
        'RemoteAddressHeader' => 'INVALID',
        'AddrHeaderOptions' => '',
        /* Switch form information. */
        'DnsLookupsStatus' => 'Enabled',
        'DnsLookupsSwitchText' => 'Disable',
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
            SucuriScanInterface::info('The status of the DNS lookups for the reverse proxy detection has been changed');
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
        $params['DnsLookupsStatus'] = 'Disabled';
        $params['DnsLookupsSwitchText'] = 'Enable';
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
    $params['IsUsingFirewall'] = $proxy_info['status'] ? 'active' : 'not active';

    if ($base_domain !== $proxy_info['http_host']) {
        $params['TopLevelDomain'] = sprintf('%s (%s)', $params['TopLevelDomain'], $base_domain);
    }

    return SucuriScanTemplate::getSection('settings-general-ipdiscoverer', $params);
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
        ':api_protocol',
        ':api_service',
        ':cloudproxy_apikey',
        ':diff_utility',
        ':dns_lookups',
        ':email_subject',
        ':emails_per_hour',
        ':ignored_events',
        ':lastlogin_redirection',
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

                /* import trusted ip addresses */
                if (array_key_exists('trusted_ips', $data) && is_array($data)) {
                    $cache = new SucuriScanCache('trustip');

                    foreach ($data['trusted_ips'] as $trustedIP) {
                        $trustedIP = str_replace('\/', '/', $trustedIP);
                        $trustedIP = str_replace('/32', '', $trustedIP);

                        if (SucuriScan::isValidIP($trustedIP) || SucuriScan::isValidCIDR($trustedIP)) {
                            $ipInfo = SucuriScan::getIPInfo($trustedIP);
                            $cacheKey = md5($ipInfo['remote_addr']);
                            $ipInfo['added_at'] = time();

                            if (!$cache->exists($cacheKey)) {
                                $cache->add($cacheKey, $ipInfo);
                            }
                        }
                    }
                }

                SucuriScanInterface::info(
                    sprintf(
                        '%d out of %d option have been successfully imported',
                        $count,
                        $total
                    )
                );
            } else {
                SucuriScanInterface::error('Data is incorrectly encoded');
            }
        } else {
            SucuriScanInterface::error('You need to confirm that you understand the risk of this operation.');
        }
    }

    foreach ($allowed as $option) {
        $option_name = SucuriScan::varPrefix($option);
        $settings[$option_name] = SucuriScanOption::getOption($option);
    }

    /* include the trusted IP address list */
    $settings['trusted_ips'] = array();
    $cache = new SucuriScanCache('trustip');
    $trusted = $cache->getAll();
    foreach ($trusted as $trustedIP) {
        $settings['trusted_ips'][] = $trustedIP->cidr_format;
    }

    $params['Export'] = @json_encode($settings);

    return SucuriScanTemplate::getSection('settings-general-importexport', $params);
}

/**
 * Renders a page with the option to configure the timezone.
 *
 * @param bool $nonce True if the CSRF protection worked.
 * @return string Page to configure the timezone.
 */
function sucuriscan_settings_general_timezone($nonce)
{
    $params = array();
    $current = time();
    $options = array();
    $offsets = array(
        -12.0, -11.5, -11.0, -10.5, -10.0, -9.50, -9.00, -8.50, -8.00, -7.50,
        -7.00, -6.50, -6.00, -5.50, -5.00, -4.50, -4.00, -3.50, -3.00, -2.50,
        -2.00, -1.50, -1.00, -0.50, +0.00, +0.50, +1.00, +1.50, +2.00, +2.50,
        +3.00, +3.50, +4.00, +4.50, +5.00, +5.50, +5.75, +6.00, +6.50, +7.00,
        +7.50, +8.00, +8.50, +8.75, +9.00, +9.50, 10.00, 10.50, 11.00, 11.50,
        12.00, 12.75, 13.00, 13.75, 14.00
    );

    foreach ($offsets as $hour) {
        $sign = ($hour < 0) ? '-' : '+';
        $fill = (abs($hour) < 10) ? '0' : '';
        $keyname = sprintf('UTC%s%s%.2f', $sign, $fill, abs($hour));
        $label = date('d M, Y H:i:s', $current + ($hour * 3600));
        $options[$keyname] = $label;
    }

    if ($nonce) {
        $pattern = 'UTC[\-\+][0-9]{2}\.[0-9]{2}';
        $timezone = SucuriScanRequest::post(':timezone', $pattern);

        if ($timezone) {
            $message = 'Timezone override will use ' . $timezone;

            SucuriScanOption::updateOption(':timezone', $timezone);
            SucuriScanEvent::reportInfoEvent($message);
            SucuriScanEvent::notifyEvent('plugin_change', $message);
            SucuriScanInterface::info('The timezone for the date and time in the audit logs has been changed');
        }
    }

    $val = SucuriScanOption::getOption(':timezone');
    $params['Timezone.Dropdown'] = SucuriScanTemplate::selectOptions($options, $val);
    $params['Timezone.Example'] = SucuriScan::datetime();

    return SucuriScanTemplate::getSection('settings-general-timezone', $params);
}
