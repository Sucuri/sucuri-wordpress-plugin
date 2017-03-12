
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Audit Log Statistics</h3>

    <div class="inside">
        <p>
            Enabling this option allows you to have a quick view of the range of
            the activity of your users and/or the attacks directed against your
            website. By default, the plugin uses the latest entries in the audit
            logs and uses that information to draw bar and pie charts in the
            dashboard.
        </p>

        <p>
            The statistic are generated with a limited number of logs to reduce
            the memory consumption of the parser. You can increase the limit at
            your own discretion considering the amount of memory and maximum
            execution time that your PHP installation is allowed to use.
        </p>

        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>Audit Logs Limit:</label>
                <input type="text" name="sucuriscan_logs4report" value="%%SUCURI.AuditLogStats.Limit%%" placeholder="e.g. 500" />
                <button type="submit" class="button button-primary">Save</button>
            </fieldset>
        </form>
    </div>
</div>
