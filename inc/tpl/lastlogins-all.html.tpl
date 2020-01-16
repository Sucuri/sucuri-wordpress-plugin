
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Successful Logins (all)}}</h3>

    <div class="inside">
        <p>{{Here you can see a list of all the successful user logins.}}</p>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-table-double-title sucuriscan-last-logins">
            <thead>
                <tr>
                    <th colspan="5">{{Successful Logins (all)}}</th>
                </tr>

                <tr>
                    <th class="manage-column">{{Username}}</th>
                    <th class="manage-column">{{IP Address}}</th>
                    <th class="manage-column">{{Hostname}}</th>
                    <th class="manage-column">{{Date/Time}}</th>
                    <th class="manage-column">&nbsp;</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.UserList%%%

                <tr class="sucuriscan-%%SUCURI.UserList.NoItemsVisibility%%">
                    <td colspan="5">
                        <em>{{no data available}}</em>
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
    <form action="%%SUCURI.URL.Lastlogins%%#allusers" method="post">
        <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
        <input type="hidden" name="sucuriscan_delete_lastlogins" value="1" />
        <input type="submit" value="{{Delete All Successful Logins}}" class="button button-primary" />
    </form>
</div>
