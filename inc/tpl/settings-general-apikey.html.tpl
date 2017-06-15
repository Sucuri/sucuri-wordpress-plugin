
%%%SUCURI.ModalWhenAPIRegistered%%%

%%%SUCURI.ModalForApiKeyRecovery%%%

<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.APIKey@@</h3>

    <div class="inside">
        <p>@@SUCURI.APIKeyInfo@@</p>

        <div class="sucuriscan-inline-alert-info">
            <p>@@SUCURI.APIKeyTerms@@</p>
        </div>

        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.InvalidDomainVisibility%%">
            <p>@@SUCURI.APIKeyInvalidDomain@@</p>
        </div>

        <div class="sucuriscan-%%SUCURI.APIKey.RecoverVisibility%%">
            <div class="sucuriscan-hstatus sucuriscan-hstatus-0">
                <div class="sucuriscan-monospace">@@SUCURI.APIKey@@: %%SUCURI.APIKey%%</div>
                <form action="%%SUCURI.URL.Settings%%" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <button type="submit" name="sucuriscan_recover_key" class="button button-primary">@@SUCURI.APIKeyRecoverButton@@</button>
                </form>
            </div>

            <p>@@SUCURI.APIKeyRecoveryCondition@@</p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-1 sucuriscan-%%SUCURI.APIKey.RemoveVisibility%%">
            <div class="sucuriscan-monospace">@@SUCURI.APIKey@@: %%SUCURI.APIKey%%</div>
            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <button type="submit" name="sucuriscan_remove_api_key" class="button button-primary">@@SUCURI.Delete@@</button>
            </form>
        </div>
    </div>
</div>
