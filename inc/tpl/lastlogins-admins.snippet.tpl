
<tr>
    <td><a href="mailto:%%SUCURI.AdminUsers.Email%%">%%SUCURI.AdminUsers.Username%%</a></td>
    <td>%%SUCURI.AdminUsers.RegisteredAt%%</td>
    <td class="adminusers-lastlogin">
        <div class="sucuriscan-%%SUCURI.AdminUsers.NoLastLogins%%">
            <i>No data available.</i>
        </div>

        <table class="widefat sucuriscan-admins-lastlogins sucuriscan-%%SUCURI.AdminUsers.NoLastLoginsTable%%">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Date &amp; Time</th>
                </tr>
            </thead>
            <tbody>
                %%%SUCURI.AdminUsers.LastLogins%%%
            </tbody>
        </table>
    </td>
    <td>
        <a href="%%SUCURI.AdminUsers.UserURL%%" target="_blank" class="button-primary">Edit</a>
    </td>
</tr>
