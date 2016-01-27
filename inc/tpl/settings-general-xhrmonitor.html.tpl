
<div class="postbox">
    <h3>XML HTTP Request Monitor</h3>

    <div class="inside">
        <p>
            Ajax <em>(also known as XHR)</em> is a set of web development techniques
            utilizing many web technologies used on the client-side to create asynchronous
            Web applications. With Ajax, web applications can send data to and retrieve from
            a server asynchronously <em>(in the background)</em> without interfering with
            the display and behavior of the existing page. Data can be retrieved using the
            <em>XMLHttpRequest</em> object.
        </p>

        <p>
            Ajax requests can be vulnerable to CSRF and many other attacks depending on the
            way the code is written, many web developers use this technique to offer a non-
            blocking interface in their themes and extensions which are later distributed on
            the Internet, their code is not audited and people end up installing things in
            their websites with security holes which are later exploited by malicious users.
        </p>

        <div class="sucuriscan-inline-alert-warning">
            <p>
                It is possible that the response time of your website gets affected after the
                activation of this option, this is due to the way Ajax works and how WordPress
                processes the requests. Keep this option disabled if you experience issues
                related with the load time of the external pages or the administration
                dashboard.
            </p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>XML HTTP Request Monitor is %%SUCURI.XhrMonitorStatus%%</span>

            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_xhr_monitor" value="%%SUCURI.XhrMonitorSwitchValue%%" />
                <button type="submit" class="button-primary %%SUCURI.XhrMonitorSwitchCssClass%%">
                    %%SUCURI.XhrMonitorSwitchText%%
                </button>
            </form>
        </div>
    </div>
</div>
