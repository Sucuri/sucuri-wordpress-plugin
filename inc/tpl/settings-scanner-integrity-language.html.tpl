
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.IntegrityLanguageTitle@@</h3>

    <div class="inside">
        <p>@@SUCURI.IntegrityInfo@@</p>

        <div class="sucuriscan-inline-alert-info">
            <p>@@SUCURI.IntegrityNote@@</p>
        </div>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>WordPress Locale:</label>
                <select name="sucuriscan_set_language">
                    %%%SUCURI.Integrity.LanguageDropdown%%%
                </select>
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
                <span><em>(WordPress Locale %%SUCURI.Integrity.WordPressLocale%%)</em></span>
            </fieldset>
        </form>
    </div>
</div>
