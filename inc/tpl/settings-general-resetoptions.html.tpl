
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.Uninstall@@</h3>

    <div class="inside">
        <p>@@SUCURI.UninstallInfo@@</p>

        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <p>
                <label>
                    <input type="hidden" name="sucuriscan_process_form" value="0" />
                    <input type="checkbox" name="sucuriscan_process_form" value="1" />
                    <span>@@SUCURI.UnderstandTheRisk@@</span>
                </label>
            </p>
            <button type="submit" name="sucuriscan_reset_options" class="button button-primary">@@SUCURI.Submit@@</button>
        </form>
    </div>
</div>
