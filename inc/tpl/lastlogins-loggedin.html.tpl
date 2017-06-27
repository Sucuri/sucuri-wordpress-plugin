
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.LoggedInUsers@@</h3>

    <div class="inside">
        <p>@@SUCURI.LoggedInUsersInfo@@</p>

        <table class="wp-list-table widefat sucuriscan-loggedin-users">
            <thead>
                <tr>
                    <th colspan="6">@@SUCURI.LoggedInUsers@@</th>
                </tr>

                <tr>
                    <th>ID</th>
                    <th>@@SUCURI.Username@@</th>
                    <th>@@SUCURI.LastActivity@@</th>
                    <th>@@SUCURI.Registered@@</th>
                    <th>@@SUCURI.RemoteAddr@@</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.LoggedInUsers.List%%%
            </tbody>
        </table>
    </div>
</div>
