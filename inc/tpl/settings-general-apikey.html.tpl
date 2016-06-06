
%%%SUCURI.ModalWhenAPIRegistered%%%

%%%SUCURI.ModalForApiKeyRecovery%%%

<div class="postbox">
    <h3>Plugin API Key</h3>

    <div class="inside">
        <p>
            Most of the tools in this plugin can be used without a specific configuration,
            but the core features <strong>require an API key</strong> to communicate with
            the Sucuri services. The key is generated using your administrator e-mail and
            the domain of this site, this will allow you to have access to our free
            monitoring tool and other extra features.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>

                Generating an API key implies that you agree to send the information collected
                by the plugin to the Sucuri API service which is a remote server where the
                information for the audit logs is stored, this is to prevent malicious users to
                delete the logs during an attack which may affect an investigation if you
                suspect that your website was hacked. We also use this information to display <a
                href="https://sucuri.net/security-reports/brute-force/" target="_blank">statistics
                </a> and try to use the data in an anonymous way as we are concerned about your
                privacy too. Please do not generate an API key if you do not agree with this,
                you can keep using the plugin without it anyway.
            </p>
        </div>

        <div class="sucuriscan-inline-alert-warning sucuriscan-%%SUCURI.InvalidDomainVisibility%%">
            <p>
                Your domain <code>%%SUCURI.CleanDomain%%</code> does not seems to have a DNS
                <code>A</code> record so it will be considered as <em>invalid</em> by the API
                interface when you request the generation of a new key. Adding <code>www</code>
                at the beginning of the domain name may fix this issue. If you do not understand
                what is this then send an email to our support team requesting the key.
            </p>
        </div>

        <div class="sucuriscan-%%SUCURI.APIKey.RecoverVisibility%%">
            <div class="sucuriscan-hstatus sucuriscan-hstatus-0">
                <div class="sucuriscan-monospace">Plugin API Key: %%SUCURI.APIKey%%</div>
                <form action="%%SUCURI.URL.Settings%%" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <button type="submit" name="sucuriscan_recover_key" class="button-primary">Recover Via E-mail</button>
                </form>
            </div>

            <p>
                If you don't have access to the e-mail address used to generate the
                API key, but have a copy of the key at hand you can <a target="_self"
                href="%%SUCURI.URL.Settings%%&recover">click this link</a> to activate
                the plugin manually. Be aware that if the key is invalid the plugin will
                delete it afterwards.
            </p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-1 sucuriscan-%%SUCURI.APIKey.RemoveVisibility%%">
            <div class="sucuriscan-monospace">Plugin API Key: %%SUCURI.APIKey%%</div>
            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <button type="submit" name="sucuriscan_remove_api_key" class="button-primary button-danger">Remove</button>
            </form>
        </div>
    </div>
</div>
