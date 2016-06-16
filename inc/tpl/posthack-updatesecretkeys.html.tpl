
<div class="sucuriscan-panelstuff sucuriscan-update-security-keys">
    <div class="postbox">
        <div class="inside">
            <p>
                The secret or security keys are a list of constants added to your site to ensure
                better encryption of information stored in the user's cookies. A secret key
                makes your site harder to hack and access by adding random elements to the
                password. You do not have to remember the keys, just write a random,
                complicated, and long string in the <code>wp-config.php</code> file. You can
                change these keys at any point in time to invalidate all existing cookies,
                forcing all users to login again.
            </p>

            <div class="sucuriscan-inline-alert-warning">
                <p>Your session will expire immediately after the security keys are changed.</p>
            </div>

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-security-keys-table">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.SecurityKeys.List%%%
                </tbody>
            </table>

            <form method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_update_wpconfig" value="1" />

                <p>
                    <label>
                        <input type="hidden" name="sucuriscan_process_form" value="0" />
                        <input type="checkbox" name="sucuriscan_process_form" value="1" />
                        <span>I understand that this operation can not be reverted.</span>
                    </label>
                </p>

                <input type="submit" value="Generate New Security Keys" class="button button-primary" />
            </form>

            <div class="sucuriscan_wpconfig_keys_updated sucuriscan-monospace sucuriscan-%%SUCURI.WPConfigUpdate.Visibility%%">
                <textarea>%%SUCURI.WPConfigUpdate.NewConfig%%</textarea>
            </div>
        </div>
    </div>
</div>
