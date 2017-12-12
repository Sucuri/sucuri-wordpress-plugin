
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Log Exporter</h3>

    <div class="inside">
        <p>This option allows you to export the WordPress audit logs to a local log file that can be read by a SIEM or any log analysis software <em>(we recommend OSSEC)</em>. That will give visibility from within WordPress to complement your log monitoring infrastructure. <b>NOTE:</b> Do not use a publicly accessible file, you must use a file at least one level up the document root to prevent leaks of information.</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-%%SUCURI.SelfHosting.DisabledVisibility%%">
            <span>Log Exporter &mdash; %%SUCURI.SelfHosting.Status%%</span>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-monitor-fpath sucuriscan-%%SUCURI.SelfHosting.FpathVisibility%%">
            <span class="sucuriscan-monospace">%%SUCURI.SelfHosting.Fpath%%</span>
            <form action="%%SUCURI.URL.Settings%%#general" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_selfhosting_fpath" />
                <button type="submit" class="button button-primary">%%SUCURI.SelfHosting.SwitchText%%</button>
            </form>
        </div>

        <form action="%%SUCURI.URL.Settings%%#general" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>File Path:</label>
                <input type="text" name="sucuriscan_selfhosting_fpath" />
                <button type="submit" class="button button-primary">Submit</button>
            </fieldset>
        </form>
    </div>
</div>
