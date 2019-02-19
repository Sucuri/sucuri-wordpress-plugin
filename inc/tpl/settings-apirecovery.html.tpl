
<div class="sucuriscan-clearfix">
    <p>{{If this operation was successful you will receive a message in the email used during the registration of the API key <em>(usually the email of the main admin user)</em>. This message contains the key in plain text, copy and paste the key in the form field below. The plugin will verify the authenticity of the key sending an initial HTTP request to the API service, if this fails the key will be removed automatically and you will have to start the process all over again.}}</p>

    <p>{{There are cases where this operation may fail, an example would be when the email address is not associated with the domain anymore, this happens when the base URL changes <em>(from www to none or viceversa)</em>. If you are having issues recovering the key please send an email explaining the situation to <a href="mailto:info@sucuri.net">info@sucuri.net</a>}}</p>

    <form action="%%SUCURI.URL.Settings%%" method="post">
        <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
        <fieldset class="sucuriscan-clearfix">
            <label>{{API Key:}}</label>
            <input type="text" name="sucuriscan_manual_api_key" />
            <button type="submit" class="button button-primary">{{Submit}}</button>
        </fieldset>
    </form>
</div>
