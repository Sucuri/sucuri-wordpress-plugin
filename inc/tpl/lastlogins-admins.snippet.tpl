
<tr>
    <td><a href="mailto:%%SUCURI.AdminUsers.Email%%">%%SUCURI.AdminUsers.Username%%</a></td>

    <td>%%SUCURI.AdminUsers.RegisteredAt%%</td>

    <td class="adminusers-lastlogin">
        <div class="sucuriscan-%%SUCURI.AdminUsers.NoLastLogins%%">
            <em>{{no data available}}</em>
        </div>

        <table class="widefat sucuriscan-admins-lastlogins sucuriscan-%%SUCURI.AdminUsers.NoLastLoginsTable%%">
            <thead>
                <tr>
                    <th>{{IP Address}}</th>
                    <th>{{Date/Time}}</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.AdminUsers.LastLogins%%%
            </tbody>
        </table>
    </td>

    <td>
        <a href="%%SUCURI.AdminUsers.UserURL%%" target="_blank" class="button button-primary" rel="noopener">{{Edit User Profile}}</a>
    </td>
</tr>
