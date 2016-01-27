
<table class="wp-list-table widefat sucuriscan-table sucuriscan-table-double-title sucuriscan-auditlogs">
    <thead>
        <tr>
            <th colspan="5" class="thead-with-button">
                <span>Audit Logs (%%SUCURI.AuditLogs.Count%% latest logs)</span>
                <form action="%%SUCURI.URL.Settings%%" method="post"
                class="thead-topright-action sucuriscan-%%SUCURI.AuditLogs.EnableAuditReportVisibility%%">
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
        %%%SUCURI.AuditLogs.List%%%

        <tr class="sucuriscan-%%SUCURI.AuditLogs.NoItemsVisibility%%">
            <td colspan="5">
                <em>No logs so far.</em>
            </td>
        </tr>

        <tr class="sucuriscan-%%SUCURI.AuditLogs.PaginationVisibility%%">
            <td colspan="5">
                <ul class="sucuriscan-pagination">
                    %%%SUCURI.AuditLogs.PaginationLinks%%%
                </ul>
            </td>
        </tr>
    </tbody>
</table>
