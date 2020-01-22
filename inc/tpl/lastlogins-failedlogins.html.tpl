
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Failed logins}}</h3>

    <div class="inside">
        <p>{{This information will be used to determine if your site is being victim of <a href="https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing" target="_blank" rel="noopener">Password Guessing Brute Force Attacks</a>. These logs will be accumulated and the plugin will send a report via email if there are more than <code>%%SUCURI.FailedLogins.MaxFailedLogins%%</code> failed login attempts during the same hour, you can change this number from <a href="%%SUCURI.URL.Settings%%#alerts">here</a>. <b>NOTE:</b> Some <em>"Two-Factor Authentication"</em> plugins do not follow the same rules that WordPress have to report failed login attempts, so you may not see all the attempts in this panel if you have one of these plugins installed.}}</p>

        <form action="%%SUCURI.URL.Lastlogins%%#blocked" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-lastlogins-failed">
                <thead>
                    <tr>
                        <th class="manage-column">{{Username}}</th>
                        <th class="manage-column">{{IP Address}}</th>
                        <th class="manage-column">{{Date/Time}}</th>
                        <th class="manage-column" width="300">{{Web Browser}}</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.FailedLogins.List%%%

                    <tr class="sucuriscan-%%SUCURI.FailedLogins.NoItemsVisibility%%">
                        <td colspan="4">
                            <em>{{no data available}}</em>
                        </td>
                    </tr>

                    <tr class="sucuriscan-%%SUCURI.FailedLogins.PaginationVisibility%%">
                        <td colspan="4">
                            <ul class="sucuriscan-pagination">
                                %%%SUCURI.FailedLogins.PaginationLinks%%%
                            </ul>
                        </td>
                    </tr>
                </tbody>
            </table>
            
        </form>
        <form action="%%SUCURI.URL.Lastlogins%%#failed" method="post">
        <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
        <input type="hidden" name="sucuriscan_delete_failedlogins" value="1" />
        <input type="submit" value="{{Delete All Failed Logins}}" class="button button-primary" />
    </form>
    </div>
</div>
