<?php

/**
 * Code related to the auditlogs.lib.php interface.
 *
 * @package Sucuri Security
 * @subpackage auditlogs.lib.php
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
 * Lists the logs collected by the API service.
 */
class SucuriScanAuditLogs
{
    /**
     * Print a HTML code with the content of the logs audited by the remote Sucuri
     * API service, this page is part of the monitoring tool.
     *
     * @return string HTML with the audit logs page.
     */
    public static function pageAuditLogs()
    {
        $params = array();

        return SucuriScanTemplate::getSection('auditlogs', $params);
    }

    /**
     * Gets the security logs from the API service.
     *
     * To reduce the amount of queries to the API this method will cache the logs
     * for a short period of time enough to give the service a rest. Once the
     * cache expires the method will communicate with the API once again to get
     * a fresh copy of the new logs. The cache is skipped when the user clicks
     * around the pagination.
     *
     * Additionally, if the API key has not been added but the website owner has
     * enabled the security log exporter, the method will retrieve the logs from
     * the local server with the limitation that only the latest entries in the
     * file will be processed.
     */
    public static function ajaxAuditLogs()
    {
        if (SucuriScanRequest::post('form_action') !== 'get_audit_logs') {
            return;
        }

        $response = array();
        $response['count'] = 0;
        $response['content'] = '';
        $response['selfhosting'] = false;

        /* Initialize the values for the pagination. */
        $maxPerPage = SUCURISCAN_AUDITLOGS_PER_PAGE;
        $pageNumber = SucuriScanTemplate::pageNumber();
        $logsLimit = ($pageNumber * $maxPerPage);

        /* Get data from the cache if possible. */
        $errors = ''; /* no errors so far */
        $cache = new SucuriScanCache('auditlogs');
        $auditlogs = $cache->get('response', SUCURISCAN_AUDITLOGS_LIFETIME, 'array');
        $cacheTheResponse = false; /* cache if the data comes from the API */

        /* API call if cache is invalid. */
        if (!$auditlogs || $pageNumber !== 1) {
            ob_start();
            $cacheTheResponse = true;
            $auditlogs = SucuriScanAPI::getAuditLogs($logsLimit);
            $errors = ob_get_contents();
            ob_end_clean();
        }

        /* Stop everything and report errors. */
        if (!empty($errors)) {
            header('Content-Type: text/html; charset=UTF-8');
            print($errors);
            exit(0);
        }

        /* Cache the data for sometime. */
        if ($cacheTheResponse && $auditlogs && empty($errors)) {
            $cache->add('response', $auditlogs);
        }

        /* Fallback; get the logs from the local server */
        if (!$auditlogs && !SucuriScanOption::getOption(':api_key')) {
            $auditlogs = SucuriScanAPI::getSelfHostingLogs(SUCURISCAN_AUDITLOGS_PER_PAGE);
            $response['selfhosting'] = (bool) ($auditlogs !== false);
        }

        if ($auditlogs) {
            $counter_i = 0;
            $previousDate = '';
            $todaysDate = date('M d, Y');
            $total_items = count($auditlogs['output_data']);
            $iterator_start = ($pageNumber - 1) * $maxPerPage;

            for ($i = $iterator_start; $i < $total_items; $i++) {
                if ($counter_i > $maxPerPage) {
                    break;
                }

                if (!isset($auditlogs['output_data'][$i])) {
                    continue;
                }

                $audit_log = $auditlogs['output_data'][$i];

                $snippet_data = array(
                    'AuditLog.Event' => $audit_log['event'],
                    'AuditLog.Time' => date('H:i', $audit_log['timestamp']),
                    'AuditLog.Date' => date('M d, Y', $audit_log['timestamp']),
                    'AuditLog.Username' => $audit_log['username'],
                    'AuditLog.Address' => $audit_log['remote_addr'],
                    'AuditLog.Message' => $audit_log['message'],
                    'AuditLog.Extra' => '',
                );

                // Determine if we need to print the date.
                if ($snippet_data['AuditLog.Date'] === $previousDate) {
                    $snippet_data['AuditLog.Date'] = '';
                } elseif ($snippet_data['AuditLog.Date'] === $todaysDate) {
                    $previousDate = $snippet_data['AuditLog.Date'];
                    $snippet_data['AuditLog.Date'] = 'Today';
                } else {
                    $previousDate = $snippet_data['AuditLog.Date'];
                }

                // Decorate date if necessary.
                if (!empty($snippet_data['AuditLog.Date'])) {
                    $snippet_data['AuditLog.Date'] =
                    '<div class="sucuriscan-auditlog-date">'
                    . $snippet_data['AuditLog.Date']
                    . '</div>';
                }

                // Print every file_list information item in a separate table.
                if ($audit_log['file_list']) {
                    $css_scrollable = $audit_log['file_list_count'] > 10 ? 'sucuriscan-list-as-table-scrollable' : '';
                    $snippet_data['AuditLog.Extra'] .= '<ul class="sucuriscan-list-as-table ' . $css_scrollable . '">';

                    foreach ($audit_log['file_list'] as $log_extra) {
                        $snippet_data['AuditLog.Extra'] .= '<li>' . SucuriScan::escape($log_extra) . '</li>';
                    }

                    $snippet_data['AuditLog.Extra'] .= '</ul>';
                }

                $response['content'] .= SucuriScanTemplate::getSnippet('auditlogs', $snippet_data);
                $counter_i += 1;
            }

            $response['count'] = $counter_i;

            if ($total_items > 1) {
                $maxpages = ceil($auditlogs['total_entries'] / $maxPerPage);

                if ($maxpages > SUCURISCAN_MAX_PAGINATION_BUTTONS) {
                    $maxpages = SUCURISCAN_MAX_PAGINATION_BUTTONS;
                }

                if ($maxpages > 1) {
                    $response['pagination'] = SucuriScanTemplate::pagination(
                        SucuriScanTemplate::getUrl(),
                        ($maxPerPage * $maxpages),
                        $maxPerPage
                    );
                }
            }
        } else {
            $response['content'] = 'There are no logs.';
        }

        header('Content-Type: application/json');
        print(json_encode($response));
        exit(0);
    }

    /**
     * Draws the statistic charts with data from the security logs.
     *
     * The percentage of successful and failed logins. The percentage of events
     * distributed by severity. The amount of events triggered by each user. And
     * the amount of events triggered from each IP address. All these statistics
     * will be rendered in the page.
     *
     * @return string HTML with the stats about the security logs.
     */
    public static function pageAuditLogsReport()
    {
        $params = array();
        $logs4report = SucuriScanOption::getOption(':logs4report');

        $params['AuditReport.Logs4Report'] = $logs4report;

        return SucuriScanTemplate::getSection('auditlogs-report', $params);
    }

    /**
     * Analyzes the latest security logs and generates statistics.
     *
     * A JSON-encoded data structure will be returned after the plugin reads,
     * processes and extracts relevant information from the latest security logs.
     * By default the plugin will use the latest 500 logs but the website owner
     * can increase or decrease this value to reduce or extend the statistics.
     */
    public static function ajaxAuditLogsReport()
    {
        if (SucuriScanRequest::post('form_action') !== 'get_audit_logs_report') {
            return;
        }

        $response = array();
        $logs4report = SucuriScanOption::getOption(':logs4report');
        $report = SucuriScanAPI::getAuditReport($logs4report);

        $response['status'] = false;
        $response['message'] = 'Not enough logs';
        $response['eventsPerUserSeries'] = array();
        $response['eventsPerUserCategories'] = array();
        $response['eventsPerIPAddressSeries'] = array();
        $response['eventsPerIPAddressCategories'] = array();
        $response['eventsPerTypePoints'] = array();
        $response['eventsPerTypeColors'] = array();
        $response['eventsPerLogin'] = array();

        if ($report) {
            $response['status'] = true;
            $response['message'] = '';
            $response['eventsPerTypeColors'] = $report['event_colors'];

            /* Generate report chart data for the events per type */
            foreach ($report['events_per_type'] as $event => $times) {
                $response['eventsPerTypePoints'][] = array(
                    ucwords($event . "\x20events"),
                    $times /* amount of events */
                );
            }

            /* Generate report chart data for the events per login */
            foreach ($report['events_per_login'] as $event => $times) {
                $response['eventsPerLogin'][] = array(
                    ucwords($event . "\x20logins"),
                    $times /* number of logins */
                );
            }

            /* Generate report chart data for the events per user */
            $users = array_values($report['events_per_user']);
            $response['eventsPerUserSeries'] = array_merge(array('data'), $users);
            $response['eventsPerUserCategories'] = array_keys($report['events_per_user']);

            /* Generate report chart data for the events per remote address */
            $ips = array_values($report['events_per_ipaddress']);
            $response['eventsPerIPAddressSeries'] = array_merge(array('data'), $ips);
            $response['eventsPerIPAddressCategories'] = array_keys($report['events_per_ipaddress']);
        }

        header('Content-Type: application/json');
        print(json_encode($response));
        exit(0);
    }
}
