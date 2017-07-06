
<script type="text/javascript">
/* global jQuery */
/* jshint camelcase: false */
jQuery(document).ready(function ($) {
    $.post('%%SUCURI.AjaxURL.Firewall%%', {
        action: 'sucuriscan_ajax',
        sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
        form_action: 'firewall_settings',
    }, function (data) {
        if (data.ok) {
            var value;
            $('#firewall-settings-table tbody').html('');
            for (var name in data.settings) {
                if (data.settings.hasOwnProperty(name) &&
                    typeof data.settings[name] === 'string'
                ) {
                    value = data.settings[name];
                    $('#firewall-settings-table tbody').append('<tr>' +
                    '<td><label>' + name + '</label></td>' +
                    '<td><span class="sucuriscan-monospace">' +
                    value + '</span></td></tr>');
                }
            }
        } else {
            $('#firewall-settings-table tbody')
            .html('<tr><td colspan="2">' + data.error + '</td></tr>');
        }
    });
});
</script>

<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.FirewallSettingsTitle@@</h3>

    <div class="inside">
        <p>@@SUCURI.FirewallSettingsInfo@@</p>

        <div class="sucuriscan-inline-alert-info sucuriscan-%%SUCURI.Firewall.APIKeyFormVisibility%%">
            <p>@@SUCURI.FirewallAddKey@@</p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-firewall-apikey sucuriscan-%%SUCURI.Firewall.APIKeyVisibility%%">
            <strong>@@SUCURI.FirewallKey@@:</strong>
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

        <table class="wp-list-table widefat sucuriscan-table" id="firewall-settings-table">
            <thead>
                <tr>
                    <th>@@SUCURI.Name@@</th>
                    <th>@@SUCURI.Value@@</th>
                </tr>
            </thead>

            <tbody>
                <tr><td colspan="2">@@SUCURI.Loading@@</td></tr>
            </tbody>
        </table>

        <p>@@SUCURI.FirewallFootNote@@</p>
    </div>
</div>
