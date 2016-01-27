
<div id="poststuff">
    <div class="postbox">
        <h3>Firewall Audit Logs</h3>

        <div class="inside">
            <p>
                CloudProxy logs every request involved in an attack and separates them from the
                legitimate requests. You can analyze the data from the latest entries in the
                logs using this tool and take action either enabling the advanced features of
                the IDS <em>(Intrusion Detection System)</em> from the <a  target="_blank"
                href="https://waf.sucuri.net/?settings">CloudProxy Dashboard</a> and/or blocking
                IP addresses and URL paths directly from the <a href="https://waf.sucuri.net/?audit"
                target="_blank">CloudProxy Audit Trails</a> page.
            </p>

            <div class="sucuriscan-inline-alert-info">
                <p>Note that non-blocked requests are hidden from the logs, this is intentional.</p>
            </div>

            <div>
                <form action="%%SUCURI.URL.Firewall%%#auditlogs" method="post">
                    <span class="sucuriscan-input-group">
                        <label>Query:</label>
                        <input type="text" id="sucuriscan_firewall_query" class="input-text" />
                        <select id="sucuriscan_firewall_day">%%%SUCURI.AuditLogs.DateDays%%%</select>
                        <select id="sucuriscan_firewall_month">%%%SUCURI.AuditLogs.DateMonths%%%</select>
                        <select id="sucuriscan_firewall_year">%%%SUCURI.AuditLogs.DateYears%%%</select>
                    </span>
                    <button id="sucuriscan-firewall-auditlogs-button" class="button button-primary">Retrieve Logs</button>
                </form>
            </div>

            <script type="text/javascript">
            jQuery(function($){
                $('#sucuriscan-firewall-auditlogs-button').on('click', function(ev){
                    ev.preventDefault();
                    $('.sucuriscan-firewall-auditlogs tbody').html(
                        '<tr><td><em>Loading...</em></td></tr>'
                    );
                    var params = {
                        action: 'sucuriscan_firewall_ajax',
                        sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                        form_action: 'get_audit_logs',
                    };
                    params.sucuriscan_query = $('#sucuriscan_firewall_query').val();
                    params.sucuriscan_month = $('#sucuriscan_firewall_month').val();
                    params.sucuriscan_year = $('#sucuriscan_firewall_year').val();
                    params.sucuriscan_day = $('#sucuriscan_firewall_day').val();
                    $.post('%%SUCURI.AjaxURL.Firewall%%', params, function(data){
                        $('.sucuriscan-firewall-auditlogs tbody').html(data);
                    });
                });
                $('#sucuriscan-firewall-auditlogs-button').click();
            });
            </script>

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-firewall-auditlogs">
                <thead>
                    <tr>
                        <th>Audit Logs</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td><em>Loading...</em></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
