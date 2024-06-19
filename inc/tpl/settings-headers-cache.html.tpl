
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Cache Control Header Options}}</h3>

    <div class="inside">
        <p>{{Here you can see all the cache options available.}}</p>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-table-double-title sucuriscan-last-logins">
            <thead>
                <tr>
                    <th class="manage-column">{{Option}}</th>
                    <th class="manage-column">{{max-age}}</th>
                    <th class="manage-column">{{s-maxage}}</th>
                    <th class="manage-column">{{stale-if-error}}</th>
                    <th class="manage-column">{{stale-while-revalidate}}</th>
                    <th class="manage-column">{{Pagination factor}}</th>
                    <th class="manage-column">{{Old age multiplier}}</th>

                    <th class="manage-column">&nbsp;</th>
                </tr>
            </thead>

            <tbody data-cy="sucuriscan_last_logins_table">

            %%%SUCURI.CacheOptions.Options%%%

            <tr class="sucuriscan-%%SUCURI.CacheOptions.NoItemsVisibility%%">
                <td colspan="5">
                    <em>{{No options available}}</em>
                </td>
            </tr>

            </tbody>
        </table>
    </div>

    <form action="%%SUCURI.URL.Lastlogins%%#allusers" method="post">
        <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
        <input type="hidden" name="sucuriscan_delete_lastlogins" value="1" />
        <input type="submit" value="{{Disable Cache Control}}" class="button button-primary" data-cy="sucuriscan_last_logins_delete_logins_button" />
    </form>
</div>
