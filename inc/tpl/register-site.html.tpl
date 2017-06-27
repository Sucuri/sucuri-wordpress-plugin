
<p>@@SUCURI.APIKeyExplanation@@</p>

<div class="sucuriscan-inline-alert-info">
    <p>@@SUCURI.APIKeyHelp@@</p>
</div>

<form action="%%SUCURI.URL.Settings%%" method="post">
    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
    <input type="hidden" name="sucuriscan_plugin_api_key" value="1" />

    <fieldset class="sucuriscan-clearfix">
        <label>@@SUCURI.Website@@:</label>
        <input type="text" value="%%SUCURI.CleanDomain%%" readonly="readonly">
    </fieldset>

    <fieldset class="sucuriscan-clearfix">
        <label>@@SUCURI.Email@@:</label>
        <select name="sucuriscan_setup_user">
            %%%SUCURI.AdminEmails%%%
        </select>
    </fieldset>

    <fieldset class="sucuriscan-clearfix">
        <label>@@SUCURI.DNSLookups@@</label>
        <input type="hidden" name="sucuriscan_dns_lookups" value="disable" />
        <input type="checkbox" name="sucuriscan_dns_lookups" value="enable" checked="checked" />
        <span class="sucuriscan-tooltip" content="@@SUCURI.DNSLookupsText@@">@@SUCURI.DNSLookupsLabel@@</span>
    </fieldset>

    <div class="sucuriscan-clearfix">
        <div class="sucuriscan-pull-left">
            <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
        </div>
    </div>
</form>
