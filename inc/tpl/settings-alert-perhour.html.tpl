
<div class="postbox">
    <h3>Alerts Per Hour</h3>

    <div class="inside">
        <p>
            Depending on the number of yours registered in your website or the number of
            actions performed by these users the recipients of the alerts sent when the site
            triggers an action that the plugin monitors may become annoying or irrelevant
            after some time. You can use this option to configure the maximum number of
            alerts to receive during the same hour.
        </p>

        <div class="sucuriscan-inline-alert-warning">
            <p>
                If you have enabled the alerts for <a href="https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing"
                target="_blank">password guessing brute force attacks</a> and lowered the number
                of alerts sent during the hour has reached its limit, the plugin will force the
                sending of the alert; you can consider the limit for alerts per hour a
                <em>"limit + one"</em> if the brute force attack summary is generated.
            </p>
        </div>

        <form action="%%SUCURI.URL.Settings%%#notifications" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <span class="sucuriscan-input-group">
                <label>Maximum Alerts Per Hour:</label>
                <select name="sucuriscan_emails_per_hour">
                    %%%SUCURI.AlertSettings.PerHour%%%
                </select>
            </span>
            <button type="submit" class="button-primary">Save</button>
        </form>
    </div>
</div>
