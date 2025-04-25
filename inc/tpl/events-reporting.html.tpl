
<script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */
    jQuery(document).ready(function ($) {
        var sucuriscanSiteCheckLinks = function (target, links) {
            if (links.length === 0) {
                $(target).html('<div class="sucuriscan-panel"><em>{{No data available}}</em></div>');
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

<div class="sucuriscan-tabs">
    <ul class="sucuriscan-clearfix sucuriscan-tabs-buttons">
        <li><a href="%%SUCURI.URL.Dashboard%%#auditlogs">{{Audit Logs}}</a></li>
        <li><a href="%%SUCURI.URL.Dashboard%%#iframes" id="sucuriscan-title-iframes">Loading...</a>
        </li>
        <li><a href="%%SUCURI.URL.Dashboard%%#links" id="sucuriscan-title-links">Loading...</a>
        </li>
        <li><a href="%%SUCURI.URL.Dashboard%%#scripts" id="sucuriscan-title-scripts">Loading...</a>
        </li>
    </ul>

    <div class="sucuriscan-tabs-containers">
        <div id="sucuriscan-tabs-auditlogs">
            %%%SUCURI.AuditLogs%%%
        </div>

        <div id="sucuriscan-tabs-iframes">
            <div class="sucuriscan-panel">Loading...</div>
        </div>

        <div id="sucuriscan-tabs-links">
            <div class="sucuriscan-panel">Loading...</div>
        </div>

        <div id="sucuriscan-tabs-scripts">
            <div class="sucuriscan-panel">Loading...</div>
        </div>
    </div>
</div>