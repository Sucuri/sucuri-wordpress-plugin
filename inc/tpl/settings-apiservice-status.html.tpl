
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">API Service Communication</h3>

    <div class="inside">
        <p>Once the API key is generate the plugin will communicate with a remote API service that will act as a safe data storage for the audit logs generated when the website triggers certain events that the plugin monitors. If the website is hacked the attacker will not have access to these logs and that way you can investigate what was modified <em>(for malware infaction)</em> and/or how the malicious person was able to gain access to the website.</p>

        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.ApiStatus.ErrorVisibility%%">
            <p>Disabling the API service communication will stop the event monitoring, consider to enable the <a href="%%SUCURI.URL.Settings%%#general">Log Exporter</a> to keep the monitoring working while the HTTP requests are ignored, otherwise an attacker may execute an action that will not be registered in the security logs and you will not have a way to investigate the attack in the future.</p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.ApiStatus.StatusNum%%">
            <span>API Service Communication &mdash; %%SUCURI.ApiStatus.Status%% &mdash;</span>
            <span class="sucuriscan-monospace">%%SUCURI.ApiStatus.ServiceURL%%</span>
            <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_api_service" value="%%SUCURI.ApiStatus.SwitchValue%%" />
                <button type="submit" class="button button-primary">%%SUCURI.ApiStatus.SwitchText%%</button>
            </form>
        </div>
    </div>
</div>
