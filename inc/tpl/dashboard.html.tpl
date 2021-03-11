
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
        $('#sucuriscan-title-iframes').html(data.iframes.title);
        $('#sucuriscan-title-links').html(data.links.title);
        $('#sucuriscan-title-scripts').html(data.scripts.title);

        sucuriscanSiteCheckLinks('#sucuriscan-tabs-iframes', data.iframes.content);
        sucuriscanSiteCheckLinks('#sucuriscan-tabs-links', data.links.content);
        sucuriscanSiteCheckLinks('#sucuriscan-tabs-scripts', data.scripts.content);

        $('#sucuriscan-malware').html(data.malware);
        $('#sucuriscan-blocklist').html(data.blocklist);
        $('#sucuriscan-recommendations').html(data.recommendations);
    });
});
</script>

<div class="sucuriscan-clearfix">
    <div class="sucuriscan-pull-left sucuriscan-dashboard-left">
        <div class="sucuriscan-panel">
            <div class="sucuriscan-tabs">
                <ul class="sucuriscan-clearfix sucuriscan-tabs-buttons">
                    <li><a href="%%SUCURI.URL.Dashboard%%#auditlogs">{{Audit Logs}}</a></li>
                    <li><a href="%%SUCURI.URL.Dashboard%%#iframes" id="sucuriscan-title-iframes">%%SUCURI.SiteCheck.iFramesTitle%%</a></li>
                    <li><a href="%%SUCURI.URL.Dashboard%%#links" id="sucuriscan-title-links">%%SUCURI.SiteCheck.LinksTitle%%</a></li>
                    <li><a href="%%SUCURI.URL.Dashboard%%#scripts" id="sucuriscan-title-scripts">%%SUCURI.SiteCheck.ScriptsTitle%%</a></li>
                </ul>

                <div class="sucuriscan-tabs-containers">
                    <div id="sucuriscan-tabs-auditlogs">
                        %%%SUCURI.AuditLogs%%%
                    </div>

                    <div id="sucuriscan-tabs-iframes">
                        %%%SUCURI.SiteCheck.iFramesContent%%%
                    </div>

                    <div id="sucuriscan-tabs-links">
                        %%%SUCURI.SiteCheck.LinksContent%%%
                    </div>

                    <div id="sucuriscan-tabs-scripts">
                        %%%SUCURI.SiteCheck.ScriptsContent%%%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sucuriscan-pull-right sucuriscan-dashboard-right">
        %%%SUCURI.SiteCheck.Malware%%%

        %%%SUCURI.SiteCheck.Blocklist%%%

        %%%SUCURI.SiteCheck.Recommendations%%%
        
        %%%SUCURI.WordPress.Recommendations%%%
    </div>
</div>
