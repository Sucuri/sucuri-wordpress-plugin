
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.SecretKeys@@</h3>

    <div class="inside">
        <p>@@SUCURI.SecretKeysInfo@@</p>

        <div class="sucuriscan-inline-alert-error">
            <p>@@SUCURI.SecretKeysExpiration@@</p>
        </div>

        <div class="sucuriscan_wpconfig_keys_updated sucuriscan-monospace sucuriscan-%%SUCURI.WPConfigUpdate.Visibility%%">
            <textarea class="sucuriscan-full-textarea" style="height:405px">%%SUCURI.WPConfigUpdate.NewConfig%%</textarea>
        </div>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-security-keys-table">
            <thead>
                <tr>
                    <th>@@SUCURI.Status@@</th>
                    <th>@@SUCURI.Name@@</th>
                    <th>@@SUCURI.Value@@</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.SecurityKeys.List%%%
            </tbody>
        </table>

        <form action="%%SUCURI.URL.Settings%%#posthack" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_update_wpconfig" value="1" />

            <p>
                <label>
                    <input type="hidden" name="sucuriscan_process_form" value="0" />
                    <input type="checkbox" name="sucuriscan_process_form" value="1" />
                    <span>@@SUCURI.UnderstandTheRisk@@</span>
                </label>
            </p>

            <input type="submit" value="@@SUCURI.SecretKeysGenerate@@" class="button button-primary" />
        </form>
    </div>
</div>
