
<div class="sucuriscan-panelstuff">
    <div class="postbox sucuriscan-border sucuriscan-table-description">
        <h3>Blocked Users</h3>

        <div class="inside">
            <p>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
                quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
                consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
                cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
                proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
            </p>

            <div class="sucuriscan-inline-alert-info">
                <p>
                    Take in consideration that this is not a 100% bulletproof mechanism
                    to block unwanted user authentications from malicious users. Depending
                    on the configuration of your website, installed plugins, installed
                    themes, and even the version of WordPress there might still be weak
                    points that automated tools can take advantage of to brute force the
                    user accounts registered in your website. <a target="_blank"
                    href="https://sucuri.net/website-firewall/?wp=bu">Install a firewall</a>
                    to have full protection and mitigate this and a myriad of other attacks.
                </p>
            </div>

            <div class="sucuriscan-inline-alert-warning">
                <p>Do not block existent accounts, they will lose access forever.</p>
            </div>

            <form method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

                <table class="wp-list-table widefat sucuriscan-table">
                    <thead>
                        <tr>
                            <th class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                                <input id="cb-select-all-1" type="checkbox">
                            </th>
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
                                <em>The table is empty.</em>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="sucuriscan-recipient-form">
                    <button type="submit" class="button button-primary">Unblock User</button>
                </div>
            </form>
        </div>
    </div>
</div>
