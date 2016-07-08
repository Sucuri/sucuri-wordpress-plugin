
<div class="sucuriscan-panelstuff">
    <div class="postbox">
        <h3>Firewall Settings</h3>

        <div class="inside">
            <p>
                A powerful <b>WAF</b> <em>(Web Application Firewall)</em> and <b>Intrusion
                Prevention</b> system for any WordPress user and many other platforms. This page
                will help you to configure and monitor your site through <strong>Sucuri
                CloudProxy</strong>. Once enabled, our firewall will act as a shield, protecting
                your site from attacks and preventing malware infections and reinfections. It
                will block SQL injection attempts, brute force attacks, XSS, RFI, backdoors and
                many other threats against your site.
            </p>

            <div class="sucuriscan-inline-alert-info sucuriscan-%%SUCURI.Firewall.APIKeyFormVisibility%%">
                <p>
                    Add your <a href="https://waf.sucuri.net/?settings&panel=api" target="_blank">
                    CloudProxy API key</a> in the form below to start communicating with the firewall
                    API service.
                </p>
            </div>

            <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-firewall-apikey sucuriscan-%%SUCURI.Firewall.APIKeyVisibility%%">
                <span class="sucuriscan-monospace">%%SUCURI.Firewall.APIKey%%</span>
                <form action="%%SUCURI.URL.Firewall%%" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <button type="submit" name="sucuriscan_delete_wafkey" class="button-primary button-danger">Delete</button>
                </form>
            </div>

            <form action="%%SUCURI.URL.Firewall%%" method="post" class="sucuriscan-%%SUCURI.Firewall.APIKeyFormVisibility%%">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <span class="sucuriscan-input-group">
                    <label>CloudProxy API Key:</label>
                    <input type="text" name="sucuriscan_cloudproxy_apikey" class="input-text" />
                </span>
                <button type="submit" class="button-primary">Save</button>
            </form>

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-firewall-settings sucuriscan-%%SUCURI.Firewall.SettingsVisibility%%">
                <thead>
                    <tr>
                        <th>Setting Name</th>
                        <th>Setting Value</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.Firewall.SettingOptions%%%
                </tbody>
            </table>

            <p>
                <em>[1]</em> More information about <a href="https://sucuri.net/website-firewall/"
                target="_blank">CloudProxy</a>, features and pricing.<br>
                <em>[2]</em> Instructions and videos in the official <a href="https://kb.sucuri.net/cloudproxy"
                target="_blank">Knowledge Base</a> site.<br>
                <em>[3]</em> <a href="https://login.sucuri.net/signup2/create?CloudProxy" target="_blank">
                Sign up</a> for a new account and start protecting your site with CloudProxy.
            </p>
        </div>
    </div>
</div>
