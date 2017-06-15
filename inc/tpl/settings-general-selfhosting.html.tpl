
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.SelfHosting@@</h3>

    <div class="inside">
        <p>@@SUCURI.SelfHostingInfo@@</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-%%SUCURI.SelfHosting.DisabledVisibility%%">
            <span>@@SUCURI.SelfHosting@@ &mdash; %%SUCURI.SelfHosting.Status%%</span>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-monitor-fpath sucuriscan-%%SUCURI.SelfHosting.FpathVisibility%%">
            <span class="sucuriscan-monospace">%%SUCURI.SelfHosting.Fpath%%</span>
            <form action="%%SUCURI.URL.Settings%%#general" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_selfhosting_fpath" />
                <button type="submit" class="button button-primary">%%SUCURI.SelfHosting.SwitchText%%</button>
            </form>
        </div>

        <form action="%%SUCURI.URL.Settings%%#general" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.FilePath@@:</label>
                <input type="text" name="sucuriscan_selfhosting_fpath" />
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>
        </form>
    </div>
</div>
