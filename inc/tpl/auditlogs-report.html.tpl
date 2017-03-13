
<script type="text/javascript">
/* global c3 */
/* global jQuery */
/* jshint camelcase:false */
jQuery(function ($) {
    var sucuriscanPieChart = function (element, series, colors) {
        c3.generate({
            bindto: element,
            size: { height: 250 },
            padding: { top: 10, bottom: 10 },
            color: { pattern: colors },
            legend: { position: 'right' },
            data: { type: 'pie', labels: true, columns: series },
        });
    };

    var sucuriscanBarChart = function (element, categories, series) {
        c3.generate({
            bindto: element,
            size: { height: 320 },
            padding: { top: 10, bottom: 0 },
            tooltip: { show: false },
            legend: { show: false },
            data: { type: 'bar', labels: true, columns: [ series ] },
            axis: {
                rotated: true,
                x: { type: 'category', categories: categories },
            },
        });
    };

    $.post('%%SUCURI.AjaxURL.Dashboard%%', {
        action: 'sucuriscan_ajax',
        sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
        form_action: 'get_audit_logs_report',
    }, function (data) {
        $('#sucuriscan-audit-report-response').html(data.message);

        if (data.status) {
            $('.sucuriscan-report-chart').removeClass('sucuriscan-hidden');

            /* Pie-chart with number of audit logs per event type. */
            sucuriscanPieChart(
                '#sucuriscan-report-events-per-type',
                data.eventsPerTypePoints,
                data.eventsPerTypeColors
            );

            /* Column-chart with number of audit logs per event login. */
            sucuriscanPieChart(
                '#sucuriscan-report-events-per-login',
                data.eventsPerLogin,
                [ '#5cb85c', '#f27d7d' ]
            );

            /* Bar-chart with number of audit logs per user account. */
            sucuriscanBarChart(
                '#sucuriscan-report-events-per-user',
                data.eventsPerUserCategories,
                data.eventsPerUserSeries
            );

            /* Bar-chart with number of audit logs per remote address. */
            sucuriscanBarChart(
                '#sucuriscan-report-events-per-ipaddress',
                data.eventsPerIPAddressCategories,
                data.eventsPerIPAddressSeries
            );
        }
    });
});
</script>

<div class="sucuriscan-audit-report">
    <div class="sucuriscan-inline-alert-info">
        <p>
            The data used to generate these charts comes from the last
            <strong>%%SUCURI.AuditReport.Logs4Report%% audit logs</strong>, you can
            configure this number from the plugin settings page, you can also disable
            and enable this panel from there at any time.
        </p>
    </div>

    <div id="sucuriscan-audit-report-response">
        <p>Loading...</p>
    </div>

    <div class="sucuriscan-report-chart sucuriscan-hidden">
        <h4>Audit Logs per Event</h4>
        <div id="sucuriscan-report-events-per-type"></div>
    </div>

    <div class="sucuriscan-report-chart sucuriscan-hidden">
        <h4>Successful/Failed Logins</h4>
        <div id="sucuriscan-report-events-per-login"></div>
    </div>

    <div class="sucuriscan-report-chart sucuriscan-hidden">
        <h4>Audit Logs per User</h4>
        <div id="sucuriscan-report-events-per-user"></div>
    </div>

    <div class="sucuriscan-report-chart sucuriscan-hidden">
        <h4>Audit Logs per IP Address</h4>
        <div id="sucuriscan-report-events-per-ipaddress"></div>
    </div>
</div>
