
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Trusted IP Addresses}}</h3>

    <div class="inside">
        <p>{{If you are working in a LAN <em>(Local Area Network)</em> you may want to include the IP addresses of all the nodes in the subnet, this will force the plugin to stop sending email alerts about actions executed from trusted IP addresses. Use the CIDR <em>(Classless Inter Domain Routing)</em> format to specify ranges of IP addresses <em>(only 8, 16, and 24)</em>.}}</p>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="POST">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <fieldset class="sucuriscan-clearfix">
                <label>{{IP Address:}}</label>
                <input type="text" name="sucuriscan_trust_ip" placeholder="{{e.g. 182.120.56.0/24}}" />
                <input type="submit" value="{{Submit}}" class="button button-primary" />
            </fieldset>
        </form>

        <hr>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-settings-trustip">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">{{Select All}}</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="manage-column">{{IP Address}}</th>
                        <th class="manage-column">{{CIDR Format}}</th>
                        <th class="manage-column">{{IP Added At}}</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.TrustedIPs.List%%%

                    <tr class="sucuriscan-%%SUCURI.TrustedIPs.NoItems.Visibility%%">
                        <td colspan="4">
                            <em>{{no data available}}</em>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="submit" class="button button-primary">{{Delete}}</button>
        </form>
    </div>
</div>
