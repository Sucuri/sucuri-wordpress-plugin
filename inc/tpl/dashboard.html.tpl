%%%SUCURI.Integrity%%%

<script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */
    jQuery(document).ready(function ($) {
        var sucuriscanSiteCheckLinks = function (target, links) {
            if (links.length === 0) {
                $(target).html('<div><em>{{No data available}}</em></div>');
                return;
            }

            var tbody = $('<tbody>');
            var options = {class: 'wp-list-table widefat sucuriscan-table'};

            for (var key in links) {
                if (links.hasOwnProperty(key)) {
                    tbody.append('<tr><td><a href="' + links[key] + '" target="_b' +
                        'lank" class="sucuriscan-monospace">' + links[key] + '</a></t' +
                        'd></tr>');
                }
            }

            $(target).html($('<table>', options).html(tbody));
        };

        $.post('%%SUCURI.AjaxURL.Dashboard%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            sucuriscan_sitecheck_refresh: '%%SUCURI.SiteCheck.Refresh%%',
            form_action: 'malware_scan',
        }, function (data) {
            $('#sucuriscan-malware').html(data.malware);
            $('#sucuriscan-blocklist').html(data.blocklist);
            $('#sucuriscan-recommendations').html(data.recommendations);
        });
    });
</script>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        /**
         * Renders a collapsible “table” for matched vulnerabilities (WordPress or PHP).
         * @param {Array}  vulnerabilities - An array of vulnerability objects.
         * @param {String} selector - e.g. '#core-vulnerability-results' or '#php-vulnerability-results'
         * @param {String} type - either 'wp' (WordPress) or 'php' to differentiate naming & ID
         */
        function renderCollapsibleVulnerabilities(vulnerabilities, selector, type) {
            if (!vulnerabilities || vulnerabilities.length === 0) {
                $(selector).html('<p>No vulnerabilities found.</p>');
                return;
            }

            let html = `
        <div class="sucuriscan-collapsible-table">
          <div class="sucuriscan-collapsible-table-header-row">
            <div class="sucuriscan-collapsible-table-header-left">Vulnerability Name</div>
            <button class="sucuriscan-collapsible-table-show-all button-secondary">Show All</button>
          </div>
          <div class="sucuriscan-collapsible-table-body">
        `;

            vulnerabilities.forEach(function (vulnerability, index) {

                let vulnerabilityName = 'Unnamed Vulnerability';

                // For WP vulnerabilities: Use the first source object's name (if it exists)
                if (type === 'wp') {
                    if (vulnerability.source && vulnerability.source.length > 0 && vulnerability.source[0].name) {
                        vulnerabilityName = vulnerability.source[0].name;
                    }
                } else {
                    // For PHP vulnerabilities: Use the top-level vuln.name
                    vulnerabilityName = vulnerability.name || 'Unnamed Vulnerability';
                }

                const collapsibleId = `${type}-vuln-details-${index}`;

                const detailHTML = buildVulnDetailHTML(vulnerability);

                html += `
              <div class="sucuriscan-collapsible-table-row" data-index="${index}" data-target="#${collapsibleId}">
                <div class="sucuriscan-collapsible-name">${vulnerabilityName}</div>
                <div class="sucuriscan-collapsible-toggle">+</div>
              </div>
              <div class="sucuriscan-collapsible-table-details-row" id="${collapsibleId}" style="display: none;">
                ${detailHTML}
              </div>
            `;
            });

            html += `
          </div> <!-- .sucuriscan-collapsible-table-body -->
        </div> <!-- .sucuriscan-collapsible-table-header-row -->
        `;

            $(selector).html(html);

            const $container = $(selector).find('.sucuriscan-collapsible-table');

            $container.on('click', '.sucuriscan-collapsible-table-row', function () {
                const $row       = $(this);
                const detailsId  = $row.data('target');
                const $details   = $(detailsId);
                const $toggle    = $row.find('.sucuriscan-collapsible-toggle');

                if ($details.is(':visible')) {
                    $details.slideUp(200);
                    $toggle.text('+');
                    $toggle.removeClass('sucuriscan-collapsible-toggle-open');
                } else {
                    $details.slideDown(200);
                    $toggle.text('−');
                    $toggle.addClass('sucuriscan-collapsible-toggle-open');
                }
            });

            $container.find('.sucuriscan-collapsible-table-show-all').on('click', function () {
                const $btn = $(this);
                const isShowingAll = ($btn.text() === 'Show All');

                if (isShowingAll) {
                    $container.find('.sucuriscan-collapsible-table-details-row').slideDown(200);
                    $container.find('.sucuriscan-collapsible-toggle').text('−');
                    $container.find('.sucuriscan-collapsible-toggle').addClass('sucuriscan-collapsible-toggle-open');
                    $btn.text('Hide All');
                } else {
                    $container.find('.sucuriscan-collapsible-table-details-row').slideUp(200);
                    $container.find('.sucuriscan-collapsible-toggle').text('+');
                    $container.find('.sucuriscan-collapsible-toggle').removeClass('sucuriscan-collapsible-toggle-open');
                    $btn.text('Show All');
                }
            });
        }

        /**
         * Build the HTML for each vulnerability’s details section,
         * merging data from the "source" objects.
         */
        function buildVulnDetailHTML(vuln) {
            let detail = '';

            const affectedVersions = vuln.name || 'Not specified';
            const sources = vuln.source || [];

            if (sources.length === 0) {
                return `
                  <div class="sucuriscan-collapsible-table-source-block">
                    <div class="sucuriscan-collapsible-table-field">No source data provided.</div>
                  </div>
                `;
            }

            sources.forEach(function (src) {
                const cveId    = src.id || 'N/A';
                const desc     = src.description || 'No description available.';
                const link     = src.link || '#';
                const severity = getSeverityString(vuln.impact);

                detail += `
                  <div class="sucuriscan-collapsible-table-source-block">
                    <div class="sucuriscan-collapsible-table-field"><strong>CVE ID:</strong> <p>${cveId}</p></div>
                    <div class="sucuriscan-collapsible-table-field"><strong>Description:</strong> <p>${desc}</p></div>
                    <div class="sucuriscan-collapsible-table-field"><strong>Severity:</strong> <p>${severity}</p></div>
                    <div class="sucuriscan-collapsible-table-field"><strong>Affected Versions:</strong> <p>${affectedVersions}</p></div>
                    <div class="sucuriscan-collapsible-table-field"><strong>Source:</strong>
                      <p>We partner with WPVulnerabilities to provide you with valuable data that will improve your asset's stance. Please note data shown by external vulnerability scanners can present delays.</p>
                    </div>
                  </div>
                `;
            });

            return detail;
        }

        function getSeverityString(impactObj) {
            if (!impactObj || !impactObj.cvss || !impactObj.cvss.severity) {
                return 'Unknown';
            }

            return impactObj.cvss.severity;
        }

        $.post(
            '%%SUCURI.AjaxURL.Dashboard%%',
            {
                action: 'sucuriscan_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action: 'vulnerabilities_scan_core_php'
            },
            function (response) {
                if (!response || !response.success) {
                    $('#core-vulnerability-results')
                        .html('<strong>Error:</strong> Could not fetch WordPress Core vulnerabilities.');
                    $('#php-vulnerability-results')
                        .html('<strong>Error:</strong> Could not fetch PHP vulnerabilities.');
                    return;
                }

                var wpData = response.data.WordPressCoreVulnerabilies;
                var phpData = response.data.PHPVulnerabilities;

                renderCollapsibleVulnerabilities(wpData && wpData.matched_vulnerabilities, '#core-vulnerability-results', 'wp');
                renderCollapsibleVulnerabilities(phpData && phpData.matched_vulnerabilities, '#php-vulnerability-results', 'php');
            }
        ).fail(function () {
            $('#core-vulnerability-results')
                .html('<strong>Error:</strong> Could not fetch WordPress Core vulnerabilities.');
            $('#php-vulnerability-results')
                .html('<strong>Error:</strong> Could not fetch PHP vulnerabilities.');
        });
    });
</script>

<script type="text/javascript">
    jQuery(document).ready(function ($) {
        function showVulnerabilityPopup(title, bodyHTML) {
            var $overlay = $('<div class="sucuriscan-overlay" style="display:none;"></div>');
            var $modal   = $('<div class="sucuriscan-vulnerability-modal"></div>');

            var $closeBtn = $('<button class="sucuriscan-vulnerability-close">X</button>');
            var $title    = $('<h3></h3>').text(title);
            var $body     = $('<div class="sucuriscan-collapsible-table-body"></div>').html(bodyHTML);

            $modal.append($closeBtn);
            $modal.append($title);
            $modal.append($body);

            $overlay.append($modal);
            $('body').append($overlay);

            $overlay.fadeIn();

            $overlay.on('click', '.sucuriscan-vulnerability-close', function () {
                $overlay.fadeOut(function () {
                    $overlay.remove();
                });
            });
        }

        $('.sucuriscan-card-container').on('click', '.sucuriscan-plugin-card a[data-name]', function (event) {
            event.preventDefault();

            var $anchor  = $(this);
            var itemName = $anchor.data('name') || 'Unknown';

            var html = `<p>We partner with WPVulnerabilities to provide you with valuable data that will improve your asset's stance. Please note data shown by external vulnerability scanners can present delays.</p>`;

            showVulnerabilityPopup(itemName, html);
        });

        function loadVulnerabilityData($anchor, type) {
            var $miniCard = $anchor.closest('.sucuriscan-plugin-mini-card');
            var $card     = $anchor.closest('.sucuriscan-plugin-card');
            var $tag = $card.find('.sucuriscan-tag');

            $miniCard.addClass('sucuriscan-status-loading');

            var slug    = $anchor.data('name');
            var version = $anchor.data('version');

            var formAction = (type === 'plugin')
                ? 'plugin_vulnerabilities_scan'
                : 'theme_vulnerabilities_scan';

            return $.post('%%SUCURI.AjaxURL.Dashboard%%', {
                action: 'sucuriscan_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action: formAction,
                slug: slug,
                version: version
            })
                .done(function (response) {
                    $miniCard.removeClass('sucuriscan-status-loading');

                    if (!response || !response.success || !response.data) {
                        $miniCard.addClass('sucuriscan-status-unknown');
                        return;
                    }

                    var matchedVulns = response.data.matched_vulnerabilities || [];

                    if (matchedVulns.length === 0) {
                        $miniCard.addClass('sucuriscan-status-success');
                    } else {
                        $miniCard.addClass('sucuriscan-status-warning');
                        $tag.html(`<span>${matchedVulns.length} Vulnerabilities</span>`);
                        $tag.removeClass('sucuriscan-hidden');
                    }
                })
                .fail(function () {
                    $miniCard.removeClass('sucuriscan-status-loading');
                    $miniCard.addClass('sucuriscan-status-unknown');
                });
        }


        var pluginRequests = [];

        $('.sucuriscan-plugins-list-wrapper:first .sucuriscan-themes-list-body .sucuriscan-plugin-card a[data-name]')
            .each(function () {
                var $anchor = $(this);

                pluginRequests.push(loadVulnerabilityData($anchor, 'plugin'));
            });


        var themeRequests = [];

        $('.sucuriscan-plugins-list-wrapper:last .sucuriscan-themes-list-body .sucuriscan-plugin-card a[data-name]')
            .each(function () {
                var $anchor = $(this);

                themeRequests.push(loadVulnerabilityData($anchor, 'theme'));
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

        <a href="#" class="sucuriscan-upgrade-button">Upgrade Now</a>
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
            <a href="#">
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
                    <a href="#"
                       class="sucuriscan-resources-link">
                        <div class="sucuriscan-resources-label">
                            <div class="sucuriscan-resources-icon sucuriscan-resources-icon-hub"></div>

                            <span>Technical Hub</span>
                        </div>

                        <div class="sucuriscan-resources-arrow"></div>
                    </a>
                </li>

                <li>
                    <a href="#"
                       class="sucuriscan-resources-link">
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
            <a href="#">
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
