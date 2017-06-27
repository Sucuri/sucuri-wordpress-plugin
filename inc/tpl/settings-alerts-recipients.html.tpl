
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.AlertsRecipient@@</h3>

    <div class="inside">
        <p>@@SUCURI.AlertsRecipientInfo@@</p>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.Email@@:</label>
                <input type="text" name="sucuriscan_recipient" placeholder="e.g. user@example.com" />
                <button type="submit" name="sucuriscan_save_recipient" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>

            <table class="wp-list-table widefat sucuriscan-table">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="manage-column">@@SUCURI.Email@@</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.Alerts.Recipients%%%
                </tbody>
            </table>

            <button type="submit" name="sucuriscan_delete_recipients" class="button button-primary">@@SUCURI.Delete@@</button>
            <button type="submit" name="sucuriscan_debug_email" value="1" class="button button-primary">@@SUCURI.TestAlerts@@</button>
        </form>
    </div>
</div>
