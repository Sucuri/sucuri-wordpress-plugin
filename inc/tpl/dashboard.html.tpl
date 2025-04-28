%%%SUCURI.Integrity%%%

<script type="text/javascript">
    jQuery(function ($) {
        function sucuriscanSiteCheckLinks(target, links) {
            if (!Array.isArray(links) || links.length === 0) {
                $(target).html('<div><em>{{No data available}}</em></div>');
                return;
            }

            var $tbody = $('<tbody>');

            $.each(links, function (_, href) {
                var $a = $('<a>', {
                    href:   href,
                    target: '_blank',
                    rel:    'noopener noreferrer',
                    class:  'sucuriscan-monospace',
                    text:   href
                });
                $tbody.append($('<tr>').append($('<td>').append($a)));
            });

            $(target).html(
                $('<table>', {class: 'wp-list-table widefat sucuriscan-table'}).html($tbody)
            );
        }

        $.post(
            '%%SUCURI.AjaxURL.Dashboard%%',
            {
                action:                      'sucuriscan_ajax',
                sucuriscan_page_nonce:       '%%SUCURI.PageNonce%%',
                sucuriscan_sitecheck_refresh:'%%SUCURI.SiteCheck.Refresh%%',
                form_action:                 'malware_scan'
            },
            function (data) {
                var dataMalware = data.malware ? data.malware : 'SiteCheck error: Failed to fetch remote scanner data.';
                var dataBlocklist = data.blocklist ? data.blocklist : 'SiteCheck error: Failed to fetch remote scanner data.';

                $('#sucuriscan-malware').html(dataMalware);
                $('#sucuriscan-blocklist').html(dataBlocklist);
                $('#sucuriscan-recommendations').html(data.recommendations);
            }
        );
    });

    jQuery(function ($) {
        /**
         * HTML‑escape helper.  We leave "<", ">", "=" untouched because they are
         * often part of version strings (e.g. "PHP 7.4 < 7.4.1").
         * They are not a script‑injection vector by themselves, yet we still
         * encode &, ", ', ` for safety.
         */
        function esc(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/`/g, '&#96;')
                .replace(/\//g, '&#x2F;')
                .replace(/=/g, '&#x3D;');
        }

        function getSeverityLabel(code) {
            switch (String(code).toLowerCase()) {
                case 'c': return 'Critical';
                case 'h': return 'High';
                case 'm': return 'Medium';
                case 'l': return 'Low';
                default:  return 'Unknown';
            }
        }

        function buildVulnDetailHTML(vuln) {
            var cve      = esc(vuln.cve_id            || 'Unknown');
            var desc     = esc(vuln.description       || 'No description available.');
            var severity = esc(getSeverityLabel(vuln.severity));
            var affected = vuln.affected_version  || 'Not specified';

            return (
                '<div class="sucuriscan-collapsible-table-source-block">' +
                '<div class="sucuriscan-collapsible-table-field"><strong>CVE&nbsp;ID:</strong> <p>' + cve + '</p></div>' +
                '<div class="sucuriscan-collapsible-table-field"><strong>Description:</strong> <p>' + desc + '</p></div>' +
                '<div class="sucuriscan-collapsible-table-field"><strong>Severity:</strong> <p>' + severity + '</p></div>' +
                '<div class="sucuriscan-collapsible-table-field"><strong>Affected&nbsp;Version:</strong> <p>' + affected + '</p></div>' +
                '<div class="sucuriscan-collapsible-table-field"><strong>Source</strong> <p>We partner with WPVulnerability to provide you with valuable data that will improve your asset\'s stance. Please note data shown by external vulnerability scanners can present delays.</p></div>' +
                '</div>'
            );
        }

        function renderCollapsibleVulnerabilities(vulns, selector, prefix) {

            if (!Array.isArray(vulns) || vulns.length === 0) {
                $(selector).html('<p>No vulnerabilities found.</p>');
                return;
            }

            var html = '' +
                '<div class="sucuriscan-collapsible-table">' +
                '<div class="sucuriscan-collapsible-table-header-row">' +
                '<div class="sucuriscan-collapsible-table-header-left">CVE&nbsp;ID</div>' +
                '<button class="sucuriscan-collapsible-table-show-all button-secondary" id="show-all-' + prefix + '">Show All</button>' +
                '</div>' +
                '<div class="sucuriscan-collapsible-table-body">';

            for (var i = 0; i < vulns.length; i++) {
                var vuln  = vulns[i];
                var cve   = esc(vuln.cve_id || 'Unknown');
                var rowId = prefix + '-vuln-detail-' + i;

                html += '' +
                    '<div class="sucuriscan-collapsible-table-row" data-target="#' + rowId + '">' +
                    '<div class="sucuriscan-collapsible-name">' + cve + '</div>' +
                    '<div class="sucuriscan-collapsible-toggle">+</div>' +
                    '</div>' +
                    '<div class="sucuriscan-collapsible-table-details-row" id="' + rowId + '" style="display:none;">' +
                    buildVulnDetailHTML(vuln) +
                    '</div>';
            }

            html += '</div></div>';
            $(selector).html(html);

            $(selector).on('click', '.sucuriscan-collapsible-table-row', function () {
                var $row = $(this);
                var $det = $($row.data('target'));
                var $tog = $row.find('.sucuriscan-collapsible-toggle');

                $det.slideToggle(200, function () {
                    var open = $det.is(':visible');
                    $tog.text(open ? '−' : '+')
                        .toggleClass('sucuriscan-collapsible-toggle-open', open);
                });
            });

            $(selector).find('#show-all-' + prefix).on('click', function () {
                const $btn = $(this);
                const isShowingAll = ($btn.text() === 'Show All');

                if (isShowingAll) {
                    $(selector).find('.sucuriscan-collapsible-table-details-row').slideDown(200);
                    $(selector).find('.sucuriscan-collapsible-toggle').text('−');
                    $(selector).find('.sucuriscan-collapsible-toggle').addClass('sucuriscan-collapsible-toggle-open');
                    $btn.text('Hide All');
                } else {
                    $(selector).find('.sucuriscan-collapsible-table-details-row').slideUp(200);
                    $(selector).find('.sucuriscan-collapsible-toggle').text('+');
                    $(selector).find('.sucuriscan-collapsible-toggle').removeClass('sucuriscan-collapsible-toggle-open');
                    $btn.text('Show All');
                }
            });
        }

        $.post(
            '%%SUCURI.AjaxURL.Dashboard%%',
            {
                action:                'sucuriscan_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action:           'vulnerabilities_scan_core_php'
            },
            function (response) {
                if (!response || !response.success || !response.data) {
                    $('#core-vulnerability-results').html('<strong>Error:</strong> Could not fetch WordPress Core vulnerabilities.');
                    $('#php-vulnerability-results').html('<strong>Error:</strong> Could not fetch PHP vulnerabilities.');
                    return;
                }

                var wpVulns  = (response.data.WordPressCoreVulnerabilities  || {}).matched_vulnerabilities || [];
                var phpVulns = (response.data.PHPVulnerabilities         || {}).matched_vulnerabilities || [];

                renderCollapsibleVulnerabilities(wpVulns,  '#core-vulnerability-results', 'core');
                renderCollapsibleVulnerabilities(phpVulns, '#php-vulnerability-results', 'php');
            }
        ).fail(function () {
            $('#core-vulnerability-results').html('<strong>Error:</strong> Could not fetch WordPress Core vulnerabilities.');
            $('#php-vulnerability-results').html('<strong>Error:</strong> Could not fetch PHP vulnerabilities.');
        });
    });

    jQuery(function ($) {

        /**
         * Load vulnerability info for a plugin or theme card and update its badge.
         */
        function loadVulnerabilityData($anchor, type) {
            var $mini   = $anchor.closest('.sucuriscan-plugin-mini-card');
            var $card   = $anchor.closest('.sucuriscan-plugin-card');
            var $tag    = $card.find('.sucuriscan-tag');
            var slug    = $anchor.data('name');
            var version = $anchor.data('version');

            $mini.addClass('sucuriscan-status-loading');

            $.post(
                '%%SUCURI.AjaxURL.Dashboard%%',
                {
                    action:                'sucuriscan_ajax',
                    sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                    form_action:           (type === 'plugin') ? 'plugin_vulnerabilities_scan' : 'theme_vulnerabilities_scan',
                    slug:                  slug,
                    version:               version
                }
            )
                .done(function (response) {
                    $mini.removeClass('sucuriscan-status-loading');

                    if (!response || !response.success || !response.data) {
                        $mini.addClass('sucuriscan-status-unknown');
                        return;
                    }

                    var matched = response.data.matched_vulnerabilities || [];

                    if (matched.length === 0) {
                        $mini.addClass('sucuriscan-status-success');
                    } else {
                        $mini.addClass('sucuriscan-status-warning');
                        $tag.html('<span>' + matched.length + ' Vulnerabilities</span>')
                            .removeClass('sucuriscan-hidden');
                    }
                })
                .fail(function () {
                    $mini.removeClass('sucuriscan-status-loading')
                        .addClass('sucuriscan-status-unknown');
                });
        }

        $('.sucuriscan-plugins-list-wrapper:first .sucuriscan-plugin-card a[data-name]')
            .each(function () {
                loadVulnerabilityData($(this), 'plugin');
            });

        $('.sucuriscan-plugins-list-wrapper:last .sucuriscan-plugin-card a[data-name]')
            .each(function () {
                loadVulnerabilityData($(this), 'theme');
            });

        function showVulnerabilityPopup(title, bodyHTML) {
            var $overlay = $('<div class="sucuriscan-overlay" style="display:none;"></div>');
            var $modal   = $('<div class="sucuriscan-vulnerability-modal"></div>');

            $('<button>', {class:'sucuriscan-vulnerability-close', text:'X'}).appendTo($modal);
            $('<h3>').text(title).appendTo($modal);
            $('<div>', {class:'sucuriscan-collapsible-table-body'}).html(bodyHTML).appendTo($modal);

            $overlay.append($modal).appendTo('body').fadeIn();
            $overlay.on('click', '.sucuriscan-vulnerability-close', function () {
                $overlay.fadeOut(function () { $overlay.remove(); });
            });
        }

        $('.sucuriscan-card-container').on('click', '.sucuriscan-plugin-card a[data-name]', function (e) {
            e.preventDefault();
            var name = $(this).data('name') || 'Unknown';
            var html = '<p>We partner with WPVulnerability to provide you with valuable data that will improve your asset\'s stance. Data may be delayed.</p>';
            showVulnerabilityPopup(name, html);
        });
    });
</script>

<div class="sucuriscan-upgrade-banner %%SUCURI.FreemiumVisibility%%">
    <div class="sucuriscan-upgrade-left">
        <div class="sucuriscan-upgrade-icon">
            <img src="%%SUCURI.PluginURL%%/inc/images/scan-icon.svg" alt="Scan Icon">
        </div>

        <div class="sucuriscan-upgrade-text">
            <h4>Upgrade for <br /><span>More Scans</span></h4>
        </div>

        <div class="sucuriscan-upgrade-text">
            <p>Want more frequent and thorough security scans for your WordPress site?
                Upgrade to our premium version and connect your Sucuri WAF plan
                to unlock additional scanning options.</p>
        </div>
    </div>

    <div class="sucuriscan-upgrade-right">
        <img class="sucuriscan-upgrade-bg-shape"
             src="%%SUCURI.PluginURL%%/inc/images/upgrade-shape.svg"
             alt="Decorative shape">

        <a href="https://sucuri.net/website-firewall/" class="sucuriscan-upgrade-button" target="_blank" rel="noopener">Upgrade Now</a>
    </div>
</div>

<div class="sucuriscan-clearfix %%SUCURI.PremiumVisibility%%">
    <div class="sucuriscan-panel">
        <h3>Core Vulnerability Scanning</h3>
        <div id="core-vulnerability-results">Loading WordPress Core vulnerabilities...</div>
    </div>
</div>

<div class="sucuriscan-clearfix %%SUCURI.PremiumVisibility%%">
    <div class="sucuriscan-panel">
        <h3>PHP Vulnerabilities</h3>
        <div id="php-vulnerability-results">Loading PHP vulnerabilities...</div>
    </div>
</div>

<div class="sucuriscan-card-container %%SUCURI.PremiumVisibility%%">
    <div class="sucuriscan-plugins-list-wrapper">
        <div class="sucuriscan-plugins-list">
            <div class="sucuriscan-plugins-list-header">
                <div class="sucuriscan-plugin-card-header">
                    Plugins
                </div>
                <div>
                    Installed Plugins: %%SUCURI.PluginsCount%%
                </div>
            </div>
            <div class="sucuriscan-themes-list-body">
                %%%SUCURI.Plugins%%%
            </div>
        </div>

    </div>

    <div class="sucuriscan-plugins-list-wrapper">
        <div class="sucuriscan-plugins-list">
            <div class="sucuriscan-plugins-list-header">
                <div class="sucuriscan-plugin-card-header">
                    Themes
                </div>
                <div>
                    Installed Themes: %%SUCURI.ThemesCount%%
                </div>
            </div>
            <div class="sucuriscan-themes-list-body">
                %%%SUCURI.Themes%%%
            </div>
        </div>
    </div>
</div>

<div class="sucuriscan-card-container">
    <div class="sucuriscan-card-content-3">%%%SUCURI.SiteCheck.Malware%%%</div>
    <div class="sucuriscan-card-content-3">%%%SUCURI.SiteCheck.Blocklist%%%</div>
    <div class="sucuriscan-card-content-3">%%%SUCURI.WordPress.Recommendations%%%</div>
</div>

<div class="sucuriscan-card-container">
    <div class="sucuriscan-card-content-3">
        <div class="sucuriscan-panel text-center">
            <a href="https://sucuri.net/live-chat/" target="_blank" rel="noopener">
                <img src="%%SUCURI.PluginURL%%/inc/images/tso.png" />
            </a>
        </div>
    </div>
    <div class="sucuriscan-card-content-3">

        <div class="sucuriscan-panel sucuriscan-resources">
            <h3 class="sucuriscan-resources-title">Sucuri Resources</h3>
            <ul class="sucuriscan-resources-list">

                <li>
                    <a href="https://sucuri.net/email-courses/" target="_blank" rel="noopener"
                       class="sucuriscan-resources-link">
                        <div class="sucuriscan-resources-label">
                            <div class="sucuriscan-resources-icon sucuriscan-resources-icon-email"></div>

                            <span>Email Course</span>
                        </div>

                        <div class="sucuriscan-resources-arrow"></div>
                    </a>
                </li>

                <li>
                    <a href="https://blog.sucuri.net/"
                       class="sucuriscan-resources-link" target="_blank" rel="noopener">
                        <div class="sucuriscan-resources-label">
                            <div class="sucuriscan-resources-icon sucuriscan-resources-icon-blog"></div>

                            <span>Sucuri Blog</span>
                        </div>

                        <div class="sucuriscan-resources-arrow"></div>
                    </a>
                </li>

                <li>
                    <a href="https://sucuri.net/technical-hub/"
                       class="sucuriscan-resources-link" target="_blank" rel="noopener">
                        <div class="sucuriscan-resources-label">
                            <div class="sucuriscan-resources-icon sucuriscan-resources-icon-hub"></div>

                            <span>Technical Hub</span>
                        </div>

                        <div class="sucuriscan-resources-arrow"></div>
                    </a>
                </li>

                <li>
                    <a href="https://info.sucuri.net/subscribe-to-security"
                       class="sucuriscan-resources-link" target="_blank" rel="noopener">
                        <div class="sucuriscan-resources-label">
                            <div class="sucuriscan-resources-icon sucuriscan-resources-icon-newsletter"></div>

                            <span>Newsletter</span>
                        </div>

                        <div class="sucuriscan-resources-arrow"></div>
                    </a>
                </li>

            </ul>
        </div>

    </div>
    <div class="sucuriscan-card-content-3">
        <div class="sucuriscan-panel text-center">
            <a href="https://sucuri.net/documentation/CreditCardSkimmingMalwareThreats.pdf" target="_blank" rel="noopener">
                <img src="%%SUCURI.PluginURL%%/inc/images/ebook.png" />
            </a>
        </div>
    </div>
</div>

<div class="sucuriscan-guardian-logo">
    <a href="https://sucuri.net/website-firewall/" target="_blank" rel="noopener">
        <img src="%%SUCURI.PluginURL%%/inc/images/sucuri-guardian.png" alt="Sucuri Security" />
    </a>
</div>
