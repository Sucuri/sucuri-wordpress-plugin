
<div class="postbox">
    <h3>Log Exporter</h3>

    <div class="inside">
        <p>
            This option allows you to export the WordPress audit logs to a local log file
            that can be read by a SIEM or any log analysis software <em>(we recommend OSSEC)
            </em>. That will give visibility from within WordPress to complement your log
            monitoring infrastructure.
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-%%SUCURI.SelfHostingMonitor.DisabledVisibility%%">
            <span>Log Exporter is %%SUCURI.SelfHostingMonitor.Status%%</span>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-monitor-fpath sucuriscan-%%SUCURI.SelfHostingMonitor.FpathVisibility%%">
            <span class="sucuriscan-monospace">%%SUCURI.SelfHostingMonitor.Fpath%%</span>
            <form action="%%SUCURI.URL.Settings%%#selfhosting" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_selfhosting_fpath" class="input-text" />
                <button type="submit" class="button-primary %%SUCURI.SelfHostingMonitor.SwitchCssClass%%">
                %%SUCURI.SelfHostingMonitor.SwitchText%%</button>
            </form>
        </div>

        <p>
            Specify the absolute location of the file <em>(including the extension)</em>
            that you want to use to store a copy of the events that are being monitored by
            the plugin. The file must exists and be writable by the PHP interpreter. Note
            that the events that are being triggered when this option is disabled will not
            be copied to this file even if you have enabled this feature before, you must
            consider this when you give access to other administrator users to change the
            settings of your website.
        </p>

        <div class="sucuriscan-inline-alert-warning">
            <p>
                Do not use a public location to store the logs, you will end up leaking
                sensitive information about your website and the activity of your users. If you
                decide to use a file located in the public directory for any particular reason
                we recommend you to name it with a random-unique string so malicious users can
                not easily access it.
            </p>
        </div>

        <form action="%%SUCURI.URL.Settings%%#selfhosting" method="post" class="sucuriscan-%%SUCURI.SelfHostingMonitor.DisabledVisibility%%">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <span class="sucuriscan-input-group">
                <label>Absolute File Path:</label>
                <input type="text" name="sucuriscan_selfhosting_fpath" class="input-text" />
            </span>
            <button type="submit" class="button-primary">Save</button>
        </form>
    </div>
</div>
