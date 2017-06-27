
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.APITimeout@@</h3>

    <div class="inside">
        <p>@@SUCURI.APITimeoutInfo@@</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>@@SUCURI.APITimeoutValue@@</span>
        </div>

        <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.APITimeoutLabel@@</label>
                <input type="text" name="sucuriscan_request_timeout" />
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>
        </form>
    </div>
</div>
