
<div id="poststuff">
    <div class="postbox sucuriscan-border sucuriscan-table-description sucuriscan-errorlogs">
        <h3>Error Logs</h3>

        <div class="inside">

            <p>
                Web servers like Apache, Nginx and others use files to record errors encountered
                during the execution of a dynamic language or the server processes. Depending on
                the configuration of the server, these files may be accessible from the web
                opening a hole in your site to allow an attacker to gather sensitive information
                of your project, so it is highly recommended to delete them.
            </p>

            <div class="sucuriscan-inline-alert-info">
                <p>
                    If you are a developer, you may want to check the latest errors encountered by
                    the server before delete the log file, that way you can see where the
                    application is failing and fix the errors. Note that a log file may have
                    thousand of lines, so to prevent an overflow in the memory of the PHP
                    interpreter the plugin limits the process to the <strong>latest
                    %%SUCURI.ErrorLog.LogsLimit%% lines</strong> inserted in the log file.
                </p>
            </div>

            <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.ErrorLog.DisabledVisibility%%">
                <p>
                    The analysis of error logs is disabled, go to the <em>Scanner Settings</em>
                    panel in the <em>Settings</em> page to enable it.
                </p>
            </div>

            <div class="sucuriscan-inline-alert-warning sucuriscan-%%SUCURI.ErrorLog.InvalidFormatVisibility%%">
                <p>
                    Note that if the log file is not empty but the table is, it means that the
                    format of the logs used by the web server is not supported by the scanner,
                    you can try to increase the number of lines processed though from
                    <a href="%%SUCURI.URL.Settings%%#scanner">here</a> in case that
                    other lines have a different format which is very common on servers with
                    mixed configurations.
                </p>
            </div>

        </div>
    </div>
</div>

<table class="wp-list-table widefat sucuriscan-table sucuriscan-table-double-title sucuriscan-errorlogs-list">
    <thead>
        <tr>
            <th colspan="5" class="thead-with-button">
                <span>Error Logs (%%SUCURI.ErrorLog.FileSize%%)</span>

                <form action="%%SUCURI.URL.Hardening%%#error-logs" method="post" class="thead-topright-action">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_run_hardening" value="1" />
                    <input type="hidden" name="sucuriscan_harden_errorlog" value="Harden" />
                    <button type="submit" class="button-primary">Delete logs</button>
                </form>
            </th>
        </tr>

        <tr>
            <th width="100">Date Time</th>
            <th width="50">Type</th>
            <th>Error Message</th>
            <th width="300">File</th>
            <th width="50">Line</th>
        </tr>
    </thead>

    <tbody>
        %%%SUCURI.ErrorLog.List%%%

        <tr class="sucuriscan-%%SUCURI.ErrorLog.InvalidFormatVisibility%%">
            <td colspan="5">
                <em>No valid logs in the last %%SUCURI.ErrorLog.LogsLimit%% lines of the error log file.</em>
            </td>
        </tr>

        <tr class="sucuriscan-%%SUCURI.ErrorLog.NoItemsVisibility%%">
            <td colspan="5">
                <em>No logs so far.</em>
            </td>
        </tr>
    </tbody>
</table>
