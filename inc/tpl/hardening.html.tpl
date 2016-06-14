
<div class="sucuriscan-tabs">
    <script type="text/javascript">
    jQuery(document).ready(function ($) {
        var total = $('.sucuriscan-hardening-boxes .postbox').length;
        var applied = $('.sucuriscan-hardening-boxes .postbox .sucuriscan-hstatus-1').length;

        $('#sucuriscan-hardening-stats').html(
            '({{APPLIED}}/{{TOTAL}})'
            .replace('{{TOTAL}}', total)
            .replace('{{APPLIED}}', applied)
        );
    });
    </script>

    <ul>
        <li>
            <a href="#hardening" data-tabname="hardening">
                <span>Hardening Options</span>
                <em id="sucuriscan-hardening-stats">(Loading...)</em>
            </a>
        </li>
        <li>
            <a href="#whitelist" data-tabname="whitelist">Whitelist Blocked PHP Files</a>
        </li>
    </ul>

    <div class="sucuriscan-tab-containers">
        <div id="sucuriscan-hardening">
            %%%SUCURI.Hardening.Panel%%%
        </div>

        <div id="sucuriscan-whitelist">
            %%%SUCURI.Hardening.Whitelist%%%
        </div>
    </div>
</div>
