
<div class="wrap sucuriscan-container">
    <h2 id="warnings_hook">
        <!-- Dynamically populated via JavaScript -->
    </h2>

    %%%SUCURI.GenerateAPIKey.Modal%%%

    <div class="sucuriscan-header sucuriscan-clearfix">
        <div class="sucuriscan-pull-left sucuriscan-logo-wrapper">
            <a href="https://sucuri.net/signup" target="_blank" title="{{Sucuri Security}}" class="sucuriscan-logo">
                <img src="%%SUCURI.PluginURL%%/inc/images/pluginlogo.png" alt="Sucuri Inc." />
            </a>
            <span class="sucuriscan-subtitle">{{WP Plugin}}</span>
            <span class="sucuriscan-version">v%%SUCURI.PluginVersion%%</span>
        </div>

        <div class="sucuriscan-pull-right sucuriscan-navbar">
            <ul>
                <li>
                    <div class="sucuriscan-dropdown">
                        <a target="_blank" rel="noopener" class="button button-secondary">{{Help & More}}</a>

                        <div class="sucuriscan-dropdown-content">
                            <i class="sucuriscan-pointer"></i>
                            <a href="https://sucuri.net/guides/how-to-use-the-wordpress-security-plugin/" target="_blank">{{Help}}</a>
                            <a href="https://docs.sucuri.net/plugins/" target="_blank">{{Knowledge Base}}</a>
                            <a href="https://sucuri.typeform.com/to/qNe18eDf" target="_blank">{{Feedback Survey}}</a>
                        </div>
                    </div>
                </li>

                <li><a href="%%SUCURI.URL.Dashboard%%" class="button button-primary sucuriscan-%%SUCURI.DashboardButtonVisibility%%"">{{Dashboard}}</a></li>

                <li><a href="%%SUCURI.URL.Firewall%%" class="button button-primary sucuriscan-%%SUCURI.FirewallButtonVisibility%%" data-cy="sucuriscan-main-nav-firewall">{{Firewall (WAF)}}</a></li>

                <li><a href="%%SUCURI.URL.Settings%%" class="button button-primary sucuriscan-%%SUCURI.SettingsButtonVisibility%%">{{Settings}}</a></li>
            </ul>
        </div>
    </div>

    <div class="sucuriscan-clearfix sucuriscan-content sucuriscan-%%SUCURI.PageStyleClass%%">
        %%%SUCURI.PageContent%%%
    </div>

    <div class="sucuriscan-clearfix sucuriscan-footer">
        <div>{{Copyright}} &copy; %%SUCURI.Year%% {{Sucuri Inc. All Rights Reserved.}}</div>
    </div>
</div>
