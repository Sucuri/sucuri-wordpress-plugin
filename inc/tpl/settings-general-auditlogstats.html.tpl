
<div class="postbox">
    <h3>Audit Log Statistics</h3>

    <div class="inside">
        <p>
            Enabling this option allows you to have a quick view of the range of the
            activity of your users and/or the attacks directed against your website. By
            default, the plugin uses the <strong>latest %%SUCURI.AuditLogStats.Limit%%
            entries</strong> in the audit logs and uses that information to draw bar and
            pie charts in the dashboard.
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.AuditLogStats.StatusNum%%">
            <span>Audit Log Statistics are %%SUCURI.AuditLogStats.Status%%</span>
            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_audit_report" value="%%SUCURI.AuditLogStats.SwitchValue%%" />
                <button type="submit" class="button-primary %%SUCURI.AuditLogStats.SwitchCssClass%%">%%SUCURI.AuditLogStats.SwitchText%%</button>
            </form>
        </div>

        <p>
            The statistic charts are generated with a limited number of logs stored in the
            remote API server, you can increase the number to draw the charts with more data
            and represent the activity during a wider range of days, but you must consider
            that the bigger the number the slower the plugin dashboard will be because it
            will take more time to read more logs.
        </p>

        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <span class="sucuriscan-input-group">
                <label>Audit Logs Limit:</label>
                <input type="text" name="sucuriscan_logs4report" class="input-text" placeholder="e.g. 500" />
            </span>
            <button type="submit" class="button-primary">Save</button>
        </form>
    </div>
</div>
