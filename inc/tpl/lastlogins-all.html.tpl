
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Successful Logins (all)</h3>

    <div class="inside">
        <p>
            Here you can see a list of all the successful user logins.
        </p>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-table-double-title sucuriscan-last-logins">
            <thead>
                <tr>
                    <th colspan="5">User Last Logins (%%SUCURI.UserList.Total%%)</th>
                </tr>

                <tr>
                    <th class="manage-column">User</th>
                    <th class="manage-column">IP Address</th>
                    <th class="manage-column">Hostname</th>
                    <th class="manage-column">Date/Time</th>
                    <th class="manage-column">&nbsp;</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.UserList%%%

                <tr class="sucuriscan-%%SUCURI.UserList.NoItemsVisibility%%">
                    <td colspan="5">
                        <em>No logs so far.</em>
                    </td>
                </tr>

                <tr class="sucuriscan-%%SUCURI.UserList.PaginationVisibility%%">
                    <td colspan="5">
                        <ul class="sucuriscan-pagination">
                            %%%SUCURI.UserList.Pagination%%%
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
