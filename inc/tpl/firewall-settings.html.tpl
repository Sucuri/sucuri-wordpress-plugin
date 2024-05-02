
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

    jQuery(document).ready(function($) {
        $('#sucuriscan_toggle_wafkey').click(function(e) {
            e.preventDefault();

            var apiKey = $('#sucuriscan_waf_key');
            var isHidden = apiKey.text().startsWith('*');

            if (isHidden) {
                apiKey.text(apiKey.data('key'));
                $(this).text('Hide');
            } else {
                apiKey.text('*******************************************************');
                $(this).text('Show');
            }
        });
    });

    jQuery(document).ready(function($) {
        $('#sucuriscan-waf-key-options option[value="update"]').click(function() {
            var form = $('#sucuriscan-waf-key-form');

            form.attr('class', 'sucuriscan-visible');
            form.find('input[name="sucuriscan_cloudproxy_apikey"]').val($('#sucuriscan_waf_key').data('key'));

            $('.sucuriscan-firewall-apikey').attr('class', 'sucuriscan-hidden');
        });

        $('#sucuriscan-waf-key-options option[value="delete"]').click(function() {
            $('input[name="sucuriscan_delete_wafkey"]').val('1');

            $(this).closest('form').submit();
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

        <div id="sucuriscan-waf-key-box" class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-firewall-apikey sucuriscan-%%SUCURI.Firewall.APIKeyVisibility%%">
            <strong>{{Firewall API Key:}}</strong>
            <span id="sucuriscan_waf_key" class="sucuriscan-monospace" data-key="%%SUCURI.Firewall.APIKey%%">*******************************************************</span>
            <button id="sucuriscan_toggle_wafkey" name="sucuriscan_toggle_wafkey" data-cy="sucuriscan-toggle-wafkey" class="button button-primary">{{Show}}</button>
            <form action="%%SUCURI.URL.Firewall%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_delete_wafkey" value="" />

                <div class="sucuriscan-dropdown">
                    <a target="_blank" rel="noopener" class="button button-secondary">Options</a>

                    <div id="sucuriscan-waf-key-options" class="sucuriscan-dropdown-content sucuriscan-dropdown-content-sm">
                        <i class="sucuriscan-pointer"></i>
                        <option value="update">Update</option>
                        <option value="delete">Delete</option>
                    </div>
                </div>
            </form>
        </div>

        <form id="sucuriscan-waf-key-form" action="%%SUCURI.URL.Firewall%%" method="post" class="sucuriscan-%%SUCURI.Firewall.APIKeyFormVisibility%%">
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
