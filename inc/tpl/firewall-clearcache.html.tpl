
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.FirewallCacheTitle@@</h3>

    <div class="inside">
        <p>@@SUCURI.FirewallCacheInfo@@</p>

        <div class="sucuriscan-inline-alert-info">
            <p>@@SUCURI.FirewallCacheNote@@</p>
        </div>

        <p>@@SUCURI.FirewallCacheWiki@@</p>

        <form action="%%SUCURI.URL.Firewall%%#clearcache" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_clear_cache" value="1" />
            <input type="submit" value="@@SUCURI.FirewallCacheButton@@" class="button button-primary" />
        </form>
    </div>
</div>
