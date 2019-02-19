
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Logged-in Users}}</h3>

    <div class="inside">
        <p>{{Here you can see a list of the users that are currently logged-in.}}</p>

        <table class="wp-list-table widefat sucuriscan-loggedin-users">
            <thead>
                <tr>
                    <th colspan="6">{{Logged-in Users}}</th>
                </tr>

                <tr>
                    <th>{{ID}}</th>
                    <th>{{Username}}</th>
                    <th>{{Last Activity}}</th>
                    <th>{{Registered}}</th>
                    <th>{{IP Address}}</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.LoggedInUsers.List%%%
            </tbody>
        </table>
    </div>
</div>
