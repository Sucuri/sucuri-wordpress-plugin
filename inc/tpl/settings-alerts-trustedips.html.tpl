
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.TrustedIPs@@</h3>

    <div class="inside">
        <p>@@SUCURI.TrustedIPsInfo@@</p>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="POST">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.RemoteAddr@@:</label>
                <input type="text" name="sucuriscan_trust_ip" placeholder="e.g. 182.120.56.0/24" />
                <input type="submit" value="@@SUCURI.Submit@@" class="button button-primary" />
            </fieldset>
        </form>

        <hr>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-settings-trustip">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="manage-column">@@SUCURI.RemoteAddr@@</th>
                        <th class="manage-column">@@SUCURI.CIDRFormat@@</th>
                        <th class="manage-column">@@SUCURI.IPAddedAt@@</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.TrustedIPs.List%%%

                    <tr class="sucuriscan-%%SUCURI.TrustedIPs.NoItems.Visibility%%">
                        <td colspan="4">
                            <em>@@SUCURI.NoData@@</em>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="submit" class="button button-primary">@@SUCURI.Delete@@</button>
        </form>
    </div>
</div>
