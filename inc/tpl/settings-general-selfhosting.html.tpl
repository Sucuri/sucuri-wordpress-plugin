
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Log Exporter</h3>

    <div class="inside">
        <p>
            This option allows you to export the WordPress audit logs to a local log file
            that can be read by a SIEM or any log analysis software <em>(we recommend OSSEC)
            </em>. That will give visibility from within WordPress to complement your log
            monitoring infrastructure.
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-%%SUCURI.SelfHosting.DisabledVisibility%%">
            <span>Log Exporter is %%SUCURI.SelfHosting.Status%%</span>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-monitor-fpath sucuriscan-%%SUCURI.SelfHosting.FpathVisibility%%">
            <span class="sucuriscan-monospace">%%SUCURI.SelfHosting.Fpath%%</span>
            <form action="%%SUCURI.URL.Settings%%#general" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_selfhosting_fpath" />
                <button type="submit" class="button button-primary">%%SUCURI.SelfHosting.SwitchText%%</button>
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

        <form action="%%SUCURI.URL.Settings%%#general" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>Absolute File Path:</label>
                <input type="text" name="sucuriscan_selfhosting_fpath" />
                <button type="submit" class="button button-primary">Save</button>
            </fieldset>
        </form>
    </div>
</div>
