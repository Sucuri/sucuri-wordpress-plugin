<?php

/**
 * Code related to the auditlogs.lib.php interface.
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
 * Lists the logs collected by the API service.
 *
 * @category   Library
 * @package    Sucuri
 * @subpackage SucuriScanner
 * @author     Daniel Cid <dcid@sucuri.net>
 * @copyright  2010-2018 Sucuri Inc.
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @link       https://wordpress.org/plugins/sucuri-scanner
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

        $params['AuditLogs.Lifetime'] = SUCURISCAN_AUDITLOGS_LIFETIME;

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
     *
     * @codeCoverageIgnore - Notice that there is a test case that covers this
     * code, but since the WP-Send-JSON method uses die() to stop any further
     * output it means that XDebug cannot cover the next line, leaving a report
     * with a missing line in the coverage. Since the test case takes care of
     * the functionality of this code we will assume that it is fully covered.
     *
     * @return void
     */
    public static function ajaxAuditLogs()
    {
        if (SucuriScanRequest::post('form_action') !== 'get_audit_logs') {
            return;
        }

        $response = array();
        $response['count'] = 0;
        $response['status'] = '';
        $response['content'] = '';
        $response['queueSize'] = 0;
        $response['pagination'] = '';
        $response['selfhosting'] = false;

        /* initialize the values for the pagination */
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
            $start = microtime(true);
            $cacheTheResponse = true;
            $auditlogs = SucuriScanAPI::getAuditLogs($logsLimit);
            $errors = ob_get_contents(); /* capture errors */
            $duration = microtime(true) - $start;
            ob_end_clean();

            /* report latency in the API calls */
            if (!is_array($auditlogs)) {
                $response['status'] = __('API is not available; using local queue', 'sucuri-scanner');
            } else {
                $response['status'] = sprintf(__('API %s secs', 'sucuri-scanner'), round($duration, 4));
            }
        }

        /* explain missing API key */
        if (!SucuriScanAPI::getPluginKey()) {
            $response['status'] = __('API key is missing', 'sucuri-scanner');
        }

        /* stop everything and report errors */
        if (!empty($errors)) {
            $response['content'] .= $errors;
        }

        /* Cache the data for sometime. */
        if ($cacheTheResponse && $auditlogs && empty($errors)) {
            $cache->add('response', $auditlogs);
        }

        /* merge the logs from the queue system */
        $queuelogs = SucuriScanAPI::getAuditLogsFromQueue();

        if (is_array($queuelogs) && !empty($queuelogs)) {
            if (!$auditlogs) {
                $auditlogs = $queuelogs;
            } else {
                $auditlogs['output'] = array_merge(
                    $queuelogs['output'],
                    @$auditlogs['output']
                );
                $auditlogs['output_data'] = array_merge(
                    $queuelogs['output_data'],
                    @$auditlogs['output_data']
                );

                if (isset($auditlogs['total_entries'])) {
                    $auditlogs['total_entries'] = $auditlogs['total_entries'] + $queuelogs['total_entries'];
                } else {
                    $auditlogs['total_entries'] = $queuelogs['total_entries'];
                }
            }
        }

        if (!is_array($auditlogs)
            || !isset($auditlogs['output_data'])
            || !isset($auditlogs['total_entries'])
            || !is_array($auditlogs['output_data'])
            || !is_numeric($auditlogs['total_entries'])
        ) {
            $response['content'] = __('There are no logs.', 'sucuri-scanner');
            wp_send_json($response, 200);
            return;
        }

        $counter_i = 0;
        $previousDate = '';
        $outdata = (array) $auditlogs['output_data'];
        $todaysDate = SucuriScan::datetime(null, 'M d, Y');
        $iterator_start = ($pageNumber - 1) * $maxPerPage;
        $total_items = count($outdata);

        usort($outdata, array('SucuriScanAuditLogs', 'sortByDate'));

        for ($i = $iterator_start; $i < $total_items; $i++) {
            if ($counter_i > $maxPerPage) {
                break;
            }

            if (!isset($outdata[$i])) {
                continue;
            }

            $audit_log = (array) $outdata[$i];

            if (strpos($audit_log['message'], ";\x20password:")) {
                $idx = strpos($audit_log['message'], ";\x20password:");
                $audit_log['message'] = substr($audit_log['message'], 0, $idx);
            }

            $snippet_data = array(
                'AuditLog.Event' => $audit_log['event'],
                'AuditLog.Time' => SucuriScan::datetime($audit_log['timestamp'], 'H:i'),
                'AuditLog.Date' => SucuriScan::datetime($audit_log['timestamp'], 'M d, Y'),
                'AuditLog.Username' => $audit_log['username'],
                'AuditLog.Address' => $audit_log['remote_addr'],
                'AuditLog.Message' => $audit_log['message'],
                'AuditLog.Extra' => '',
            );

            /* determine if we need to print the date */
            if ($snippet_data['AuditLog.Date'] === $previousDate) {
                $snippet_data['AuditLog.Date'] = '';
            } elseif ($snippet_data['AuditLog.Date'] === $todaysDate) {
                $previousDate = $snippet_data['AuditLog.Date'];
                $snippet_data['AuditLog.Date'] = __('Today', 'sucuri-scanner');
            } else {
                $previousDate = $snippet_data['AuditLog.Date'];
            }

            /* decorate date if necessary */
            if (!empty($snippet_data['AuditLog.Date'])) {
                $snippet_data['AuditLog.Date'] = '<div class="sucuriscan-aud'
                . 'itlog-date">' . $snippet_data['AuditLog.Date'] . '</div>';
            }

            /* print every file_list information item in a separate table */
            if ($audit_log['file_list']) {
                $snippet_data['AuditLog.Extra'] .= '<ul class="sucuriscan-list-as-table">';

                foreach ($audit_log['file_list'] as $log_extra) {
                    $snippet_data['AuditLog.Extra'] .= '<li>' . SucuriScan::escape($log_extra) . '</li>';
                }

                $snippet_data['AuditLog.Extra'] .= '</ul>';
            }

            /* simplify the details of events with low metadata */
            if (strpos($audit_log['message'], __('status has been changed', 'sucuri-scanner'))) {
                $snippet_data['AuditLog.Extra'] = implode(",\x20", $audit_log['file_list']);
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

        $cache = new SucuriScanCache('auditqueue');
        $finfo = $cache->getDatastoreInfo();
        $events = $cache->getAll();
        $response['queueSize'] = count($events);

        wp_send_json($response, 200);
    }

    /**
     * Send the logs from the queue to the API.
     *
     * @codeCoverageIgnore - Notice that there is a test case that covers this
     * code, but since the WP-Send-JSON method uses die() to stop any further
     * output it means that XDebug cannot cover the next line, leaving a report
     * with a missing line in the coverage. Since the test case takes care of
     * the functionality of this code we will assume that it is fully covered.
     *
     * @return void
     */
    public static function ajaxAuditLogsSendLogs()
    {
        if (SucuriScanRequest::post('form_action') !== 'auditlogs_send_logs') {
            return;
        }

        /* blocking; might take a while */
        wp_send_json(SucuriScanEvent::sendLogsFromQueue(), 200);
    }

    /**
     * Sort the audit logs by date.
     *
     * Considering that the logs from the API service will be merged with the
     * logs from the local queue system to complement the information until the
     * queue is emptied, we will have to sort the entries in the list to keep
     * the dates in sync.
     *
     * @param  array $a Data associated to a single log.
     * @param  array $b Data associated to another log.
     * @return int      Comparison between the dates of both logs.
     */
    public static function sortByDate($a, $b)
    {
        if ($a['timestamp'] === $b['timestamp']) {
            return 0;
        }

        return ($a['timestamp'] > $b['timestamp']) ? -1 : 1;
    }
}
