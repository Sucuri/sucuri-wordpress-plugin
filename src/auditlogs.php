<?php

/**
 * Print a HTML code with the content of the logs audited by the remote Sucuri
 * API service, this page is part of the monitoring tool.
 *
 * @return void
 */
function sucuriscan_auditlogs()
{
    $params = array();
    $params['PageTitle'] = 'Audit Logs';

    if (SucuriScanOption::get_option(':api_key')) {
        return SucuriScanTemplate::getSection('integrity-auditlogs', $params);
    }

    return '' /* Empty string */;
}

function sucuriscan_audit_logs_ajax()
{
    if (SucuriScanRequest::post('form_action') == 'get_audit_logs') {
        $response = array();
        $response['count'] = 0;
        $response['content'] = '';
        $response['enable_report'] = false;

        // Initialize the values for the pagination.
        $max_per_page = SUCURISCAN_AUDITLOGS_PER_PAGE;
        $page_number = SucuriScanTemplate::pageNumber();
        $logs_limit = ($page_number * $max_per_page);

        ob_start();
        $audit_logs = SucuriScanAPI::getLogs($logs_limit);
        $errors = ob_get_contents();
        ob_end_clean();

        if (!empty($errors)) {
            header('Content-Type: text/html; charset=UTF-8');
            print($errors);
            exit(0);
        }

        if ($audit_logs) {
            $counter_i = 0;
            $total_items = count($audit_logs['output_data']);
            $iterator_start = ($page_number - 1) * $max_per_page;

            if (array_key_exists('total_entries', $audit_logs)
                && $audit_logs['total_entries'] >= $max_per_page
                && SucuriScanOption::is_disabled(':audit_report')
            ) {
                $response['enable_report'] = true;
            }

            for ($i = $iterator_start; $i < $total_items; $i++) {
                if ($counter_i > $max_per_page) {
                    break;
                }

                if (isset($audit_logs['output_data'][ $i ])) {
                    $audit_log = $audit_logs['output_data'][ $i ];

                    $css_class = ($counter_i % 2 === 0) ? '' : 'alternate';
                    $snippet_data = array(
                        'AuditLog.CssClass' => $css_class,
                        'AuditLog.Event' => $audit_log['event'],
                        'AuditLog.EventTitle' => ucfirst($audit_log['event']),
                        'AuditLog.Timestamp' => $audit_log['timestamp'],
                        'AuditLog.DateTime' => SucuriScan::datetime($audit_log['timestamp']),
                        'AuditLog.Account' => $audit_log['account'],
                        'AuditLog.Username' => $audit_log['username'],
                        'AuditLog.RemoteAddress' => $audit_log['remote_addr'],
                        'AuditLog.Message' => $audit_log['message'],
                        'AuditLog.Extra' => '',
                    );

                    // Print every file_list information item in a separate table.
                    if ($audit_log['file_list']) {
                        $css_scrollable = $audit_log['file_list_count'] > 10 ? 'sucuriscan-list-as-table-scrollable' : '';
                        $snippet_data['AuditLog.Extra'] .= '<ul class="sucuriscan-list-as-table ' . $css_scrollable . '">';

                        foreach ($audit_log['file_list'] as $log_extra) {
                            $snippet_data['AuditLog.Extra'] .= '<li>' . SucuriScan::escape($log_extra) . '</li>';
                        }

                        $snippet_data['AuditLog.Extra'] .= '</ul>';
                    }

                    $response['content'] .= SucuriScanTemplate::getSnippet('integrity-auditlogs', $snippet_data);
                    $counter_i += 1;
                }
            }

            $response['count'] = $counter_i;

            if ($total_items > 1) {
                $max_pages = ceil($audit_logs['total_entries'] / $max_per_page);

                if ($max_pages > SUCURISCAN_MAX_PAGINATION_BUTTONS) {
                    $max_pages = SUCURISCAN_MAX_PAGINATION_BUTTONS;
                }

                if ($max_pages > 1) {
                    $response['pagination'] = SucuriScanTemplate::pagination(
                        SucuriScanTemplate::getUrl(),
                        ($max_per_page * $max_pages),
                        $max_per_page
                    );
                }
            }
        }

        header('Content-Type: application/json');
        print(json_encode($response));
        exit(0);
    }
}

/**
 * Print a HTML code with the content of the logs audited by the remote Sucuri
 * API service, this page is part of the monitoring tool.
 *
 * @return void
 */
function sucuriscan_auditreport()
{
    $audit_report = false;
    $logs4report = SucuriScanOption::get_option(':logs4report');

    if (SucuriScanOption::is_enabled(':audit_report')) {
        $audit_report = SucuriScanAPI::getAuditReport($logs4report);
    }

    $params = array(
        'PageTitle' => 'Audit Reports',
        'AuditReport.EventColors' => '',
        'AuditReport.EventsPerType' => '',
        'AuditReport.EventsPerLogin' => '',
        'AuditReport.EventsPerUserCategories' => '',
        'AuditReport.EventsPerUserSeries' => '',
        'AuditReport.EventsPerIPAddressCategories' => '',
        'AuditReport.EventsPerIPAddressSeries' => '',
        'AuditReport.Logs4Report' => $logs4report,
    );

    if ($audit_report) {
        $params['AuditReport.EventColors'] = @implode(',', $audit_report['event_colors']);

        // Generate report chart data for the events per type.
        foreach ($audit_report['events_per_type'] as $event => $times) {
            $params['AuditReport.EventsPerType'] .= sprintf(
                "[ '%s', %d ],\n",
                ucwords($event . "\x20events"),
                $times
            );
        }

        // Generate report chart data for the events per login.
        foreach ($audit_report['events_per_login'] as $event => $times) {
            $params['AuditReport.EventsPerLogin'] .= sprintf(
                "[ '%s', %d ],\n",
                ucwords($event . "\x20logins"),
                $times
            );
        }

        // Generate report chart data for the events per user.
        foreach ($audit_report['events_per_user'] as $event => $times) {
            $params['AuditReport.EventsPerUserCategories'] .= sprintf('"%s",', $event);
            $params['AuditReport.EventsPerUserSeries'] .= sprintf('%d,', $times);
        }

        // Generate report chart data for the events per remote address.
        foreach ($audit_report['events_per_ipaddress'] as $event => $times) {
            $params['AuditReport.EventsPerIPAddressCategories'] .= sprintf('"%s",', $event);
            $params['AuditReport.EventsPerIPAddressSeries'] .= sprintf('%d,', $times);
        }

        return SucuriScanTemplate::getSection('integrity-auditreport', $params);
    }

    return '';
}
