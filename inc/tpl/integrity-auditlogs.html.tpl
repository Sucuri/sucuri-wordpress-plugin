
<script type="text/javascript">
jQuery(function ($) {
    var sucuriscanLoadAuditLogs = function (page, reset) {
        var url = '%%SUCURI.AjaxURL.Home%%';

        if (page !== undefined && page > 0) {
            url += '&paged=' + page;
        }

        if (reset === true) {
            var loading = '<tr><td colspan="5"><em>Loading...</em></td></tr>';
            $('.sucuriscan-auditlogs tbody').html(loading);
        }

        $('.sucuriscan-pagination-loading').html('Loading...');

        $.post(url, {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'get_audit_logs',
        }, function (data) {
            if (data.content) {
                $('.sucuriscan-auditlogs tbody').html(data.content);
                $('.sucuriscan-pagination-loading').html('');
                $('.sucuriscan-auditlogs-count').html('(' + data.count + ' latest logs)');

                if (data.pagination !== '') {
                    $('.sucuriscan-auditlogs .sucuriscan-pagination').html(data.pagination);
                }

                if (data.enable_report) {
                    $('.sucuriscan-audit-report').removeClass('sucuriscan-hidden');
                }
            } else if (typeof data === 'object') {
                $('.sucuriscan-auditlogs tbody').html(
                '<tr><td colspan="5">Unrecoverable error</td></tr>');
            } else {
                $('.sucuriscan-auditlogs tbody').html(
                '<tr><td colspan="5">' + data + '</td></tr>');
            }
        });
    }

    setTimeout(function () {
        sucuriscanLoadAuditLogs(0, true);
    }, 100);

    $('.sucuriscan-auditlogs').on('click', '.sucuriscan-pagination-link', function (event) {
        event.preventDefault();
        sucuriscanLoadAuditLogs($(this).attr('data-page'));
    });
});
</script>

<table class="wp-list-table widefat sucuriscan-table sucuriscan-table-double-title sucuriscan-auditlogs">
    <thead>
        <tr>
            <th colspan="5" class="thead-with-button">
                <span>Audit Logs</span>
                <span class="sucuriscan-auditlogs-count">(Loading...)</span>

                <form action="%%SUCURI.URL.Settings%%" method="post"
                class="thead-topright-action sucuriscan-hidden sucuriscan-audit-report">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_audit_report" value="enable" />
                    <button type="submit" class="button-primary">Enable Audit Report</button>
                </form>
            </th>
        </tr>

        <tr>
            <th>&nbsp;</th>
            <th width="200">Date</th>
            <th>Username</th>
            <th>IP Address</th>
            <th>Event Message</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td colspan="5">
                <em>Loading...</em>
            </td>
        </tr>
    </tbody>

    <tfoot>
        <td colspan="5">
            <div class="sucuriscan-clearfix">
                <ul class="sucuriscan-pull-left sucuriscan-pagination">
                    <!-- Populated via JavaScript -->
                </ul>

                <div class="sucuriscan-pull-right sucuriscan-pagination-loading">
                    <!-- Populated via JavaScript -->
                </div>
            </div>
        </td>
    </tfoot>
</table>
