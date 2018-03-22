
<tr>
    <td class="sucuriscan-firewall-accesslog sucuriscan-monospace">
        <div class="sucuriscan-accesslog-origin">
            <img src="%%SUCURI.PluginURL%%/inc/images/blank.png"
            class="sucuriscan-flag sucuriscan-flag-%%SUCURI.AccessLog.RequestCountryCode%%" />
            <span>%%SUCURI.AccessLog.RemoteAddr%%</span>
            <span>(%%SUCURI.AccessLog.RequestCountryName%%)</span>
        </div>

        <div class="sucuriscan-accesslog-datetime">
            <span class="sucuriscan-accesslog-label">Date/Time:</span>
            <span>%%SUCURI.AccessLog.RequestDate%%</span>
            <span>%%SUCURI.AccessLog.RequestTime%%</span>
            <span>%%SUCURI.AccessLog.RequestTimezone%%</span>
        </div>

        <div class="sucuriscan-accesslog-signature">
            <span class="sucuriscan-accesslog-label">Signature:</span>
            <span>%%SUCURI.AccessLog.SucuriBlockCode%%</span>
            <span>(%%SUCURI.AccessLog.SucuriBlockReason%%)</span>
        </div>

        <div class="sucuriscan-accesslog-request">
            <span class="sucuriscan-accesslog-label">Request:</span>
            <span>%%SUCURI.AccessLog.HttpProtocol%%</span>
            <span>%%SUCURI.AccessLog.RequestMethod%%</span>
            <span>%%SUCURI.AccessLog.HttpStatus%%</span>
            <span>%%SUCURI.AccessLog.HttpStatusTitle%%</span>
        </div>

        <div class="sucuriscan-accesslog-useragent">
            <span class="sucuriscan-accesslog-label">U-Agent:</span>
            <span>%%SUCURI.AccessLog.HttpUserAgent%%</span>
        </div>

        <div class="sucuriscan-accesslog-target">
            <span class="sucuriscan-accesslog-label">Target.:</span>
            <span>%%SUCURI.AccessLog.ResourcePath%%</span>
        </div>

        <div class="sucuriscan-accesslog-referer">
            <span class="sucuriscan-accesslog-label">Referer:</span>
            <span>%%SUCURI.AccessLog.HttpReferer%%</span>
        </div>
    </td>
</tr>
