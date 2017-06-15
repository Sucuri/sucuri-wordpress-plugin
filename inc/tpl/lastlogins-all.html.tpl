
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.LoginsAll@@</h3>

    <div class="inside">
        <p>@@SUCURI.LoginsAllInfo@@</p>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-table-double-title sucuriscan-last-logins">
            <thead>
                <tr>
                    <th colspan="5">@@SUCURI.LoginsAll@@</th>
                </tr>

                <tr>
                    <th class="manage-column">@@SUCURI.Username@@</th>
                    <th class="manage-column">@@SUCURI.RemoteAddr@@</th>
                    <th class="manage-column">@@SUCURI.Hostname@@</th>
                    <th class="manage-column">@@SUCURI.Datetime@@</th>
                    <th class="manage-column">&nbsp;</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.UserList%%%

                <tr class="sucuriscan-%%SUCURI.UserList.NoItemsVisibility%%">
                    <td colspan="5">
                        <em>@@SUCURI.NoData@@</em>
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
