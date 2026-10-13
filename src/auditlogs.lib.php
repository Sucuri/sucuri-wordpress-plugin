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

        /**
         * The export is a plain link, so the nonce travels in the href. The
         * template renders this through the escaped "%%...%%" tag, which turns
         * the query separators into "&amp;" as an HTML attribute requires.
         *
         * admin_url() on purpose, rather than the SucuriScan::adminURL() helper
         * the rest of the plugin uses: that helper switches to
         * network_admin_url() on multisite, and WordPress ships no
         * wp-admin/network/admin-post.php for the link to point at.
         */
        $params['AuditLogs.DownloadURL'] = add_query_arg(
            array(
                'action' => 'sucuriscan_download_audit_logs',
                'sucuriscan_page_nonce' => wp_create_nonce('sucuriscan_page_nonce'),
            ),
            admin_url('admin-post.php')
        );

        return SucuriScanTemplate::getSection('auditlogs', $params);
    }


    /**
     * Generates the HTML snippet for the audit log filters.
     *
     * This method constructs the HTML code for the audit log filters, including
     * select dropdowns for various filter options and date input fields for custom date ranges.
     *
     * @param array $frontend_filter The currently applied filters from the frontend.
     *
     * @return string The HTML code containing the filters.
     */
    private static function getFiltersSnippet($frontend_filter)
    {
        $filters_snippet = '';

        $filters = SucuriScanAPI::getFilters();

        foreach ($filters as $filter => $options) {
            $filter_options = '';

            if ($filter === 'startDate' || $filter === 'endDate') {
                $date = isset($frontend_filter[$filter]) ? $frontend_filter[$filter] : '';
                $label = ($filter === 'startDate')
                    ? __('Start Date', 'sucuri-scanner')
                    : __('End Date', 'sucuri-scanner');

                $filters_snippet .= '<input type="date" id="' . esc_attr($filter) . '" name="' . esc_attr($filter . 'Filter') . '" value="' . esc_attr($date) . '" aria-label="' . esc_attr($label) . '" title="' . esc_attr($label) . '">';
                continue;
            }

            foreach ($options as $value => $option) {
                $label = $option['label'];

                $selected = (isset($frontend_filter[$filter]) && $frontend_filter[$filter] === $value) ? ' selected' : '';

                $filter_options .= '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
            }

            $option_data = array(
                'AuditLog.FilterName' => esc_attr($filter),
                'AuditLog.FilterLabel' => esc_attr(ucwords($filter)),
                'AuditLog.FilterOptions' => $filter_options,
            );

            $filters_snippet .= SucuriScanTemplate::getSnippet('auditlogs-filter', $option_data);
        }

        $filters_data = array(
            'AuditLog.Filters' => $filters_snippet,
            'AuditLog.Search' => isset($frontend_filter['search']) ? esc_attr($frontend_filter['search']) : '',
        );

        return SucuriScanTemplate::getSnippet('auditlogs-filters', $filters_data);
    }

    /**
     * Get and validate filters supplied by the audit-log interface.
     *
     * @return array Valid audit-log filters.
     */
    private static function getRequestFilters()
    {
        $filters = array();
        $available = SucuriScanAPI::getFilters();

        foreach (array('time', 'posts', 'logins', 'users', 'plugins', 'themes', 'files', 'events') as $key) {
            $value = SucuriScanRequest::get($key);

            if ($value
                && strpos($value, 'all ') !== 0
                && isset($available[$key][$value])
            ) {
                $filters[$key] = $value;
            }
        }

        if (isset($filters['time']) && $filters['time'] === 'custom') {
            $start_date = SucuriScanRequest::get('startDate', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
            $end_date = SucuriScanRequest::get('endDate', '[0-9]{4}-[0-9]{2}-[0-9]{2}');

            if ($start_date
                && $end_date
                && strtotime($start_date) !== false
                && strtotime($end_date) !== false
                && strtotime($start_date) <= strtotime($end_date)
            ) {
                $filters['startDate'] = $start_date;
                $filters['endDate'] = $end_date;
            } else {
                unset($filters['time']);
            }
        }

        $search = SucuriScanRequest::get('search');

        if ($search) {
            $search = trim(html_entity_decode($search, ENT_QUOTES, 'UTF-8'));

            if ($search !== '') {
                $filters['search'] = substr($search, 0, 200);
            }
        }

        return $filters;
    }

    /**
     * Retrieve the latest remote logs and local queue, then apply filters once.
     *
     * @param array $filters Filters to apply.
     * @return array Audit-log result and request metadata.
     */
    private static function getAuditLogData($filters)
    {
        $result = array(
            'auditlogs' => false,
            'errors' => '',
            'limited' => false,
            'queueSize' => 0,
            'status' => '',
        );
        $logs_limit = SUCURISCAN_AUDITLOGS_PER_PAGE * SUCURISCAN_MAX_PAGINATION_BUTTONS;
        $cache = new SucuriScanCache('auditlogs');
        $auditlogs = $cache->get('response_full', SUCURISCAN_AUDITLOGS_LIFETIME, 'array');

        if (!$auditlogs) {
            ob_start();
            $start = microtime(true);
            $auditlogs = SucuriScanAPI::getAuditLogs($logs_limit);
            $result['errors'] = ob_get_contents();
            $duration = microtime(true) - $start;
            ob_end_clean();

            if (!is_array($auditlogs)) {
                $result['status'] = __('API is not available; using local queue', 'sucuri-scanner');
            } else {
                /* translators: %s: number of seconds */
                $result['status'] = sprintf(__('API %s secs', 'sucuri-scanner'), round($duration, 4));
            }

            if ($auditlogs && empty($result['errors'])) {
                $cache->add('response_full', $auditlogs);
            }
        }

        if (is_array($auditlogs)
            && isset($auditlogs['total_entries'], $auditlogs['output'])
            && $auditlogs['total_entries'] > count((array) $auditlogs['output'])
        ) {
            $result['limited'] = true;
        }

        $queuelogs = SucuriScanAPI::getAuditLogsFromQueue();

        if (is_array($queuelogs) && !empty($queuelogs['output'])) {
            $result['queueSize'] = (int) $queuelogs['total_entries'];

            if (!$auditlogs || empty($auditlogs['output'])) {
                $auditlogs = $queuelogs;
            } else {
                $auditlogs['output'] = array_merge(
                    $queuelogs['output'],
                    (array) $auditlogs['output']
                );
            }
        }

        if (is_array($auditlogs)) {
            $auditlogs = SucuriScanAPI::filterAuditLogs($auditlogs, $filters);
        }

        $result['auditlogs'] = $auditlogs;

        return $result;
    }

    /**
     * Gets the security logs from the API service.
     *
     * To reduce the amount of queries to the API this method will cache the logs
     * for a short period of time enough to give the service a rest. Once the
     * cache expires the method will communicate with the API once again to get
     * a fresh copy of the new logs. Filters and pagination are applied to the
     * same cached raw response.
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
        $response['filters'] = '';
        $response['filtersStatus'] = '';
        $response['auditlogs'] = 'Loading...';

        /* initialize the values for the pagination */
        $maxPerPage = SUCURISCAN_AUDITLOGS_PER_PAGE;
        $pageNumber = SucuriScanTemplate::pageNumber();
        $filters = self::getRequestFilters();

        if (!empty($filters)) {
            $response['filtersStatus'] = 'active';
        }

        $response['filters'] = self::getFiltersSnippet($filters);

        $data = self::getAuditLogData($filters);
        $auditlogs = $data['auditlogs'];
        $response['content'] .= $data['errors'];
        $response['limited'] = $data['limited'];
        $response['queueSize'] = $data['queueSize'];
        $response['status'] = $data['status'];

        if (!is_array($auditlogs)
            || !isset($auditlogs['output_data'])
            || !isset($auditlogs['total_entries'])
            || !is_array($auditlogs['output_data'])
            || !is_numeric($auditlogs['total_entries'])
            || empty($auditlogs['output_data'])
        ) {
            $response['content'] = __('There are no logs.', 'sucuri-scanner');
            wp_send_json($response, 200);
            return;
        }

        $counter_i = 0;
        $previousDate = '';
        $outdata = (array)$auditlogs['output_data'];
        $todaysDate = SucuriScan::datetime(null, 'M d, Y');
        $iterator_start = ($pageNumber - 1) * $maxPerPage;
        $total_items = count($outdata);

        usort($outdata, array('SucuriScanAuditLogs', 'sortByDate'));

        for ($i = $iterator_start; $i < $total_items; $i++) {
            if ($counter_i >= $maxPerPage) {
                break;
            }

            if (!isset($outdata[$i])) {
                continue;
            }

            $audit_log = (array)$outdata[$i];

            $snippet_data = array(
                'AuditLog.Event' => $audit_log['event'],
                'AuditLog.Time' => SucuriScan::datetime($audit_log['timestamp'], 'H:i'),
                'AuditLog.Date' => SucuriScan::datetime($audit_log['timestamp'], 'M d, Y'),
                'AuditLog.Username' => $audit_log['username'],
                'AuditLog.Address' => $audit_log['remote_addr'],
                'AuditLog.Message' => self::plainText($audit_log['message']),
                'AuditLog.Extra' => '',
            );

            $date_data = self::getAuditLogDate($snippet_data['AuditLog.Date'], $previousDate, $todaysDate);
            $snippet_data['AuditLog.Date'] = $date_data['date'];
            $previousDate = $date_data['previousDate'];

            /* print every file_list information item in a separate table */
            if ($audit_log['file_list']) {
                $snippet_data['AuditLog.Extra'] .= '<ul class="sucuriscan-list-as-table">';

                foreach ($audit_log['file_list'] as $log_extra) {
                    $snippet_data['AuditLog.Extra'] .= '<li>'
                        . SucuriScan::escape(self::plainText($log_extra)) . '</li>';
                }

                $snippet_data['AuditLog.Extra'] .= '</ul>';
            }

            /* simplify the details of events with low metadata */
            if (strpos($audit_log['message'], __('status has been changed', 'sucuri-scanner'))) {
                $snippet_data['AuditLog.Extra'] = self::formatChangedStatusFiles($audit_log['file_list']);
            }

            $response['content'] .= SucuriScanTemplate::getSnippet('auditlogs', $snippet_data);
            $counter_i += 1;
        }

        $response['count'] = $counter_i;

        if ($total_items > 1) {
            $maxpages = ceil($total_items / $maxPerPage);

            if ($maxpages > SUCURISCAN_MAX_PAGINATION_BUTTONS) {
                $maxpages = SUCURISCAN_MAX_PAGINATION_BUTTONS;
            }

            if ($maxpages > 1) {
                $response['pagination'] = SucuriScanTemplate::pagination(
                    SucuriScanTemplate::getUrl(),
                    ($maxPerPage * $maxpages),
                    $maxPerPage,
                    $filters
                );
            }
        }

        wp_send_json($response, 200);
    }

    /**
     * Send every audit trail stored on this website to the browser as a CSV.
     *
     * The filters on the audit trail page deliberately do not apply here; the
     * export is always the complete local history.
     *
     * @codeCoverageIgnore - The method ends the request with exit(), which a
     * test runner cannot survive. Everything it decides is delegated to
     * canDownloadAuditLogs() and writeAuditLogsCsv(), both of which are
     * covered.
     *
     * @return void
     */
    public static function downloadAuditLogs()
    {
        SucuriScanInterface::checkPageVisibility();

        if (!self::canDownloadAuditLogs()) {
            wp_die(
                esc_html__('The audit trail download link is invalid or expired.', 'sucuri-scanner'),
                esc_html__('Audit trail export', 'sucuri-scanner'),
                array('response' => 403, 'back_link' => true)
            );
        }

        /**
         * Assembled into a scratch stream, not a string, and completed before a
         * single header goes out. Two things fall out of that: the response
         * never holds the whole export in memory, and a failure while reading
         * the queue surfaces as an ordinary error page instead of reaching the
         * browser as a complete-looking but truncated CSV.
         */
        $buffer = self::openCsvBuffer();

        if (!is_resource($buffer) || !self::writeAuditLogsCsv($buffer)) {
            if (is_resource($buffer)) {
                fclose($buffer); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            }

            wp_die(
                esc_html__('The audit trails could not be read. No CSV was downloaded.', 'sucuri-scanner'),
                esc_html__('Audit trail export', 'sucuri-scanner'),
                array('response' => 500, 'back_link' => true)
            );
        }

        $filename = 'sucuri-audit-trails-' . SucuriScan::datetime(null, 'Y-m-d') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');

        /**
         * No Content-Length on purpose. Anything that has already written to
         * the response -- a stray notice, a plugin echoing on admin_init --
         * makes the declared length disagree with the body, and the browser
         * then saves a CSV cut short at that offset. Letting the connection
         * close instead keeps a noisy download complete.
         */

        rewind($buffer);
        fpassthru($buffer);
        fclose($buffer); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

        exit(0);
    }

    /**
     * Open the scratch stream the CSV is assembled into.
     *
     * php://temp keeps the first couple of megabytes in memory and spills the
     * rest to a temporary file, so the size of the export stops mattering to
     * the memory limit. It is cleaned up when the handle closes.
     *
     * @return resource|bool Writable stream, or false when it cannot be opened.
     */
    private static function openCsvBuffer()
    {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        return @fopen('php://temp/maxmemory:' . (2 * 1024 * 1024), 'w+');
    }

    /**
     * Check the nonce that authorizes an audit trail export.
     *
     * @return bool Whether the request carries a valid download nonce.
     */
    private static function canDownloadAuditLogs()
    {
        $nonce = SucuriScanRequest::get('sucuriscan_page_nonce', '_nonce');

        return (bool) ($nonce && wp_verify_nonce($nonce, 'sucuriscan_page_nonce'));
    }

    /**
     * Write every audit trail stored on this website to a stream as CSV.
     *
     * The queue is read a line at a time and each record is converted and
     * written before the next one is read, so peak memory is a function of the
     * longest single entry rather than of the size of the queue. That matters
     * because admin-post.php loads wp-admin/includes/admin.php rather than
     * wp-admin/admin.php, and so never raises the memory limit the way an admin
     * screen does: this runs at WP_MEMORY_LIMIT, 40M on a single site, and a
     * site that has never had an API key configured accumulates the queue
     * indefinitely because only sendLogsFromQueue() ever drains it.
     *
     * Rows come out oldest first, in the order the events were recorded. The
     * audit page sorts newest first, but sorting means holding every record at
     * once, which is the cost this method exists to avoid; chronological order
     * is the natural one for a log file anyway.
     *
     * A record the parser rejects -- a line whose JSON does not decode, which
     * an interrupted append can leave behind -- is skipped here exactly as it
     * is skipped on the page. Deliberate: the export shows what the audit trail
     * shows, rather than growing a second reader that disagrees with it.
     *
     * @param resource $stream Writable stream to receive the CSV.
     * @return bool Whether the whole queue was read and written.
     */
    private static function writeAuditLogsCsv($stream)
    {
        $written = self::writeCsvRow(
            $stream,
            array('Date', 'Time', 'Severity', 'Username', 'IP Address', 'Message', 'Details')
        );

        if (!$written) {
            return false;
        }

        $cache = new SucuriScanCache('auditqueue');
        $finfo = $cache->getDatastoreInfo();
        $path = is_array($finfo) && isset($finfo['fpath']) ? $finfo['fpath'] : '';

        if (!$path || !is_readable($path)) {
            /* nothing has ever been logged; an empty export is still valid */
            return true;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $handle = @fopen($path, 'r');

        if ($handle === false) {
            return false;
        }

        $started = false;

        while (($line = fgets($handle)) !== false) {
            /**
             * The datastore is a PHP file that stops its own execution when it
             * is requested directly. Everything up to and including the line
             * that closes that block is the guard, not data.
             */
            if (!$started) {
                $started = strpos($line, '?>') !== false;
                continue;
            }

            $log = self::parseQueueLine($line);

            if ($log === null) {
                continue;
            }

            if (!self::writeAuditLogRow($stream, $log)) {
                $written = false;
                break;
            }
        }

        fclose($handle); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

        return $written;
    }

    /**
     * Convert one raw datastore line into a parsed audit trail.
     *
     * The line is handed to the same parser the audit page uses, one record at
     * a time, so the export cannot drift from what the page shows -- redaction
     * included.
     *
     * @param string $line Raw datastore line.
     * @return array|null Parsed audit trail, or null when the line is not one.
     */
    private static function parseQueueLine($line)
    {
        $line = trim($line);
        $separator = strpos($line, ':');

        if ($line === '' || $separator === false) {
            return null;
        }

        $key = substr($line, 0, $separator);
        $message = json_decode(substr($line, $separator + 1), true);

        if (!is_string($message)) {
            return null; /* incompatible JSON data */
        }

        $offset = strpos($key, '_');
        $timestamp = $offset === false ? $key : substr($key, 0, $offset);

        $parsed = SucuriScanAPI::filterAuditLogs(
            array(
                'output' => array(
                    sprintf(
                        '%s %s : %s',
                        SucuriScan::datetime($timestamp, 'Y-m-d H:i:s'),
                        SucuriScan::getSiteEmail(),
                        $message
                    ),
                ),
                'total_entries' => 1,
                'from_queue' => '1',
            )
        );

        return isset($parsed['output_data'][0]) ? $parsed['output_data'][0] : null;
    }

    /**
     * Write one parsed audit trail as a CSV row.
     *
     * @param resource $stream Writable stream.
     * @param array    $log    Parsed audit trail.
     * @return bool Whether the complete row was written.
     */
    private static function writeAuditLogRow($stream, $log)
    {
        $details = isset($log['file_list'])
            ? array_map(array('SucuriScanAuditLogs', 'plainText'), (array) $log['file_list'])
            : array();

        return self::writeCsvRow(
            $stream,
            array(
                isset($log['date']) ? $log['date'] : '',
                isset($log['time']) ? $log['time'] : '',
                isset($log['event']) ? $log['event'] : '',
                self::plainText(isset($log['username']) ? $log['username'] : ''),
                isset($log['remote_addr']) ? $log['remote_addr'] : '',
                self::plainText(isset($log['message']) ? $log['message'] : ''),
                implode(";\x20", $details),
            )
        );
    }

    /**
     * Write one RFC 4180 CSV row to a stream.
     *
     * @param resource $stream Writable stream.
     * @param array    $fields CSV field values.
     * @return bool Whether the complete row was written.
     */
    private static function writeCsvRow($stream, $fields)
    {
        $row = self::auditLogCsvRow($fields);

        return fwrite($stream, $row) === strlen($row); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
    }

    /**
     * Write one RFC 4180 CSV row.
     *
     * Every field is quoted and every embedded quote is doubled, which is all
     * the escaping the format needs; fputcsv() is avoided because its handling
     * of the escape character changed between the PHP versions this plugin
     * supports.
     *
     * @param array $fields CSV field values.
     * @return string One CSV row, terminated with CRLF.
     */
    private static function auditLogCsvRow($fields)
    {
        $quoted = array();

        foreach ((array) $fields as $value) {
            $value = self::spreadsheetSafeValue($value);
            $quoted[] = '"' . str_replace('"', '""', $value) . '"';
        }

        return implode(',', $quoted) . "\r\n";
    }

    /**
     * Prevent spreadsheet applications from evaluating exported values.
     *
     * @param mixed $value CSV field value.
     * @return string Safe CSV field value.
     */
    private static function spreadsheetSafeValue($value)
    {
        $value = (string) $value;

        /**
         * "|" and "%" open a DDE link in LibreOffice Calc and older versions of
         * Excel, so they need the same treatment as the four characters that
         * start a formula.
         */
        if (preg_match('/^[\x00-\x20]*[=+\-@|%]/', $value)) {
            return "'" . $value;
        }

        return $value;
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
     * @param array $a Data associated to a single log.
     * @param array $b Data associated to another log.
     * @return int      Comparison between the dates of both logs.
     */
    public static function sortByDate($a, $b)
    {
        if ($a['timestamp'] === $b['timestamp']) {
            return 0;
        }

        return ($a['timestamp'] > $b['timestamp']) ? -1 : 1;
    }

    /**
     * Decode an audit trail value back to the text the event described.
     *
     * Hooks build their messages out of values passed through
     * SucuriScan::escape(), so a plugin named "A<B" is stored as "A&lt;B".
     * Every consumer escapes again on the way out -- the template through its
     * "%%...%%" tag, the CSV writer by quoting -- which would show the reader
     * the entity rather than the character. Decoding once here is what makes
     * the two agree; callers must still escape for their own medium.
     *
     * @param mixed $value Stored audit trail value.
     * @return string Decoded text.
     */
    private static function plainText($value)
    {
        return html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format the file list shown for a "status has been changed" audit entry.
     *
     * The result is assigned to AuditLog.Extra, which the audit-log template renders
     * with the raw "%%%...%%%" tag, so each file name is HTML-escaped here before it is
     * joined into the displayed string.
     *
     * @param array $file_list File names from the audit-log entry.
     * @return string Comma-separated, HTML-escaped list.
     */
    private static function formatChangedStatusFiles($file_list)
    {
        $entries = array_map(
            array('SucuriScanAuditLogs', 'plainText'),
            (array) $file_list
        );

        return implode(
            ",\x20",
            array_map(array('SucuriScan', 'escape'), $entries)
        );
    }

    /**
     * @param array $snippet_data
     * @param mixed $previousDate
     * @param mixed $todaysDate
     * @return array
     */
    public static function getAuditLogDate($date = '', $previousDate = '', $todaysDate = '')
    {
        // Since we're iterating over the logs, we need to keep track of the
        // previous date to determine if we need to print the date again.
        // This serves as a visual separator between the logs.
        if ($date === $previousDate) {
            $date = '';
        } elseif ($date === $todaysDate) {
            $previousDate = $date;
            $date = __('Today', 'sucuri-scanner');
        } else {
            $previousDate = $date;
        }

        if (!empty($date)) {
            $date = '<div class="sucuriscan-auditlog-date">' . $date . '</div>';
        }

        return array(
            'date' => $date,
            'previousDate' => $previousDate,
        );
    }
}
