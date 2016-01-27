
<div class="postbox">
    <h3>Alert Events</h3>

    <div class="inside">
        <p>
            Configure the alert settings to your needs, and make sure to read the purpose of
            each option below otherwise you will end up enabling and/or disabling things
            that will affect your personal inbox. If you experience issues with one or more
            of these options revert them to their original state.
        </p>

        <div class="sucuriscan-inline-alert-warning">
            <p>
                Enabling the alerts for failed login attempts may become an indirect mail spam
                attack as you will receive tons of emails if your website is victim of a brute
                force attack. Disable this option and enable the brute force attack reports to
                get a summary of all the failed logins detected each hour.
            </p>
        </div>

        <form action="%%SUCURI.URL.Settings%%#notifications" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-settings-notifications">
                <thead>
                    <tr>
                        <th class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </th>
                        <th class="manage-column">Event Description</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.AlertSettings.Events%%%
                </tbody>
            </table>

            <div class="sucuriscan-recipient-form">
                <button type="submit" name="sucuriscan_save_alert_events" class="button-primary">Save</button>
            </div>
        </form>
    </div>
</div>
