
<div class="postbox">
    <h3>Malware Scanner Timeout</h3>

    <div class="inside">
        <p>
            <a href="https://sitecheck.sucuri.net/" target="_blank">SiteCheck</a> is a web
            application scanner that reads the source code of a website to determine if it
            is serving malicious code, it scans the home page and linked sub-pages, then
            compares the results with a list of signatures as well as a list of blacklist
            services to see if other malware scanners have flagged the website before. This
            operation may take a couple of seconds, around twenty seconds in most cases; be
            sure to set enough timeout for the operation to finish, otherwise the scanner
            will return innacurate information.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>
                You can set up to %%SUCURI.MaxRequestTimeout%% seconds for the timeout, more than that is not allowed.
            </p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>Wait <b>%%SUCURI.RequestTimeout%%</b> before timeout</span>
        </div>

        <p>
            If you start experiencing issues related with the timeout of the requests
            you may consider to increase the number of seconds to wait for the response.
            You may also want to check with your hosting provider to see if there is
            something in the server blocking the connection.
        </p>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <span class="sucuriscan-input-group">
                <label>HTTP Request Timeout (in secs)</label>
                <input type="text" name="sucuriscan_sitecheck_timeout" class="input-text" />
            </span>
            <button type="submit" class="button-primary">Proceed</button>
        </form>
    </div>
</div>
