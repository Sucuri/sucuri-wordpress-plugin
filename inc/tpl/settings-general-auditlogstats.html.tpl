
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.LogsReport@@</h3>

    <div class="inside">
        <p>@@SUCURI.LogsReportDescription@@</p>

        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.LogsReport@@:</label>
                <input type="text" name="sucuriscan_logs4report" value="%%SUCURI.AuditLogStats.Limit%%" placeholder="e.g. 500" />
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>
        </form>
    </div>
</div>
