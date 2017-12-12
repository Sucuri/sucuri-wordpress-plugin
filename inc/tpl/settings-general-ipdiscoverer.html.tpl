
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">IP Address Discoverer</h3>

    <div class="inside">
        <p>IP address discoverer will use DNS lookups to automatically detect if the website is behind the <a href="https://sucuri.net/website-firewall/" target="_blank" rel="noopener">Sucuri Firewall</a> in which case will modify the global server variable <em>Remote-Addr</em> to set the real IP of the website's visitors. This check runs on every WordPress init action and that is why it may slow down your website as some hosting providers rely on slow DNS servers which makes the operation take more time than it should.</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>IP Address Discoverer &mdash; %%SUCURI.DnsLookupsStatus%%</span>

            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_dns_lookups" value="%%SUCURI.DnsLookupsSwitchValue%%" />
                <button type="submit" class="button button-primary">%%SUCURI.DnsLookupsSwitchText%%</button>
            </form>
        </div>

        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <fieldset class="sucuriscan-clearfix">
                <label>HTTP Header:</label>
                <select name="sucuriscan_addr_header">
                    %%%SUCURI.AddrHeaderOptions%%%
                </select>
                <button type="submit" class="button button-primary">Proceed</button>
            </fieldset>

            <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-monospace">
                <div>Sucuri Firewall &mdash; %%SUCURI.IsUsingFirewall%%</div>
                <div>Website: %%SUCURI.WebsiteURL%%</div>
                <div>Top Level Domain: %%SUCURI.TopLevelDomain%%</div>
                <div>Hostname: %%SUCURI.WebsiteHostName%%</div>
                <div>IP Address (Hostname): %%SUCURI.WebsiteHostAddress%%</div>
                <div>IP Address (Username): %%SUCURI.RemoteAddress%% (%%SUCURI.RemoteAddressHeader%%)</div>
            </div>
        </form>
    </div>
</div>
