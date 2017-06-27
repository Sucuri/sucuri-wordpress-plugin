
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
        <p>@@SUCURI.LogsReportInfo@@</p>
    </div>

    <div id="sucuriscan-audit-report-response">
        <p>@@SUCURI.Loading@@</p>
    </div>

    <div class="sucuriscan-report-chart sucuriscan-hidden">
        <h4>@@SUCURI.LogsPerEvent@@</h4>
        <div id="sucuriscan-report-events-per-type"></div>
    </div>

    <div class="sucuriscan-report-chart sucuriscan-hidden">
        <h4>@@SUCURI.LogsForLogins@@</h4>
        <div id="sucuriscan-report-events-per-login"></div>
    </div>

    <div class="sucuriscan-report-chart sucuriscan-hidden">
        <h4>@@SUCURI.LogsPerUser@@</h4>
        <div id="sucuriscan-report-events-per-user"></div>
    </div>

    <div class="sucuriscan-report-chart sucuriscan-hidden">
        <h4>@@SUCURI.LogsPerIP@@</h4>
        <div id="sucuriscan-report-events-per-ipaddress"></div>
    </div>
</div>
