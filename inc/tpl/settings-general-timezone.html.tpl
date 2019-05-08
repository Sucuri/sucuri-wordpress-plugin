
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Timezone Override}}</h3>

    <div class="inside">
        <p>{{This option defines the timezone that will be used through out the entire plugin to print the dates and times whenever is necessary. This option also affects the date and time of the logs visible in the audit logs panel which is data that comes from a remote server configured to use Eastern Daylight Time (EDT). WordPress offers an option in the general settings page to allow you to configure the timezone for the entire website, however, if you are experiencing problems with the time in the audit logs, this option will help you fix them.}}</p>

        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>{{Timezone:}}</label>
                <select name="sucuriscan_timezone">
                    %%%SUCURI.Timezone.Dropdown%%%
                </select>
                <button type="submit" class="button button-primary">{{Submit}}</button>
                <span><em>(%%SUCURI.Timezone.Example%%)</em></span>
            </fieldset>
        </form>
    </div>
</div>
