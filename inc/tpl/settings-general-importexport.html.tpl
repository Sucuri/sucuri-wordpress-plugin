
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.ImportExport@@</h3>

    <div class="inside">
        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <p>@@SUCURI.ImportExportInfo@@</p>

            <textarea name="sucuriscan_settings" class="sucuriscan-full-textarea sucuriscan-monospace">%%SUCURI.Export%%</textarea>

            <p>
                <label>
                    <input type="hidden" name="sucuriscan_process_form" value="0" />
                    <input type="checkbox" name="sucuriscan_process_form" value="1" />
                    <span>@@SUCURI.UnderstandTheRisk@@</span>
                </label>
            </p>

            <button type="submit" name="sucuriscan_import" class="button button-primary">@@SUCURI.Submit@@</button>
        </form>
    </div>
</div>
