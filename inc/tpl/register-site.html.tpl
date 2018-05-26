
<p>An API key is required to activate some additional tools available in this plugin. The keys are free and you can virtually generate an unlimited number of them as long as the domain name and email address are unique. The key is used to authenticate the HTTP requests sent by the plugin to an API service managed by Sucuri Inc.</p>

<div class="sucuriscan-inline-alert-info">
    <p>If you experience issues generating the API key you can request one by sending the domain name and email address that you want to use to <a href="mailto:info@sucuri.net">info@sucuri.net</a>. Note that generating a key for a website that is not facing the Internet is not possible because the API service needs to validate that the domain name exists.</p>
</div>

<form action="%%SUCURI.URL.Settings%%" method="post">
    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
    <input type="hidden" name="sucuriscan_plugin_api_key" value="1" />

    <fieldset class="sucuriscan-clearfix">
        <label>Website:</label>
        <input type="text" value="%%SUCURI.CleanDomain%%" readonly="readonly">
    </fieldset>

    <fieldset class="sucuriscan-clearfix">
        <label>E-mail:</label>
        <select name="sucuriscan_setup_user">
            %%%SUCURI.AdminEmails%%%
        </select>
    </fieldset>

    <fieldset class="sucuriscan-clearfix">
        <label>DNS Lookups</label>
        <input type="hidden" name="sucuriscan_dns_lookups" value="disable" />
        <input type="checkbox" name="sucuriscan_dns_lookups" value="enable" checked="checked" />
        <span class="sucuriscan-tooltip" content="Check the box if your website is behind a known firewall service, this guarantees that the IP address of your visitors will be detected correctly for the security logs. You can change this later from the settings.">Enable DNS Lookups On Startup</span>
    </fieldset>

    <p>
        <label>
            <input type="hidden" name="sucuriscan_consent_tos" value="0" />
            <input type="checkbox" name="sucuriscan_consent_tos" value="1" />
            <span>I agree to the <a target="_blank" href="https://sucuri.net/terms">Terms of Service</a>.</span>
        </label>
    </p>

    <p>
        <label>
            <input type="hidden" name="sucuriscan_consent_priv" value="0" />
            <input type="checkbox" name="sucuriscan_consent_priv" value="1" />
            <span>I have read and understand the <a target="_blank" href="https://sucuri.net/privacy">Privacy Policy</a>.</span>
        </label>
    </p>

    <div class="sucuriscan-clearfix">
        <div class="sucuriscan-pull-left">
            <button type="submit" class="button button-primary">Submit</button>
        </div>
    </div>
</form>
