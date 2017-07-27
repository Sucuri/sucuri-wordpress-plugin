
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.ChecksumsAPI@@</h3>

    <div class="inside">
        <p>@@SUCURI.ChecksumsAPIInfo@@</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>@@SUCURI.ChecksumsAPI@@ &mdash; <a target="_blank"
            href="%%SUCURI.ChecksumsAPI%%">%%SUCURI.ChecksumsAPI%%</a>
            </span>
        </div>

        <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.ChecksumsAPI@@:</label>
                <input type="text" name="sucuriscan_checksum_api" placeholder="e.g. URL — or — user/repo" size="30" />
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>
        </form>
    </div>
</div>
