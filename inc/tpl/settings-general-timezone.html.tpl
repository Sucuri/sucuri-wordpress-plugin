
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.TimezoneTitle@@</h3>

    <div class="inside">
        <p>@@SUCURI.TimezoneInfo@@</p>

        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.TimezoneTitle@@:</label>
                <select name="sucuriscan_timezone">
                    %%%SUCURI.Timezone.Dropdown%%%
                </select>
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
                <span><em>(%%SUCURI.Timezone.Example%%)</em></span>
            </fieldset>
        </form>
    </div>
</div>
