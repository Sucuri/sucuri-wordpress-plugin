
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
    <h3 class="sucuriscan-title">{{Firewall Settings}}</h3>

    <div class="inside">
        <p>{{A powerful Web Application Firewall and <b>Intrusion Detection System</b> for any WordPress user and many other platforms. This page will help you to configure and monitor your site through the <b>Sucuri Firewall</b>. Once enabled, our firewall will act as a shield, protecting your site from attacks and preventing malware infections and reinfections. It will block SQL injection attempts, brute force attacks, XSS, RFI, backdoors and many other threats against your site.}}</p>

        <div class="sucuriscan-inline-alert-info sucuriscan-%%SUCURI.Firewall.APIKeyFormVisibility%%">
            <p>{{Add your <a href="https://waf.sucuri.net/?settings&panel=api" target="_blank" rel="noopener">Firewall API key</a> in the form below to start communicating with the firewall API service.}}</p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-firewall-apikey sucuriscan-%%SUCURI.Firewall.APIKeyVisibility%%">
            <strong>{{Firewall API Key:}}</strong>
            <span class="sucuriscan-monospace">%%SUCURI.Firewall.APIKey%%</span>
            <form action="%%SUCURI.URL.Firewall%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <button type="submit" name="sucuriscan_delete_wafkey" data-cy="sucuriscan-delete-wafkey" class="button button-primary">{{Delete}}</button>
            </form>
        </div>

        <form action="%%SUCURI.URL.Firewall%%" method="post" class="sucuriscan-%%SUCURI.Firewall.APIKeyFormVisibility%%">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>{{Firewall API Key:}}</label>
                <input type="text" name="sucuriscan_cloudproxy_apikey" />
                <button type="submit" class="button button-primary" data-cy="sucuriscan-save-wafkey">{{Save}}</button>
            </fieldset>
            <br>
        </form>

        <table class="wp-list-table widefat sucuriscan-table" id="firewall-settings-table">
            <thead>
                <tr>
                    <th>{{Name}}</th>
                    <th>{{Value}}</th>
                </tr>
            </thead>

            <tbody>
                <tr><td colspan="2">{{Loading...}}</td></tr>
            </tbody>
        </table>

        <p>{{<em>[1]</em> More information about the <a href="https://sucuri.net/website-firewall/" target="_blank" rel="noopener">Sucuri Firewall</a>, features and pricing.<br><em>[2]</em> Instructions and videos in the official <a href="https://kb.sucuri.net/firewall" target="_blank" rel="noopener">Knowledge Base</a> site.<br><em>[3]</em> <a href="https://sucuri.net/website-security-platform/signup/" target="_blank" rel="noopener">Sign up</a> for a new account and start protecting your site.}}</p>
    </div>
</div>
