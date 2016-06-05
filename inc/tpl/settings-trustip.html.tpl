
<div class="sucuriscan-panelstuff">
    <div class="postbox sucuriscan-border sucuriscan-table-description sucuriscan-trustip-form">
        <h3>Trust IP Address</h3>

        <div class="inside">
            <p>
                If you are working in a LAN <em>(Local Area Network)</em> you may want to
                include the IP addresses of all the nodes in the subnet, this will force the
                plugin to stop sending email notifications about actions executed from trusted
                IP addresses. Use the CIDR <em>(Classless Inter Domain Routing)</em> format to
                specify ranges of IP addresses <em>(only 8, 16, and 24)</em>.
            </p>

            <form action="%%SUCURI.URL.Settings%%#trustip" method="POST">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="text" name="sucuriscan_trust_ip" placeholder="e.g. 182.120.56.0/24" />
                <input type="submit" value="Add Entry" class="button button-primary" />
            </form>
        </div>
    </div>
</div>

<form action="%%SUCURI.URL.Settings%%#trustip" method="post">
    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

    <table class="wp-list-table widefat sucuriscan-table sucuriscan-settings-trustip">
        <thead>
            <tr>
                <th class="manage-column column-cb check-column">
                    <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                    <input id="cb-select-all-1" type="checkbox">
                </th>
                <th class="manage-column">IP Address</th>
                <th class="manage-column">CIDR Format</th>
                <th class="manage-column">Added At</th>
            </tr>
        </thead>

        <tbody>
            %%%SUCURI.TrustedIPs.List%%%

            <tr class="sucuriscan-%%SUCURI.TrustedIPs.NoItems.Visibility%%">
                <td colspan="4">
                    <em>List is empty.</em>
                </td>
            </tr>
        </tbody>

        <tfoot>
            <tr>
                <td colspan="4">
                    <button type="submit" class="button button-primary">Remove selected</button>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
