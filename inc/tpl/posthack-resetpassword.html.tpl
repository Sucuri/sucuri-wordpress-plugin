
<div class="sucuriscan-panelstuff sucuriscan-reset-users-password">
    <div class="postbox">
        <div class="inside">
            <form method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_reset_password" value="1" />

                <p>
                    You can generate a new random password for the user accounts that you select
                    from the list. An email with the new password will be sent to the email address
                    of each chosen users.
                </p>

                <div class="sucuriscan-inline-alert-warning">
                    <p>
                        If you choose to change the password of your own user, then your current session
                        will expire immediately. You will need to log into the admin panel with the new
                        password that will be sent to your email. If you are unsure of this, do not
                        select your account from the list.
                    </p>
                </div>

                <table class="wp-list-table widefat sucuriscan-table">
                    <thead>
                        <tr>
                            <th class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                                <input id="cb-select-all-1" type="checkbox">
                            </th>
                            <th class="manage-column">User</th>
                            <th class="manage-column">Email address</th>
                            <th class="manage-column">Registered</th>
                            <th class="manage-column">Roles</th>
                        </tr>
                    </thead>

                    <tbody>
                        %%%SUCURI.ResetPassword.UserList%%%

                        <tr class="sucuriscan-%%SUCURI.ResetPassword.PaginationVisibility%%">
                            <td colspan="4">
                                <ul class="sucuriscan-pagination">
                                    %%%SUCURI.ResetPassword.PaginationLinks%%%
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p>
                    <label>
                        <input type="hidden" name="sucuriscan_process_form" value="0" />
                        <input type="checkbox" name="sucuriscan_process_form" value="1" />
                        <span>I understand that this operation can not be reverted.</span>
                    </label>
                </p>

                <input type="submit" value="Reset User Password" class="button button-primary" />
            </form>
        </div>
    </div>
</div>
