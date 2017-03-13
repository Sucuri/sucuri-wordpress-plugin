
<p>
    An API key is required to activate some additional tools available in this
    plugin, the keys are free and you can virtually generate an unlimited number of
    them as long as the domain name and email address are different. The key is used
    to authenticate the HTTP requests sent by the plugin to a public API service
    managed by Sucuri Inc. Do not generate the key if you disagree with this.
</p>

<div class="sucuriscan-inline-alert-info">
    <p>
        If you experience issues generating the API key you can request one sending the
        domain name and email address that you want to use to <a href="mailto:info@sucuri.net">
        info@sucuri.net</a>. Note that setting a key in a development environment does
        not makes sense, if you are trying to do that in a local or stage environment
        please consider to dismiss this alert.
    </p>
</div>

<form action="%%SUCURI.URL.Settings%%" method="post">
    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
    <input type="hidden" name="sucuriscan_plugin_api_key" value="1" />

    <fieldset class="sucuriscan-clearfix">
        <label>Website:</label>
        <input type="text" value="%%SUCURI.CleanDomain%%" readonly="readonly">
    </fieldset>

    <fieldset class="sucuriscan-clearfix">
        <label>E-mail Address:</label>
        <select name="sucuriscan_setup_user">
            %%%SUCURI.AdminEmails%%%
        </select>
    </fieldset>

    <fieldset class="sucuriscan-clearfix">
        <label>DNS Lookups</label>
        <input type="hidden" name="sucuriscan_dns_lookups" value="disable" />
        <input type="checkbox" name="sucuriscan_dns_lookups" value="enable" checked="checked" />
        <span class="sucuriscan-tooltip" content="Check the box if your website is
        behind a firewall like CloudProxy, this guarantees that the IP address of
        your visitors will be detected correctly for the security logs. You can
        change this later from the settings.">Enable DNS Lookups On Startup</span>
    </fieldset>

    <div class="sucuriscan-clearfix">
        <div class="sucuriscan-pull-left">
            <button type="submit" class="button button-primary">Proceed</button>
        </div>
    </div>
</form>
