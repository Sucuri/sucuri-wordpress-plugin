
<div class="sucuriscan-panelstuff">
    <div class="postbox sucuriscan-border sucuriscan-table-description">
        <h3 class="thead-with-button">
            <span>Failed logins</span>
            <span class="thead-topright-action">
                <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
                    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                    <input type="hidden" name="sucuriscan_reset_logfile" value="failedlogins" />
                    <button type="submit" class="button button-primary">Reset logs</button>
                </form>
            </span>
        </h3>

        <div class="inside">
            <p>
                This information will be used to determine if your site is being victim of
                <a href="https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing"
                target="_blank">Password Guessing Brute Force Attacks</a>. These logs will be
                accumulated and the plugin will send a report via email if there are more than
                <code>%%SUCURI.FailedLogins.MaxFailedLogins%%</code> failed login attempts during
                the same hour, you can change this number from <a href="%%SUCURI.URL.Settings%%#general">here</a>.
                <strong>Note.</strong> Some <em>"Two-Factor Authentication"</em> plugins do not
                follow the same rules that WordPress have to report failed login attempts, so
                you may not see all the attempts in this panel if you have one of these plugins
                installed.
            </p>

            <div class="sucuriscan-inline-alert-warning sucuriscan-%%SUCURI.FailedLogins.WarningVisibility%%">
                <p>
                    The option to alert possible <a href="https://kb.sucuri.net/definitions/attacks/brute-force/password-guessing"
                    target="_blank">Password Guessing Brute Force Attacks</a> is disabled, you will
                    not receive email reports with the attempts collected during the attacks, but
                    you will continue receiving the alerts of failed logins if you have enabled that
                    option. Go to the <a href="%%SUCURI.URL.Settings%%#notifications">alert
                    settings</a> panel to change this configuration.
                </p>
            </div>

            <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.FailedLogins.CollectPasswordsVisibility%%">
                <p>
                    If you mistype your password the plugin will log the username and password in
                    the security logs. If someone get access to your API key, or your server fails
                    to process the PHP files (which is not usual but may happen) then an attacker
                    may steal your credentials and get access to your site. Change this from the <a
                    href="%%SUCURI.URL.Settings%%#general">general settings</a>
                </p>
            </div>

            <form method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

                <table class="wp-list-table widefat sucuriscan-table sucuriscan-lastlogins-failed">
                    <thead>
                        <tr>
                            <th class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                                <input id="cb-select-all-1" type="checkbox">
                            </th>
                            <th class="manage-column">User</th>
                            <th class="manage-column">Password</th>
                            <th class="manage-column">IP Address</th>
                            <th class="manage-column">Date/Time</th>
                            <th class="manage-column" width="300">User-Agent</th>
                        </tr>
                    </thead>

                    <tbody>
                        %%%SUCURI.FailedLogins.List%%%

                        <tr class="sucuriscan-%%SUCURI.FailedLogins.NoItemsVisibility%%">
                            <td colspan="6">
                                <em>No logs so far.</em>
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

                <div class="sucuriscan-recipient-form">
                    <button type="submit" class="button button-primary">Block Selected Users</button>
                </div>
            </form>
        </div>
    </div>
</div>
