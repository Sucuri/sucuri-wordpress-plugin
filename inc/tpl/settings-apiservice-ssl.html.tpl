
<div class="postbox">
    <h3>API Request and SSL</h3>

    <div class="inside">
        <p>
            SSL is a cryptographic protocols designed to provide communications security
            over a computer network. The primary goal of the TLS protocol <em>(and its
            predecessor SSL - Secure Sockets Layer)</em> is to provide privacy and data
            integrity between two communicating computer applications. When you have this
            option enabled <em>(by default)</em> it forces the plugin to send the HTTP
            requests to the API service via TLS.
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.VerifySSLCertCssClass%%">
            <span>%%SUCURI.VerifySSLCert%%</span>
        </div>

        <p>
            Either because the SSL certificate of the API service has expired or because the
            HTTP transport protocol offered by your hosting provider does not supports SSL
            you may want to deactivate this option, but be aware that <a target="_blank"
            href="https://en.wikipedia.org/wiki/Man-in-the-middle_attack">MITM attacks</a>
            can take advantage of this to steal information from your website.
        </p>

        <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <span class="sucuriscan-input-group">
                <label>SSL Certificate Verification:</label>
                <select name="sucuriscan_verify_ssl_cert">
                    %%%SUCURI.VerifySSLCertOptions%%%
                </select>
            </span>
            <button type="submit" class="button-primary">Proceed</button>
        </form>
    </div>
</div>
