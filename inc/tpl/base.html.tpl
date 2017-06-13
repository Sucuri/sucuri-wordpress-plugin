
<div class="wrap sucuriscan-container">
    <h2 id="warnings_hook">
        <!-- Dynamically populated via JavaScript -->
    </h2>

    %%%SUCURI.GenerateAPIKey.Modal%%%

    <div class="sucuriscan-header sucuriscan-clearfix">
        <div class="sucuriscan-pull-left">
            <a href="https://sucuri.net/signup" target="_blank" title="Sucuri Security" class="sucuriscan-logo">
                <img src="%%SUCURI.SucuriURL%%/inc/images/pluginlogo.png" alt="Sucuri Inc." />
            </a>
            <span class="sucuriscan-subtitle">WP Plugin</span>
            <span class="sucuriscan-version">v%%SUCURI.PluginVersion%%</span>
        </div>

        <div class="sucuriscan-pull-right sucuriscan-navbar">
            <ul>
                <li><a href="https://goo.gl/aByqP5" target="_blank" rel="noopener" class="button button-secondary">@@SUCURI.Review@@</a></li>

                <li class="sucuriscan-%%SUCURI.GenerateAPIKey.Visibility%%">
                    <a href="#" class="button button-primary sucuriscan-modal-button sucuriscan-register-site-button"
                    data-modalid="sucuriscan-register-site">@@SUCURI.GenerateAPIKey@@</a>
                </li>

                <li><a href="%%SUCURI.URL.Dashboard%%" class="button button-primary">@@SUCURI.Dashboard@@</a></li>

                <li><a href="%%SUCURI.URL.Firewall%%" class="button button-primary">@@SUCURI.Firewall@@</a></li>

                <li><a href="%%SUCURI.URL.Settings%%" class="button button-primary">@@SUCURI.Settings@@</a></li>
            </ul>
        </div>
    </div>

    <div class="sucuriscan-clearfix sucuriscan-content sucuriscan-%%SUCURI.PageStyleClass%%">
        %%%SUCURI.PageContent%%%
    </div>

    <div class="sucuriscan-clearfix sucuriscan-footer">
        <div>@@SUCURI.Copyright@@</div>
    </div>
</div>
