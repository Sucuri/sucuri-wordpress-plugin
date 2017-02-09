<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * WordPress core integrity page.
 *
 * It checks whether the WordPress core files are the original ones, and the state
 * of the themes and plugins reporting the availability of updates. It also checks
 * the user accounts under the administrator group.
 *
 * @return void
 */
function sucuriscan_page()
{
    SucuriScanInterface::checkPageVisibility();

    // Process all form submissions.
    sucuriscan_integrity_form_submissions();

    $params = array(
        'WordpressVersion' => sucuriscan_wordpress_outdated(),
        'CoreFiles' => sucuriscan_core_files(),
        'AuditReports' => sucuriscan_auditreport(),
        'AuditLogs' => sucuriscan_auditlogs(),
    );

    echo SucuriScanTemplate::getTemplate('integrity', $params);
}

/**
 * Handle an Ajax request for this specific page.
 *
 * @return mixed.
 */
function sucuriscan_ajax()
{
    SucuriScanInterface::checkPageVisibility();

    if (SucuriScanInterface::checkNonce()) {
        sucuriscan_core_files_ajax();
        sucuriscan_audit_logs_ajax();
    }

    wp_die();
}
