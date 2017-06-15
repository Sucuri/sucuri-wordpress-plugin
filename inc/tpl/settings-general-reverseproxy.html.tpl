
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.ReverseProxy@@</h3>

    <div class="inside">
        <p>@@SUCURI.ReverseProxyInfo@@</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>@@SUCURI.ReverseProxy@@ &mdash; %%SUCURI.ReverseProxyStatus%%</span>

            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_revproxy" value="%%SUCURI.ReverseProxySwitchValue%%" />
                <button type="submit" class="button button-primary">%%SUCURI.ReverseProxySwitchText%%</button>
            </form>
        </div>
    </div>
</div>
