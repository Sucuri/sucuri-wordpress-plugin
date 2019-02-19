
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Update Secret Keys}}</h3>

    <div class="inside">
        <p>{{The secret or security keys are a list of constants added to your site to ensure better encryption of information stored in the user’s cookies. A secret key makes your site harder to hack by adding random elements to the password. You do not have to remember the keys, just write a random, complicated, and long string in the <code>wp-config.php</code> file. You can change these keys at any point in time. Changing them will invalidate all existing cookies, forcing all logged in users to login again.}}</p>

        <div class="sucuriscan-inline-alert-error">
            <p>{{Your current session will expire once the form is submitted.}}</p>
        </div>

        <div class="sucuriscan_wpconfig_keys_updated sucuriscan-monospace sucuriscan-%%SUCURI.WPConfigUpdate.Visibility%%">
            <textarea class="sucuriscan-full-textarea" style="height:405px">%%SUCURI.WPConfigUpdate.NewConfig%%</textarea>
        </div>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-security-keys-table">
            <thead>
                <tr>
                    <th>{{Status}}</th>
                    <th>{{Name}}</th>
                    <th>{{Value}}</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.SecurityKeys.List%%%
            </tbody>
        </table>

        <form action="%%SUCURI.URL.Settings%%#posthack" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_update_wpconfig" value="1" />

            <p>
                <label>
                    <input type="hidden" name="sucuriscan_process_form" value="0" />
                    <input type="checkbox" name="sucuriscan_process_form" value="1" />
                    <span>{{I understand that this operation cannot be reverted.}}</span>
                </label>
            </p>

            <input type="submit" value="{{Generate New Security Keys}}" class="button button-primary" />
        </form>
    </div>
</div>
