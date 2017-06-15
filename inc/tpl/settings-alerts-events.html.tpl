
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.SecurityAlerts@@</h3>

    <div class="inside">
        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-settings-alerts">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="manage-column">@@SUCURI.Event@@</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.Alerts.Events%%%
                </tbody>
            </table>

            <div class="sucuriscan-recipient-form">
                <button type="submit" name="sucuriscan_save_alert_events" class="button button-primary">@@SUCURI.Submit@@</button>
            </div>
        </form>
    </div>
</div>
