
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">WordPress Integrity (Language)</h3>

    <div class="inside">
        <p>
            The information necessary to check the WordPress integrity uses data
            obtained from the <a href="http://codex.wordpress.org/WordPress.org_API"
            target="_blank" rel="noopener">WordPress API</a>. It compares this data with the
            content of the files installed in your website. By default the API
            returns this data for the English version of WordPress. If your
            website is using a non-English version of the code you will have to
            specify the language to reduce the amount of false/positives.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>
                <b>NOTE:</b> Not all the languages are supported. If you notice
                a high amount of false/positives please consider to switch the
                option back to English and then mark the files that you consider
                are clean as such, they will be ignored by the scanner the next
                time it runs.
            </p>
        </div>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>WordPress Locale:</label>
                <select name="sucuriscan_set_language">
                    %%%SUCURI.Integrity.LanguageDropdown%%%
                </select>
                <button type="submit" class="button button-primary">Proceed</button>
                <span><em>(WordPress Locale %%SUCURI.Integrity.WordPressLocale%%)</em></span>
            </fieldset>
        </form>
    </div>
</div>
