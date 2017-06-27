
%%%SUCURI.Integrity%%%

<script type="text/javascript">
/* global jQuery */
/* jshint camelcase: false */
jQuery(function ($) {
    $.post('%%SUCURI.AjaxURL.Dashboard%%', {
        action: 'sucuriscan_ajax',
        sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
        form_action: 'malware_scan',
    }, function (data) {
        $('#sucuriscan-title-iframes').html(data.iframes.title);
        $('#sucuriscan-title-links').html(data.links.title);
        $('#sucuriscan-title-scripts').html(data.scripts.title);

        $('#sucuriscan-tabs-iframes').html(data.iframes.content);
        $('#sucuriscan-tabs-links').html(data.links.content);
        $('#sucuriscan-tabs-scripts').html(data.scripts.content);

        $('#sucuriscan-malware').html(data.malware);
        $('#sucuriscan-blacklist').html(data.blacklist);
        $('#sucuriscan-recommendations').html(data.recommendations);
    });
});
</script>

<div class="sucuriscan-clearfix">
    <div class="sucuriscan-pull-left sucuriscan-dashboard-left">
        <div class="sucuriscan-panel">
            <div class="sucuriscan-tabs">
                <ul class="sucuriscan-clearfix sucuriscan-tabs-buttons">
                    <li><a href="%%SUCURI.URL.Dashboard%%#auditlogs">@@SUCURI.AuditLogs@@</a></li>
                    <li><a href="%%SUCURI.URL.Dashboard%%#stats">@@SUCURI.Statistics@@</a></li>
                    <li><a href="%%SUCURI.URL.Dashboard%%#iframes" id="sucuriscan-title-iframes">%%SUCURI.SiteCheck.iFramesTitle%%</a></li>
                    <li><a href="%%SUCURI.URL.Dashboard%%#links" id="sucuriscan-title-links">%%SUCURI.SiteCheck.LinksTitle%%</a></li>
                    <li><a href="%%SUCURI.URL.Dashboard%%#scripts" id="sucuriscan-title-scripts">%%SUCURI.SiteCheck.ScriptsTitle%%</a></li>
                </ul>

                <div class="sucuriscan-tabs-containers">
                    <div id="sucuriscan-tabs-auditlogs">
                        %%%SUCURI.AuditLogs%%%
                    </div>

                    <div id="sucuriscan-tabs-stats">
                        %%%SUCURI.AuditLogsReport%%%
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

        %%%SUCURI.SiteCheck.Blacklist%%%

        %%%SUCURI.SiteCheck.Recommendations%%%
    </div>
</div>
