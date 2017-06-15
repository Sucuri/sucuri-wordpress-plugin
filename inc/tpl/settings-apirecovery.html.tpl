
<div class="sucuriscan-clearfix">
    <p>@@SUCURI.APIKeyRecoveryExplanation@@</p>

    <p>@@SUCURI.APIKeyRecoveryPossibleFailures@@</p>

    <form action="%%SUCURI.URL.Settings%%" method="post">
        <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
        <fieldset class="sucuriscan-clearfix">
            <label>@@SUCURI.APIKey@@:</label>
            <input type="text" name="sucuriscan_manual_api_key" />
            <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
        </fieldset>
    </form>
</div>
