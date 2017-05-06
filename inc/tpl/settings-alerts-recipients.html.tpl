
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Alert Recipients</h3>

    <div class="inside">
        <p>
            By default the plugin will send email alerts to the email address of the
            original user account created during the installation process of your website,
            you can change this adding a new address below and then deleting the old entry.
            Additionally, you are allowed to send a copy of the same alerts to other email
            addresses.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>
                Make sure to check your spam folder if you do not see the alerts in your inbox,
                if at least one of the recipients listed below receives the alert it means that
                the message was delivered correctly, if you or one of the other recipients is
                not receiving the alerts is probably because of a filter in your email service.
                This is because the plugin only sends one single message per alert, so either
                everyone gets the message or no one gets it.
            </p>
        </div>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <fieldset class="sucuriscan-clearfix">
                <label>E-mail Address:</label>
                <input type="text" name="sucuriscan_recipient" placeholder="e.g. user@example.com" />
                <button type="submit" name="sucuriscan_save_recipient" class="button button-primary">Add Recipient</button>
            </fieldset>

            <table class="wp-list-table widefat sucuriscan-table">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="manage-column">E-mail Address</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.Alerts.Recipients%%%
                </tbody>
            </table>

            <button type="submit" name="sucuriscan_delete_recipients" class="button button-primary">Delete Selected</button>
            <button type="submit" name="sucuriscan_debug_email" value="1" class="button button-primary">Test Alert Delivery</button>
        </form>
    </div>
</div>
