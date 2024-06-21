
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

    <div class="sucuriscan-double-box sucuriscan-hstatus sucuriscan-hstatus-%%SUCURI.CacheOptions.CacheControl%%" data-cy="sucuriscan_security_keys_autoupdater">
        <p>
            <strong>{{Cache Control Header}}</strong> &mdash; %%SUCURI.CacheOptions.Status%%<br />
            {{WordPress by default does not come with cache control headers, used by WAFs and CDNs that are useful to both improve performance and reduce bandwidth and other resources demand on the hosting server. }}
        </p>

        <form action="%%SUCURI.URL.Settings%%#headers" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_update_cache_options" value="1" />
            <label><strong>{{Frequency:}}</strong></label>
            <select name="sucuriscan_cache_options_mode" data-cy="sucuriscan_security_keys_autoupdater_select">
                %%%SUCURI.CacheOptions.Modes%%%
            </select>
            <input type="submit" value="{{Submit}}" class="button button-primary" data-cy="sucuriscan_security_keys_autoupdater_submit" />
        </form>
    </div>
</div>
