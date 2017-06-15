
<tr>
    <td><a href="mailto:%%SUCURI.AdminUsers.Email%%">%%SUCURI.AdminUsers.Username%%</a></td>

    <td>%%SUCURI.AdminUsers.RegisteredAt%%</td>

    <td class="adminusers-lastlogin">
        <div class="sucuriscan-%%SUCURI.AdminUsers.NoLastLogins%%">
            <em>@@SUCURI.NoData@@</em>
        </div>

        <table class="widefat sucuriscan-admins-lastlogins sucuriscan-%%SUCURI.AdminUsers.NoLastLoginsTable%%">
            <thead>
                <tr>
                    <th>@@SUCURI.RemoteAddr@@</th>
                    <th>@@SUCURI.Datetime@@</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.AdminUsers.LastLogins%%%
            </tbody>
        </table>
    </td>

    <td>
        <a href="%%SUCURI.AdminUsers.UserURL%%" target="_blank" class="button button-primary">@@SUCURI.Edit@@</a>
    </td>
</tr>
