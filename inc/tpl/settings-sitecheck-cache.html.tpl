
<div class="postbox">
    <h3>Malware Scanner Cache</h3>

    <div class="inside">
        <p>
            SiteCheck caches by default the results of every scan to reduce the bandwidth
            consumption and to make the subsequent scans faster, if you make modifications
            to your website and want to execute a fresh scan you will have to wait
            <strong>%%SUCURI.SiteCheck.CacheLifeTime%% seconds</strong>. Alternatively, you
            can reset the cache and request a fresh scan immediately.
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>Malware Scanner Cache: %%SUCURI.SiteCheck.CacheSize%% of data</span>
            <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_sitecheck_cache" value="1" />
                <button type="submit" class="button-primary">Reset Cache</button>
            </form>
        </div>
    </div>
</div>
