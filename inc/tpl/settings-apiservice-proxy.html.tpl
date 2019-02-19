
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{API Communication via Proxy}}</h3>

    <div class="inside">
        <p>{{All the HTTP requests used to communicate with the API service are being sent using the WordPress built-in functions, so (almost) all its official features are inherited, this is useful if you need to pass these HTTP requests through a proxy. According to the <a href="https://developer.wordpress.org/reference/classes/wp_http_proxy/" target="_blank" rel="noopener">official documentation</a> you have to add some constants to the main configuration file: <em>WP_PROXY_HOST, WP_PROXY_PORT, WP_PROXY_USERNAME, WP_PROXY_PASSWORD</em>.}}</p>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2 sucuriscan-monospace">
            <div>{{HTTP Proxy Hostname}}: %%SUCURI.APIProxy.Host%%</div>
            <div>{{HTTP Proxy Port num}}: %%SUCURI.APIProxy.Port%%</div>
            <div>{{HTTP Proxy Username}}: %%SUCURI.APIProxy.Username%%</div>
            <div>{{HTTP Proxy Password}}: <span class="sucuriscan-label-%%SUCURI.APIProxy.PasswordType%%">%%SUCURI.APIProxy.PasswordText%%</span></div>
        </div>
    </div>
</div>
