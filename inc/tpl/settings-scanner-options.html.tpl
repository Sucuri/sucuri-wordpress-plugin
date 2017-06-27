
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.ScannerTitle@@</h3>

    <div class="inside">
        <p>@@SUCURI.ScannerDescription@@</p>

        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.NoSPL.Visibility%%">
            <p>@@SUCURI.ScannerWithoutSPL@@</p>
        </div>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.ScannerFrequency@@</label>
                <select name="sucuriscan_scan_frequency">
                    %%%SUCURI.ScanningFrequencyOptions%%%
                </select>
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>
        </form>
    </div>
</div>
