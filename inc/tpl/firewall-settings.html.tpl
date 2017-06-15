
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.FirewallSettingsTitle@@</h3>

    <div class="inside">
        <p>@@SUCURI.FirewallSettingsInfo@@</p>

        <div class="sucuriscan-inline-alert-info sucuriscan-%%SUCURI.Firewall.APIKeyFormVisibility%%">
            <p>@@SUCURI.FirewallAddKey@@</p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-firewall-apikey sucuriscan-%%SUCURI.Firewall.APIKeyVisibility%%">
            <span class="sucuriscan-monospace">%%SUCURI.Firewall.APIKey%%</span>
            <form action="%%SUCURI.URL.Firewall%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <button type="submit" name="sucuriscan_delete_wafkey" class="button button-primary">@@SUCURI.Delete@@</button>
            </form>
        </div>

        <form action="%%SUCURI.URL.Firewall%%" method="post" class="sucuriscan-%%SUCURI.Firewall.APIKeyFormVisibility%%">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.FirewallKey@@:</label>
                <input type="text" name="sucuriscan_cloudproxy_apikey" />
                <button type="submit" class="button button-primary">@@SUCURI.Save@@</button>
            </fieldset>
            <br>
        </form>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-firewall-settings sucuriscan-%%SUCURI.Firewall.SettingsVisibility%%">
            <thead>
                <tr>
                    <th>@@SUCURI.Name@@</th>
                    <th>@@SUCURI.Value@@</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.Firewall.SettingOptions%%%
            </tbody>
        </table>

        <p>@@SUCURI.FirewallFootNote@@</p>
    </div>
</div>
