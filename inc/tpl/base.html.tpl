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

                setTimeout(function () {
                    $('#firewall-clear-cache-response').html('');
                    button.attr('disabled', false);
                }, 5000);
            });
        });

        var SucuriThemeManager = {
            init: function () {
                this.createToggle();
                this.bindEvents();
                this.updateToggleState();
            },

            toggleTheme: function (theme) {
                var $link = $('#sucuriscan-css');

                if (!$link.length) { return; }

                var href = $link.attr('href')
                    .replace(/\/(dark|light)\.css/i, '/' + theme + '.css');

                $link.attr('href', href);

                var $logo = $('.sucuriscan-logo img');

                if (!$logo.length) { return; }

                $logo.attr(
                    'src',
                    $logo.attr('src').replace(
                        /pluginlogo(?:-darktheme)?\.png/i,
                        theme === 'dark' ? 'pluginlogo-darktheme.png' : 'pluginlogo.png'
                    )
                );

                this.updateToggleState(theme);
            },

            createToggle: function () {
                var currentTheme = '%%%SUCURI.Theme%%%';

                var toggleHtml = `
                    <div class="sucuriscan-theme-toggle-container">
                        <button class="sucuriscan-theme-toggle"
                                id="sucuriscan-toggle-theme"
                                data-theme="${currentTheme}"
                                aria-label="Toggle dark mode"
                                title="Toggle between light and dark mode">
                            <div class="sucuriscan-toggle-icons">
                                <div class="sucuriscan-icon-container sucuriscan-moon-container">
                                    <svg class="sucuriscan-toggle-icon" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/>
                                    </svg>
                                </div>
                                <div class="sucuriscan-icon-container sucuriscan-sun-container">
                                    <svg class="sucuriscan-toggle-icon" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7.05a1,1,0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1,0,0,0,.7-.29l.71-.71a1,1,0,1,0-1.41-1.41L17,5.64a1,1,0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="sucuriscan-toggle-circle">
                                <svg class="sucuriscan-circle-icon" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/>
                                </svg>
                            </div>
                        </button>
                    </div>
                `;

                $('#sucuriscan-theme-toggle-placeholder').html(toggleHtml);
            },

            updateToggleState: function (theme) {
                theme = theme || $('#sucuriscan-toggle-theme').attr('data-theme');
                var $toggle = $('#sucuriscan-toggle-theme');
                var $circleIcon = $('.sucuriscan-circle-icon');

                $toggle.attr('data-theme', theme);

                if (theme === 'dark') {
                    $toggle.addClass('sucuriscan-theme-dark');
                    $circleIcon.html(`
                        <path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7.05a1,1,0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1,0,0,0,.7-.29l.71-.71a1,1,0,1,0-1.41-1.41L17,5.64a1,1,0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/>
                    `);
                } else {
                    $toggle.removeClass('sucuriscan-theme-dark');
                    $circleIcon.html(`
                        <path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/>
                    `);
                }
            },

            bindEvents: function () {
                var self = this;

                $(document).on('click', '#sucuriscan-toggle-theme', function (event) {
                    event.preventDefault();

                    var $btn = $(this);
                    var currentTheme = $btn.attr('data-theme');
                    var newTheme = currentTheme === 'dark' ? 'light' : 'dark';

                    $.post('%%SUCURI.AjaxURL.Firewall%%', {
                        action: 'sucuriscan_ajax',
                        sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                        form_action: 'toggle_theme',
                    }, function (data) {
                        $('#firewall-clear-cache-response').html(data);
                        self.toggleTheme(newTheme);
                    });
                });
            }
        };

        // Initialize the theme manager
        SucuriThemeManager.init();
    });
</script>

<div class="wrap sucuriscan-container">
    <h2 id="warnings_hook">
        <!-- Dynamically populated via JavaScript -->
    </h2>

    <div id="firewall-clear-cache-response" class="mt-2 mb-2"></div>

    %%%SUCURI.GenerateAPIKey.Modal%%%

    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            function setWafDismissCookie() {
                try {
                    var date = new Date();
                    date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000)); // 1 year!
                    var expires = '; expires=' + date.toUTCString();
                    var secure = (window.location.protocol === 'https:') ? '; Secure' : '';
                    var samesite = '; SameSite=Lax';
                    document.cookie = 'sucuriscan_waf_dismissed=1' + expires + '; path=/' + samesite + secure;
                } catch (e) { }
            }

            function removeWafModal() {
                try {
                    var overlay = document.querySelector('.sucuriscan-overlay.sucuriscan-activate-your-waf-key-modal');
                    var modal = document.querySelector('.sucuriscan-modal.sucuriscan-activate-your-waf-key-modal');
                    if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
                    if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
                } catch (error) { console.warn(error); }
            }

            $(document).on('click', '[data-cy="sucuriscan-waf-modal-main-action"]', function () {
                setWafDismissCookie();
                removeWafModal();
            });

            $(document).on('click', '.sucuriscan-modal-close[data-cy="sucuriscan-waf-modal-dismiss"]', function (event) {
                event.preventDefault();
                setWafDismissCookie();
                removeWafModal();
            });

            $(document).on('click', '.sucuriscan-overlay.sucuriscan-activate-your-waf-key-modal, .sucuriscan-activate-your-waf-key-modal .sucuriscan-modal-close', function () {
                setWafDismissCookie();
                removeWafModal();
            });
        });
    </script>

    <div class="sucuriscan-header sucuriscan-clearfix">
        <div class="sucuriscan-pull-left sucuriscan-logo-wrapper">
            <a href="https://sucuri.net/signup" target="_blank" title="{{Sucuri Security}}" class="sucuriscan-logo">
                <img src="%%SUCURI.PluginURL%%/inc/images/%%SUCURI.SucuriLogo%%" alt="Sucuri Inc." />
            </a>

            <div class="sucuriscan-version-content">
                <span class="sucuriscan-subtitle">{{WP Plugin}}</span>
                <span class="sucuriscan-version">v%%SUCURI.PluginVersion%%</span>

                <a href="https://sucuri.net/website-firewall/" class="unlock-premium %%SUCURI.FreemiumVisibility%%"
                    target="_blank">Unlock Premium</a>
            </div>
        </div>

        <div id="sucuriscan-theme-toggle-wrapper" class="sucuriscan-pull-right %%SUCURI.PremiumVisibility%%">
            <div id="sucuriscan-theme-toggle-placeholder"></div>
        </div>

        <div class="sucuriscan-pull-right sucuriscan-navbar">
            <ul>
                <li><button id="firewall-clear-cache-button"
                        class="button button-primary %%SUCURI.PremiumVisibility%%">{{Clear Firewall Cache}}</button>
                </li>

                <li><a href="https://support.sucuri.net/support/"
                        class="button button-primary sucuriscan-%%SUCURI.DashboardButtonVisibility%%"
                        target="_blank">{{Get Help}}</a></li>

                <li><a href="https://docs.sucuri.net/plugins/" class="button button-primary" target="_blank">
                        {{Knowledge Base}}
                    </a></li>

                <li><a href="https://sucuri.typeform.com/to/qNe18eDf" class="button button-primary"
                        target="_blank">{{Feedback Survey}}</a></li>

                <li><a href="%%SUCURI.URL.Dashboard%%"
                        class="button button-primary sucuriscan-%%SUCURI.DashboardButtonVisibility%%"">{{Dashboard}}</a></li>

                <li><a href=" %%SUCURI.URL.Firewall%%"
                        class="button button-primary sucuriscan-%%SUCURI.FirewallButtonVisibility%%"
                        data-cy="sucuriscan-main-nav-firewall">{{Firewall (WAF)}}</a></li>

                <li><a href="%%SUCURI.URL.Settings%%"
                        class="button button-primary sucuriscan-%%SUCURI.SettingsButtonVisibility%%">{{Settings}}</a>
                </li>
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