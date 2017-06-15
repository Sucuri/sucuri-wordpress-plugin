
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.BlockedUsers@@</h3>

    <div class="inside">
        <p>@@SUCURI.BlockedUsersInfo@@</p>

        <div class="sucuriscan-inline-alert-info">
            <p>@@SUCURI.BlockedUsersNote@@</p>
        </div>

        <div class="sucuriscan-inline-alert-error">
            <p>@@SUCURI.BlockedUsersByIP@@</p>
        </div>

        <form action="%%SUCURI.URL.Lastlogins%%#blocked" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="manage-column">@@SUCURI.Username@@</th>
                        <th class="manage-column">@@SUCURI.BlockedAt@@</th>
                        <th class="manage-column">@@SUCURI.FirstAttempt@@</th>
                        <th class="manage-column">@@SUCURI.LastAttempt@@</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.BlockedUsers.List%%%

                    <tr class="sucuriscan-%%SUCURI.BlockedUsers.NoItemsVisibility%%">
                        <td colspan="5">
                            <em>@@SUCURI.NoData@@</em>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="submit" class="button button-primary">@@SUCURI.Unblock@@</button>
        </form>
    </div>
</div>
