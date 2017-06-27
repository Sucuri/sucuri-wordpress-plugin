
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.APICommunication@@</h3>

    <div class="inside">
        <p>@@SUCURI.APICommunicationInfo@@</p>

        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.ApiStatus.ErrorVisibility%%">
            <p>@@SUCURI.APICommunicationDisabled@@</p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.ApiStatus.StatusNum%%">
            <span>@@SUCURI.APICommunication@@ &mdash; %%SUCURI.ApiStatus.Status%% &mdash;</span>
            <span class="sucuriscan-monospace">%%SUCURI.ApiStatus.ServiceURL%%</span>
            <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_api_service" value="%%SUCURI.ApiStatus.SwitchValue%%" />
                <button type="submit" class="button button-primary">%%SUCURI.ApiStatus.SwitchText%%</button>
            </form>
        </div>
    </div>
</div>
