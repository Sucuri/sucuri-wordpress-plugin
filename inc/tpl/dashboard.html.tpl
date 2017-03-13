
%%%SUCURI.Integrity%%%

<div class="sucuriscan-clearfix">
    <div class="sucuriscan-pull-left sucuriscan-dashboard-left">
        <div class="sucuriscan-panel">
            <div class="sucuriscan-tabs">
                <ul class="sucuriscan-clearfix sucuriscan-tabs-buttons">
                    <li><a href="%%SUCURI.URL.Dashboard%%#auditlogs">Audit Logs</a></li>
                    <li><a href="%%SUCURI.URL.Dashboard%%#stats">Statistics</a></li>
                    <li><a href="%%SUCURI.URL.Dashboard%%#iframes">%%SUCURI.SiteCheck.iFramesTitle%%</a></li>
                    <li><a href="%%SUCURI.URL.Dashboard%%#links">%%SUCURI.SiteCheck.LinksTitle%%</a></li>
                    <li><a href="%%SUCURI.URL.Dashboard%%#scripts">%%SUCURI.SiteCheck.ScriptsTitle%%</a></li>
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
