
<div class="postbox sucuriscan-audit-report sucuriscan-border">
    <h3>Audit Report</h3>

    <div class="inside">

        <div class="sucuriscan-inline-alert-info">
            <p>
                The data used to generate these charts comes from the last
                <strong>%%SUCURI.AuditReport.Logs4Report%% audit logs</strong>, you can
                configure this number from the plugin settings page, you can also disable
                and enable this panel from there at any time.
            </p>
        </div>

        <div class="sucuriscan-clearfix sucuriscan-report-row">

            <div class="sucuriscan-pull-left sucuriscan-report-chart">
                <h4>Audit Logs per Event</h4>
                <h5>source https://sucuri.net/</h5>
                <div id="sucuriscan-report-events-per-type"></div>
            </div>

            <div class="sucuriscan-pull-right sucuriscan-report-chart">
                <h4>Successful/Failed Logins</h4>
                <h5>source https://sucuri.net/</h5>
                <div id="sucuriscan-report-events-per-login"></div>
            </div>

        </div>

        <div class="sucuriscan-clearfix sucuriscan-report-row">

            <div class="sucuriscan-pull-left sucuriscan-report-chart">
                <h4>Audit Logs per User</h4>
                <h5>source https://sucuri.net/</h5>
                <div id="sucuriscan-report-events-per-user"></div>
            </div>

            <div class="sucuriscan-pull-right sucuriscan-report-chart">
                <h4>Audit Logs per IP Address</h4>
                <h5>source https://sucuri.net/</h5>
                <div id="sucuriscan-report-events-per-ipaddress"></div>
            </div>

        </div>

    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($){

    var sucuriscan_pie_chart = function( element, series, colors ){
        c3.generate({
            bindto: element,
            size: { height: 250 },
            padding: { top: 10, bottom: 10 },
            color: { pattern: colors },
            legend: { position: 'right' },
            data: { type: 'pie', labels: true, columns: series },
        });
    };

    var sucuriscan_bar_chart = function( element, categories, series ){
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

    /* Pie-chart with number of audit logs per event type. */
    sucuriscan_pie_chart(
        '#sucuriscan-report-events-per-type',
        [ %%%SUCURI.AuditReport.EventsPerType%%% ],
        [ %%%SUCURI.AuditReport.EventColors%%% ]
    );

    /* Column-chart with number of audit logs per event login. */
    sucuriscan_pie_chart(
        '#sucuriscan-report-events-per-login',
        [ %%%SUCURI.AuditReport.EventsPerLogin%%% ],
        [ '#5cb85c', '#f27d7d' ]
    );

    /* Bar-chart with number of audit logs per user account. */
    sucuriscan_bar_chart(
        '#sucuriscan-report-events-per-user',
        [ %%%SUCURI.AuditReport.EventsPerUserCategories%%% ],
        [ 'data', %%%SUCURI.AuditReport.EventsPerUserSeries%%% ]
    );

    /* Bar-chart with number of audit logs per remote address. */
    sucuriscan_bar_chart(
        '#sucuriscan-report-events-per-ipaddress',
        [ %%%SUCURI.AuditReport.EventsPerIPAddressCategories%%% ],
        [ 'data', %%%SUCURI.AuditReport.EventsPerIPAddressSeries%%% ]
    );

});
</script>
