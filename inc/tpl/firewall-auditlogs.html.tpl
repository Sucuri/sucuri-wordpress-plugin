
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.FirewallLogsTitle@@</h3>

    <div class="inside">
        <p>@@SUCURI.FirewallLogsInfo@@</p>

        <div class="sucuriscan-inline-alert-info">
            <p>@@SUCURI.FirewallLogsNote@@</p>
        </div>

        <script type="text/javascript">
        /* global jQuery */
        /* jshint camelcase: false */
        jQuery(function ($) {
            $('#sucuriscan-firewall-auditlogs-button').on('click', function (event) {
                event.preventDefault();

                var params = {};

                $('.sucuriscan-firewall-auditlogs tbody')
                .html('<tr><td><em>@@SUCURI.Loading@@</em></td></tr>');

                params.action = 'sucuriscan_ajax';
                params.form_action = 'get_firewall_logs';
                params.sucuriscan_page_nonce = '%%SUCURI.PageNonce%%';
                params.sucuriscan_query = $('#sucuriscan_firewall_query').val();
                params.sucuriscan_month = $('#sucuriscan_firewall_month').val();
                params.sucuriscan_year = $('#sucuriscan_firewall_year').val();
                params.sucuriscan_day = $('#sucuriscan_firewall_day').val();

                $.post('%%SUCURI.AjaxURL.Dashboard%%', params, function (data) {
                    if (data.match(/sucuriscan-alert/)) {
                        data = '<tr><td>' + data + '</td></tr>';
                    }

                    $('.sucuriscan-firewall-auditlogs tbody').html(data);
                });
            });

            $('#sucuriscan-firewall-auditlogs-button').click();
        });
        </script>

        <form action="%%SUCURI.URL.Firewall%%#auditlogs" method="post">
            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.Search@@:</label>
                <input type="text" id="sucuriscan_firewall_query" />
                <select id="sucuriscan_firewall_day">%%%SUCURI.AuditLogs.DateDays%%%</select>
                <select id="sucuriscan_firewall_month">%%%SUCURI.AuditLogs.DateMonths%%%</select>
                <select id="sucuriscan_firewall_year">%%%SUCURI.AuditLogs.DateYears%%%</select>
                <button id="sucuriscan-firewall-auditlogs-button" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-firewall-auditlogs">
                <thead>
                    <tr>
                        <th>@@SUCURI.FirewallLogsTitle@@</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td><em>@@SUCURI.Loading@@</em></td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>
