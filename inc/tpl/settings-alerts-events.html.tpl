
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Security Alerts}}</h3>

    <div class="inside">
        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.Alerts.NoAlertsVisibility%%">
            <p>{{You have installed a plugin or theme that is not fully compatible with our plugin, some of the security alerts (like the successful and failed logins) will not be sent to you. To prevent an infinite loop while detecting these changes in the website and sending the email alerts via a custom SMTP plugin, we have decided to stop any attempt to send the emails to prevent fatal errors.}}</p>
        </div>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-settings-alerts">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">{{Select All}}</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="manage-column">{{Event}}</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.Alerts.Events%%%
                </tbody>
            </table>

            <div class="sucuriscan-recipient-form">
                <button type="submit" name="sucuriscan_save_alert_events" class="button button-primary" data-cy="sucuriscan_save_alert_events_submit">{{Submit}}</button>
            </div>
        </form>
    </div>
</div>
