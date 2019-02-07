
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Alerts Per Hour}}</h3>

    <div class="inside">
        <p>{{Configure the maximum number of email alerts per hour. If the number is exceeded and the plugin detects more events during the same hour, it will still log the events into the audit logs but will not send the email alerts. Be careful with this as you will miss important information.}}</p>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>{{Maximum Alerts Per Hour:}}</label>
                <select name="sucuriscan_emails_per_hour">
                    %%%SUCURI.Alerts.PerHour%%%
                </select>
                <button type="submit" class="button button-primary">{{Submit}}</button>
            </fieldset>
        </form>
    </div>
</div>
