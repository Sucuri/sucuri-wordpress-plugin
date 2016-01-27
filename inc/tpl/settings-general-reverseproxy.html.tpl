
<div class="postbox">
    <h3>Reverse Proxy and IP Address</h3>

    <div class="inside">
        <p>
            The event monitor uses the API address of the origin of the request to track the
            actions, the plugin uses two methods to retrieve this: the main method uses the
            global server variable <em>Remote-Addr</em> available in most modern web
            servers, an alternative method uses custom HTTP headers <em>(which are unsafe by
            default)</em>. You should not worry about this option unless you know what a
            reverse proxy is.
        </p>

        <p>
            Services like <a href="https://sucuri.net/website-firewall/" target="_blank">
            CloudProxy</a> once active forces the network traffic to pass through them to
            filter any security threat that may affect the original server. A side effect
            of this is that the real IP address is no longer available in the global server
            variable <em>Remote-Addr</em> but in a custom HTTP header with a name provided
            by the service. CloudProxy uses <em>"X-Sucuri-ClientIP"</em>, CloudFlare uses
            <em>"CF-Connecting-IP"</em>, others use <em>"X-Forwarded-For"</em>.
        </p>

        <div class="sucuriscan-inline-alert-warning">
            <p>
                When this option is enabled the plugin will go through a list of common HTTP
                headers to retrieve the real IP address of the origin of the requests. Note
                that this information can be spoofed and malicious people may use this to
                hide their real IP during an attack. You must not enable this option unless
                you are completely sure that your site is behind a proxy/firewall.
            </p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>Reverse Proxy Support is %%SUCURI.ReverseProxyStatus%%</span>

            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_revproxy" value="%%SUCURI.ReverseProxySwitchValue%%" />
                <button type="submit" class="button-primary %%SUCURI.ReverseProxySwitchCssClass%%">
                    %%SUCURI.ReverseProxySwitchText%%
                </button>
            </form>
        </div>
    </div>
</div>
