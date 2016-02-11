
<div class="postbox">
    <h3>Error Logs - File Limit</h3>

    <div class="inside">
        <p>
            If you are a developer, you may want to check the latest errors encountered by
            the server before delete the log file, that way you can see where the application
            is failing and fix the errors. Note that a log file may have thousand of lines,
            so to prevent an overflow in the memory of the PHP interpreter the plugin limits
            the process to the <strong>latest %%SUCURI.ErrorLogs.LogsLimit%% lines</strong>
            inserted in the log file.
        </p>

        <form action="%%SUCURI.URL.Infosys%%#error-logs" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <span class="sucuriscan-input-group">
                <label>Error Logs - File Limit:</label>
                <input type="text" name="sucuriscan_errorlogs_limit" class="input-text" placeholder="e.g. 30" />
            </span>
            <button type="submit" class="button-primary">Save</button>
        </form>
    </div>
</div>
