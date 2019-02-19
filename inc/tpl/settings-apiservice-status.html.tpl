
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{API Service Communication}}</h3>

    <div class="inside">
        <p>{{Once the API key is generate the plugin will communicate with a remote API service that will act as a safe data storage for the audit logs generated when the website triggers certain events that the plugin monitors. If the website is hacked the attacker will not have access to these logs and that way you can investigate what was modified <em>(for malware infaction)</em> and/or how the malicious person was able to gain access to the website.}}</p>

        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.ApiStatus.ErrorVisibility%%">
            <p>{{Disabling the API service communication will stop the event monitoring, consider to enable the <a href="%%SUCURI.URL.Settings%%#general">Log Exporter</a> to keep the monitoring working while the HTTP requests are ignored, otherwise an attacker may execute an action that will not be registered in the security logs and you will not have a way to investigate the attack in the future.}}</p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.ApiStatus.StatusNum%%">
            <span>{{API Service Communication}} &mdash; %%SUCURI.ApiStatus.Status%% &mdash;</span>
            <span class="sucuriscan-monospace">%%SUCURI.ApiStatus.ServiceURL%%</span>
            <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_api_service" value="%%SUCURI.ApiStatus.SwitchValue%%" />
                <button type="submit" class="button button-primary">%%SUCURI.ApiStatus.SwitchText%%</button>
            </form>
        </div>

        <p>
            {{<strong>Are you a developer?</strong> You may be interested in our API. Feel free to use the URL shown below to access the latest 50 entries in your security log, change the value for the parameter <code>l=N</code> if you need more. Be aware that the API doesn't provides an offset parameter, so if you have the intension to query specific sections of the log you will need to wrap the HTTP request around your own cache mechanism. We <strong>DO NOT</strong> take feature requests for the API, this is a semi-private service tailored for the specific needs of the plugin and not intended to be used by 3rd-party apps, we may change the behavior of each API endpoint without previous notice, use it at your own risk.}}
        </p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-monospace">
            <span>curl -s "https://wordpress.sucuri.net/api/?k=%%SUCURI.ApiStatus.ApiKey%%&a=get_logs&l=50" | python -m json.tool</span>
        </div>
    </div>
</div>
