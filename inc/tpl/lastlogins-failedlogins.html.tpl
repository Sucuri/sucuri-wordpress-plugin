
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.FailedLogins@@</h3>

    <div class="inside">
        <p>@@SUCURI.FailedLoginsInfo@@</p>

        <form action="%%SUCURI.URL.Lastlogins%%#blocked" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-lastlogins-failed">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="manage-column">@@SUCURI.Username@@</th>
                        <th class="manage-column">@@SUCURI.Password@@</th>
                        <th class="manage-column">@@SUCURI.RemoteAddr@@</th>
                        <th class="manage-column">@@SUCURI.Datetime@@</th>
                        <th class="manage-column" width="300">@@SUCURI.Browser@@</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.FailedLogins.List%%%

                    <tr class="sucuriscan-%%SUCURI.FailedLogins.NoItemsVisibility%%">
                        <td colspan="6">
                            <em>@@SUCURI.NoData@@</em>
                        </td>
                    </tr>

                    <tr class="sucuriscan-%%SUCURI.FailedLogins.PaginationVisibility%%">
                        <td colspan="6">
                            <ul class="sucuriscan-pagination">
                                %%%SUCURI.FailedLogins.PaginationLinks%%%
                            </ul>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="submit" class="button button-primary">@@SUCURI.Block@@</button>
        </form>
    </div>
</div>
