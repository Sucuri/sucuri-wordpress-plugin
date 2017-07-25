
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.SiteCheckTarget@@</h3>

    <div class="inside">
        <p>@@SUCURI.SiteCheckTargetInfo@@</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>@@SUCURI.SiteCheckTarget@@ &mdash; <a target="_blank"
            href="https://sitecheck.sucuri.net/results/%%SUCURI.SiteCheck.Target%%">
            https://sitecheck.sucuri.net/results/%%SUCURI.SiteCheck.Target%%</a>
            </span>
        </div>

        <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.SiteCheckTarget@@:</label>
                <input type="text" name="sucuriscan_sitecheck_target" />
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>
        </form>
    </div>
</div>
