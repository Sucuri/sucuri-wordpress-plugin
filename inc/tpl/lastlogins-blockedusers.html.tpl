
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Blocked Users</h3>

    <div class="inside">
        <p>Any attempt to authenticate an user account using the functions provided by WordPress will be intercepted and analyzed by the plugin, if the username coincides with any of the users in this list, the authentication process will be immediately stopped. These attemps will not be logged and no email alerts will be sent.</p>

        <div class="sucuriscan-inline-alert-info">
            <p>Take in consideration that this is not a 100% bulletproof mechanism to block unwanted user authentications from malicious users. Depending on the configuration of your website, installed plugins, installed themes, and even the version of WordPress there might still be weak points that automated tools can take advantage of to brute force the user accounts registered in your website. <a target="_blank" href="https://sucuri.net/website-firewall/?wp=bu" rel="noopener">Install a firewall</a> to have full protection and mitigate this and a myriad of other attacks.</p>
        </div>

        <div class="sucuriscan-inline-alert-error">
            <p>Blocking users per IP address is a feature provided by the <a href="https://sucuri.net/website-firewall/" target="_blank" rel="noopener">Sucuri Firewall</a>; to avoid the duplication of code and reduce the amount of false positives this feature will never be implemented in this plugin.</p>
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
                        <th class="manage-column">Username</th>
                        <th class="manage-column">Blocked At</th>
                        <th class="manage-column">First Attempt</th>
                        <th class="manage-column">Last Attempt</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.BlockedUsers.List%%%

                    <tr class="sucuriscan-%%SUCURI.BlockedUsers.NoItemsVisibility%%">
                        <td colspan="5">
                            <em>no data available</em>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="submit" class="button button-primary">Unblock</button>
        </form>
    </div>
</div>
