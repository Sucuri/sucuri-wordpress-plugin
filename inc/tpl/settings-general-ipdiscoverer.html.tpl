
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.IPDiscoverer@@</h3>

    <div class="inside">
        <p>@@SUCURI.IPDiscovererInfo@@</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span>@@SUCURI.IPDiscoverer@@ &mdash; %%SUCURI.DnsLookupsStatus%%</span>

            <form action="%%SUCURI.URL.Settings%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_dns_lookups" value="%%SUCURI.DnsLookupsSwitchValue%%" />
                <button type="submit" class="button button-primary">%%SUCURI.DnsLookupsSwitchText%%</button>
            </form>
        </div>

        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.HTTPHeader@@:</label>
                <select name="sucuriscan_addr_header">
                    %%%SUCURI.AddrHeaderOptions%%%
                </select>
                <button type="submit" class="button button-primary">Proceed</button>
            </fieldset>

            <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-monospace">
                <div>Sucuri Firewall &mdash; %%SUCURI.IsUsingFirewall%%</div>
                <div>@@SUCURI.Website@@: %%SUCURI.WebsiteURL%%</div>
                <div>Top Level Domain: %%SUCURI.TopLevelDomain%%</div>
                <div>@@SUCURI.Hostname@@: %%SUCURI.WebsiteHostName%%</div>
                <div>@@SUCURI.RemoteAddr@@ (@@SUCURI.Hostname@@): %%SUCURI.WebsiteHostAddress%%</div>
                <div>@@SUCURI.RemoteAddr@@ (@@SUCURI.Username@@): %%SUCURI.RemoteAddress%% (%%SUCURI.RemoteAddressHeader%%)</div>
            </div>
        </form>
    </div>
</div>
