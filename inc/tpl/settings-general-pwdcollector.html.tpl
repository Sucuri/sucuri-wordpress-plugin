
<div class="postbox">
    <h3>Failed Login Password Collector</h3>

    <div class="inside">
        <p>
            <b>Please do not enable this option</b> unless you understand the consequences.
            The plugin monitors all the user login attempts, when an user authentication
            succeeds it logs the event and sends an alert to the administrator if the option
            is enabled. Same thing happens for failed login attempts with two extra features:
            you can opt to send a summary of all the failed logins occured during the same
            hour and/or force the plugin to collect the password used in every authentication
            attempt to see if the attackers are getting close to your real password or not.
        </p>

        <div class="sucuriscan-inline-alert-warning">
            <p>
                You must be careful with this option as it will also log the attempts that you
                <em>(as a legitimate user)</em> send, if by mistake you mistype a character in
                the password the plugin will log this and it will be sent to the Sucuri servers.
                If a malicious user gets access to your API key or your security logs he/she will
                know the mistyped password and will use it to improve his attacks against your
                website.
            </p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>Failed Login Password Collector is %%SUCURI.PwdCollectorStatus%%</span>

            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_collect_wrong_passwords" value="%%SUCURI.PwdCollectorSwitchValue%%" />
                <button type="submit" class="button-primary %%SUCURI.PwdCollectorSwitchCssClass%%">
                    %%SUCURI.PwdCollectorSwitchText%%
                </button>
            </form>
        </div>
    </div>
</div>
