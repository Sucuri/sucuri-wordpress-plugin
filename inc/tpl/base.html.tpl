<script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */
    jQuery(document).ready(function ($) {
        $('#firewall-clear-cache-button').on('click', function (event) {
            event.preventDefault();

            var button = $(this);
            button.attr('disabled', true);
            button.html('{{Loading...}}');

            $('#firewall-clear-cache-response').html('');

            $.post('%%SUCURI.AjaxURL.Firewall%%', {
                action: 'sucuriscan_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action: 'firewall_clear_cache',
            }, function (data) {
                $('#firewall-clear-cache-response').html(data);

                button.html('{{Clear Firewall Cache}}');

                setTimeout(function() {
                    $('#firewall-clear-cache-response').html('');
                    button.attr('disabled', false);
                }, 5000);
            });
        });
    })
</script>

<div class="wrap sucuriscan-container">
    <h2 id="warnings_hook">
        <!-- Dynamically populated via JavaScript -->
    </h2>

    <div id="firewall-clear-cache-response" class="mt-2 mb-2"></div>

    %%%SUCURI.GenerateAPIKey.Modal%%%


    <div class="sucuriscan-header sucuriscan-clearfix">
        <div class="sucuriscan-pull-left sucuriscan-logo-wrapper">
            <a href="https://sucuri.net/signup" target="_blank" title="{{Sucuri Security}}" class="sucuriscan-logo">
                <img src="%%SUCURI.PluginURL%%/inc/images/%%SUCURI.SucuriLogo%%" alt="Sucuri Inc." />
            </a>

            <div class="sucuriscan-version-content">
                <span class="sucuriscan-subtitle">{{WP Plugin}}</span>
                <span class="sucuriscan-version">v%%SUCURI.PluginVersion%%</span>

                <a href="https://sucuri.net/website-firewall/" class="unlock-premium %%SUCURI.FreemiumVisibility%%" target="_blank">Unlock Premium</a>
            </div>
        </div>

        <div class="sucuriscan-pull-right sucuriscan-navbar">
            <ul>
                <li><button id="firewall-clear-cache-button" class="button button-primary %%SUCURI.PremiumVisibility%%">{{Clear Firewall Cache}}</button></li>

                <li><a href="https://support.sucuri.net/support/" class="button button-primary sucuriscan-%%SUCURI.DashboardButtonVisibility%%" target="_blank">{{Get Help}}</a></li>

                <li><a href="https://docs.sucuri.net/plugins/" class="button button-primary" target="_blank">{{Knowledge Base}}</a></li>

                <li><a href="https://sucuri.typeform.com/to/qNe18eDf" class="button button-primary" target="_blank">{{Feedback Survey}}</a></li>

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
