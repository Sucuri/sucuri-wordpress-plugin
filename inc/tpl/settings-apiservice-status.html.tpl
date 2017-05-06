
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">API Service Communication</h3>

    <div class="inside">
        <p>
            Once the API key is generate the plugin will communicate with a remote API
            service that will act as a safe data storage for the audit logs generated when
            the website triggers certain events that the plugin monitors. If the website is
            hacked the attacker will not have access to these logs and that way you can
            investigate what was modified <em>(for malware infaction)</em> and/or how the
            malicious person was able to gain access to the website.
        </p>

        <div class="sucuriscan-inline-alert-warning sucuriscan-%%SUCURI.ApiStatus.WarningVisibility%%">
            <p>
                The latency of the HTTP requests may slow down the website depending on the
                location of the server that is hosting it. Additionally, if the API goes down
                the plugin will throw warnings that may affect your workflow, in this case you
                may want to stop the communication with the API service to keep the latency at
                zero and be able to continue working in the website without interruptions.
            </p>
        </div>

        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.ApiStatus.ErrorVisibility%%">
            <p>
                Disabling the API service communication will stop the event monitoring, consider
                to enable the <a href="%%SUCURI.URL.Settings%%#general">Log Exporter</a> to
                keep the monitoring working while the HTTP requests are ignored, otherwise an
                attacker may execute an action that will not be registered in the security logs
                and you will not have a way to investigate the attack in the future.
            </p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.ApiStatus.StatusNum%%">
            <span>API Service Communication is %%SUCURI.ApiStatus.Status%%</span>
            &mdash;
            <span class="sucuriscan-monospace">%%SUCURI.ApiStatus.ServiceURL%%</span>
            <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_api_service" value="%%SUCURI.ApiStatus.SwitchValue%%" />
                <button type="submit" class="button button-primary">%%SUCURI.ApiStatus.SwitchText%%</button>
            </form>
        </div>

        <p>
            Select which interface will be used to send the HTTP requests to the
            external API services, the plugin will try to use the best option
            automatically and rescue the requests when any of the options is not
            available. Be sure to understand the purpose of this option before
            you try to modify it.
        </p>

        <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>HTTP Request Handler:</label>
                <select name="sucuriscan_api_handler">
                    %%%SUCURI.ApiHandlerOptions%%%
                </select>
                <button type="submit" class="button button-primary">Proceed</button>
            </fieldset>
        </form>
    </div>
</div>
