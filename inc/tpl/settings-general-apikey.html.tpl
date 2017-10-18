
%%%SUCURI.ModalWhenAPIRegistered%%%

%%%SUCURI.ModalForApiKeyRecovery%%%

<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">API Key</h3>

    <div class="inside">
        <p>An API key is required to prevent attackers from deleting audit logs that can help you investigate and recover after a hack, and allows the plugin to display statistics. By generating an API key, you agree that Sucuri will collect and store anonymous data about your website. We take your privacy seriously.</p>

        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.InvalidDomainVisibility%%">
            <p>Your domain <code>%%SUCURI.CleanDomain%%</code> does not seems to have a DNS <code>A</code> record so it will be considered as <em>invalid</em> by the API interface when you request the generation of a new key. Adding <code>www</code> at the beginning of the domain name may fix this issue. If you do not understand what is this then send an email to our support team requesting the key.</p>
        </div>

        <div class="sucuriscan-%%SUCURI.APIKey.RecoverVisibility%%">
            <div class="sucuriscan-hstatus sucuriscan-hstatus-0">
                <div class="sucuriscan-monospace">API Key: %%SUCURI.APIKey%%</div>
                <form action="%%SUCURI.URL.Settings%%#general" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <button type="submit" name="sucuriscan_recover_key" class="button button-primary">Recover Via E-mail</button>
                    <a href="%%SUCURI.URL.Settings%%&recover#general" class="button button-primary">Manual Activation</a>
                </form>
            </div>

            <p>If you do not have access to the administrator email, you can reinstall the plugin. The API key is generated using an administrator email and the domain of the website. Click the "Manual Activation" button if you already have a valid API key to authenticate this website with the remote API web service.</p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-1 sucuriscan-%%SUCURI.APIKey.RemoveVisibility%%">
            <div class="sucuriscan-monospace">API Key: %%SUCURI.APIKey%%</div>
            <form action="%%SUCURI.URL.Settings%%#general" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <button type="submit" name="sucuriscan_remove_api_key" class="button button-primary">Delete</button>
            </form>
        </div>
    </div>
</div>
