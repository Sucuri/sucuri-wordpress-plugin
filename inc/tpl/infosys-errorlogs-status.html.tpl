
<div class="postbox">
    <h3>Error Logs</h3>

    <div class="inside">
        <p>
            Web servers like Apache, Nginx and others use files to record errors encountered
            during the execution of a dynamic language or the server processes. Depending on
            the configuration of the server, these files may be accessible from the web
            opening a hole in your site to allow an attacker to gather sensitive information
            of your project, so it is highly recommended to delete them.
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>Ignore Scanning is %%SUCURI.ErrorLogs.Status%%</span>
            <form action="%%SUCURI.URL.Infosys%%#error-logs" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_parse_errorlogs" value="%%SUCURI.ErrorLogs.SwitchValue%%" />
                <button type="submit" class="button-primary %%SUCURI.ErrorLogs.SwitchCssClass%%">%%SUCURI.ErrorLogs.SwitchText%%</button>
            </form>
        </div>
    </div>
</div>
