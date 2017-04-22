<?php

/**
 * Code related to the pagehandler.php interface.
 *
 * @package Sucuri Security
 * @subpackage pagehandler.php
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
 * Renders the content of the plugin's dashboard page.
 */
function sucuriscan_page()
{
    $params = array();

    SucuriScanInterface::checkPageVisibility();

    if (!SucuriScanFileInfo::isSplAvailable()) {
        /* display a warning when system dependencies are not met */
        SucuriScanInterface::error('The plugin requires PHP 5 >= 5.3.0 âM-^@M-^T OR âM-^@M-^T PHP 7');
    }

    $params['Integrity'] = SucuriScanIntegrity::pageIntegrity();
    $params['SiteCheck.Details'] = SucuriScanSiteCheck::details();
    $params['SiteCheck.Malware'] = SucuriScanSiteCheck::malware();
    $params['SiteCheck.Blacklist'] = SucuriScanSiteCheck::blacklist();
    $params['SiteCheck.Recommendations'] = SucuriScanSiteCheck::recommendations();
    $params['SiteCheck.iFramesTitle'] = SucuriScanSiteCheck::iFramesTitle();
    $params['SiteCheck.LinksTitle'] = SucuriScanSiteCheck::linksTitle();
    $params['SiteCheck.ScriptsTitle'] = SucuriScanSiteCheck::scriptsTitle();
    $params['SiteCheck.iFramesContent'] = SucuriScanSiteCheck::iFramesContent();
    $params['SiteCheck.LinksContent'] = SucuriScanSiteCheck::linksContent();
    $params['SiteCheck.ScriptsContent'] = SucuriScanSiteCheck::scriptsContent();
    $params['AuditLogsReport'] = SucuriScanAuditLogs::pageAuditLogsReport();
    $params['AuditLogs'] = SucuriScanAuditLogs::pageAuditLogs();

    echo SucuriScanTemplate::getTemplate('dashboard', $params);
}

/**
 * Renders the content of the plugin's firewall page.
 */
function sucuriscan_firewall_page()
{
    SucuriScanInterface::checkPageVisibility();

    // Process all form submissions.
    $nonce = SucuriScanInterface::checkNonce();
    sucuriscan_firewall_form_submissions($nonce);

    // Get the dynamic values for the template variables.
    $api_key = SucuriScanAPI::getFirewallKey();

    // Page pseudo-variables initialization.
    $params = array(
        'Firewall.Settings' => sucuriscan_firewall_settings($api_key),
        'Firewall.AuditLogs' => sucuriscan_firewall_auditlogs(),
        'Firewall.ClearCache' => sucuriscan_firewall_clearcache($nonce),
    );

    echo SucuriScanTemplate::getTemplate('firewall', $params);
}

/**
 * Renders the content of the plugin's last logins page.
 */
function sucuriscan_lastlogins_page()
{
    SucuriScanInterface::checkPageVisibility();

    // Reset the file with the last-logins logs.
    if (SucuriScanInterface::checkNonce()
        && SucuriScanRequest::post(':reset_lastlogins') !== false
    ) {
        $file_path = sucuriscan_lastlogins_datastore_filepath();

        if (@unlink($file_path)) {
            sucuriscan_lastlogins_datastore_exists();
            SucuriScanInterface::info('Last-Logins logs were reset successfully.');
        } else {
            SucuriScanInterface::error('Could not reset the last-logins logs.');
        }
    }

    // Page pseudo-variables initialization.
    $params = array(
        'LastLogins.AllUsers' => sucuriscan_lastlogins_all(),
        'LastLogins.Admins' => sucuriscan_lastlogins_admins(),
        'LoggedInUsers' => sucuriscan_loggedin_users_panel(),
        'FailedLogins' => sucuriscan_failed_logins_panel(),
        'BlockedUsers' => SucuriScanBlockedUsers::page(),
    );

    echo SucuriScanTemplate::getTemplate('lastlogins', $params);
}

/**
 * Renders the content of the plugin's settings page.
 */
function sucuriscan_settings_page()
{
    SucuriScanInterface::checkPageVisibility();

    $params = array();
    $nonce = SucuriScanInterface::checkNonce();

    // Keep the reset options panel and form submission processor before anything else.
    $params['Settings.General.ResetOptions'] = sucuriscan_settings_general_resetoptions($nonce);

    /* settings - general */
    $params['Settings.General.ApiKey'] = sucuriscan_settings_general_apikey($nonce);
    $params['Settings.General.DataStorage'] = sucuriscan_settings_general_datastorage();
    $params['Settings.General.SelfHosting'] = sucuriscan_settings_general_selfhosting($nonce);
    $params['Settings.General.Cronjobs'] = sucuriscan_settings_general_cronjobs();
    $params['Settings.General.ReverseProxy'] = sucuriscan_settings_general_reverseproxy($nonce);
    $params['Settings.General.IPDiscoverer'] = sucuriscan_settings_general_ipdiscoverer($nonce);
    $params['Settings.General.CommentMonitor'] = sucuriscan_settings_general_commentmonitor($nonce);
    $params['Settings.General.AuditLogStats'] = sucuriscan_settings_general_auditlogstats($nonce);
    $params['Settings.General.ImportExport'] = sucuriscan_settings_general_importexport($nonce);

    /* settings - scanner */
    $params['Settings.Scanner.Options'] = sucuriscan_settings_scanner_options();
    $params['Settings.Scanner.IntegrityDiffUtility'] = SucuriScanSettingsIntegrity::diffUtility($nonce);
    $params['Settings.Scanner.IntegrityLanguage'] = SucuriScanSettingsIntegrity::language($nonce);
    $params['Settings.Scanner.IntegrityCache'] = SucuriScanSettingsIntegrity::cache($nonce);
    $params['Settings.Scanner.IgnoreFolders'] = sucuriscan_settings_scanner_ignore_folders($nonce);

    /* settings - hardening */
    $params['Settings.Hardening.Firewall'] = SucuriScanHardeningPage::firewall();
    $params['Settings.Hardening.WPVersion'] = SucuriScanHardeningPage::wpversion();
    $params['Settings.Hardening.PHPVersion'] = SucuriScanHardeningPage::phpversion();
    $params['Settings.Hardening.RemoveGenerator'] = SucuriScanHardeningPage::wpgenerator();
    $params['Settings.Hardening.NginxPHPFPM'] = SucuriScanHardeningPage::nginxphp();
    $params['Settings.Hardening.WPUploads'] = SucuriScanHardeningPage::wpuploads();
    $params['Settings.Hardening.WPContent'] = SucuriScanHardeningPage::wpcontent();
    $params['Settings.Hardening.WPIncludes'] = SucuriScanHardeningPage::wpincludes();
    $params['Settings.Hardening.Readme'] = SucuriScanHardeningPage::readme();
    $params['Settings.Hardening.AdminUser'] = SucuriScanHardeningPage::adminuser();
    $params['Settings.Hardening.FileEditor'] = SucuriScanHardeningPage::fileeditor();
    $params['Settings.Hardening.WhitelistPHPFiles'] = SucuriScanHardeningPage::whitelistPHPFiles();

    /* settings - posthack */
    $params['Settings.Posthack.SecurityKeys'] = SucuriScanPosthackPage::securityKeys();
    $params['Settings.Posthack.ResetPassword'] = SucuriScanPosthackPage::resetPassword();
    $params['Settings.Posthack.ResetPlugins'] = SucuriScanPosthackPage::resetPlugins();
    $params['Settings.Posthack.AvailableUpdates'] = SucuriScanPosthackPage::availableUpdates();

    /* settings - alerts */
    $params['Settings.Alerts.Recipients'] = sucuriscan_settings_alerts_recipients($nonce);
    $params['Settings.Alerts.TrustedIPs'] = sucuriscan_settings_alerts_trustedips();
    $params['Settings.Alerts.Subject'] = sucuriscan_settings_alerts_subject($nonce);
    $params['Settings.Alerts.PerHour'] = sucuriscan_settings_alerts_perhour($nonce);
    $params['Settings.Alerts.BruteForce'] = sucuriscan_settings_alerts_bruteforce($nonce);
    $params['Settings.Alerts.Events'] = sucuriscan_settings_alerts_events($nonce);
    $params['Settings.Alerts.IgnorePosts'] = sucuriscan_settings_alerts_ignore_posts();

    /* settings - api service */
    $params['Settings.APIService.Status'] = sucuriscan_settings_apiservice_status($nonce);
    $params['Settings.APIService.Proxy'] = sucuriscan_settings_apiservice_proxy();
    $params['Settings.APIService.Timeout'] = sucuriscan_settings_apiservice_timeout($nonce);

    /* settings - website info */
    $params['Settings.Webinfo.Details'] = sucuriscan_settings_webinfo_details();
    $params['Settings.Webinfo.WPConfig'] = sucuriscan_settings_webinfo_wpconfig();
    $params['Settings.Webinfo.HTAccess'] = sucuriscan_settings_webinfo_htaccess();

    echo SucuriScanTemplate::getTemplate('settings', $params);
}

/**
 * Handles all the AJAX plugin's requests.
 */
function sucuriscan_ajax()
{
    SucuriScanInterface::checkPageVisibility();

    if (SucuriScanInterface::checkNonce()) {
        SucuriScanIntegrity::ajaxIntegrity();
        SucuriScanIntegrity::ajaxIntegrityDiffUtility();

        SucuriScanAuditLogs::ajaxAuditLogs();
        SucuriScanAuditLogs::ajaxAuditLogsReport();

        sucuriscan_firewall_auditlogs_ajax();

        sucuriscan_settings_ignorescan_ajax();

        SucuriScanPosthackPage::getPluginsAjax();
        SucuriScanPosthackPage::resetPluginAjax();
        SucuriScanPosthackPage::resetPasswordAjax();
        SucuriScanPosthackPage::availableUpdatesAjax();
    }

    wp_die();
}
