
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.AlertsPerHour@@</h3>

    <div class="inside">
        <p>@@SUCURI.AlertsPerHourInfo@@</p>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.AlertsPerHourMaximum@@:</label>
                <select name="sucuriscan_emails_per_hour">
                    %%%SUCURI.Alerts.PerHour%%%
                </select>
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>
        </form>
    </div>
</div>
