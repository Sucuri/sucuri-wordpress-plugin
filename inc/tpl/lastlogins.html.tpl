
<div class="sucuriscan-tabs">
    <ul class="sucuriscan-clearfix sucuriscan-tabs-buttons">
        <li><a href="%%SUCURI.URL.Lastlogins%%#allusers" data-cy="sucuriscan_lastlogins_nav_all_users">{{All Users}}</a></li>
        <li><a href="%%SUCURI.URL.Lastlogins%%#admins" data-cy="sucuriscan_lastlogins_nav_admins">{{Admins}}</a></li>
        <li><a href="%%SUCURI.URL.Lastlogins%%#loggedin" data-cy="sucuriscan_lastlogins_nav_loggedin">{{Logged-in Users}}</a></li>
        <li><a href="%%SUCURI.URL.Lastlogins%%#failed" data-cy="sucuriscan_lastlogins_nav_failed">{{Failed Logins}}</a></li>
    </ul>

    <div class="sucuriscan-tabs-containers">
        <div id="sucuriscan-tabs-allusers">
            %%%SUCURI.LastLogins.AllUsers%%%
        </div>

        <div id="sucuriscan-tabs-admins">
            %%%SUCURI.LastLogins.Admins%%%
        </div>

        <div id="sucuriscan-tabs-loggedin">
            %%%SUCURI.LoggedInUsers%%%
        </div>

        <div id="sucuriscan-tabs-failed">
            %%%SUCURI.FailedLogins%%%
        </div>
    </div>
</div>
