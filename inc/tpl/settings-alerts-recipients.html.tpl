
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Alerts Recipient}}</h3>

    <div class="inside">
        <p>{{By default, the plugin will send the email alerts to the primary admin account, the same account created during the installation of WordPress in your web server. You can add more people to the list, they will receive a copy of the same security alerts.}}</p>

        <form action="%%SUCURI.URL.Settings%%#alerts" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <fieldset class="sucuriscan-clearfix">
                <label>{{E-mail:}}</label>
                <input type="text" name="sucuriscan_recipient" placeholder="{{e.g. user@example.com}}" />
                <button type="submit" name="sucuriscan_save_recipient" class="button button-primary">{{Submit}}</button>
            </fieldset>

            <table class="wp-list-table widefat sucuriscan-table">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">{{Select All}}</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="manage-column">{{E-mail}}</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.Alerts.Recipients%%%
                </tbody>
            </table>

            <button type="submit" name="sucuriscan_delete_recipients" class="button button-primary">{{Delete}}</button>
            <button type="submit" name="sucuriscan_debug_email" value="1" class="button button-primary">{{Test Alerts}}</button>
        </form>
    </div>
</div>
