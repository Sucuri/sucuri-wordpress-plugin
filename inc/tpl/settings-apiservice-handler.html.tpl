
<div class="postbox">
    <h3>API Request Handler</h3>

    <div class="inside">
        <p>
            Select which interface will be used to send the HTTP requests to the
            external API services, the plugin will try to use the best option
            automatically and rescue the requests when any of the options is not
            available. Be sure to understand the purpose of this option before
            you try to modify it.
        </p>

        <form action="%%SUCURI.URL.Settings%%#apiservice" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <span class="sucuriscan-input-group">
                <label>HTTP Request Handler:</label>
                <select name="sucuriscan_api_handler">
                    %%%SUCURI.ApiHandlerOptions%%%
                </select>
            </span>
            <button type="submit" class="button-primary">Proceed</button>
        </form>
    </div>
</div>
