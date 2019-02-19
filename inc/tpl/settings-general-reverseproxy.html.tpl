
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Reverse Proxy}}</h3>

    <div class="inside">
        <p>{{The event monitor uses the API address of the origin of the request to track the actions. The plugin uses two methods to retrieve this: the main method uses the global server variable <em>Remote-Addr</em> available in most modern web servers, and an alternative method uses custom HTTP headers <em>(which are unsafe by default)</em>. You should not worry about this option unless you know what a reverse proxy is. Services like the <a href="https://sucuri.net/website-firewall/" target="_blank" rel="noopener">Sucuri Firewall</a> &mdash; once active &mdash; force the network traffic to pass through them to filter any security threat that may affect the original server. A side effect of this is that the real IP address is no longer available in the global server variable <em>Remote-Addr</em> but in a custom HTTP header with a name provided by the service.}}</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>{{Reverse Proxy}} &mdash; %%SUCURI.ReverseProxyStatus%%</span>

            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_revproxy" value="%%SUCURI.ReverseProxySwitchValue%%" />
                <button type="submit" class="button button-primary">%%SUCURI.ReverseProxySwitchText%%</button>
            </form>
        </div>
    </div>
</div>
