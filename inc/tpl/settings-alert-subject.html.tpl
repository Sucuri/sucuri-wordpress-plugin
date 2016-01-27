
<div class="postbox">
    <h3>Alert Subject</h3>

    <div class="inside">
        <p>
            Format of the subject for the email alerts, by default the plugin will use the
            website name and the event identifier that is being reported, you can use this
            panel to include the IP address of that user that triggered the event and some
            additional data. You can create filters in your email client creating a custom
            email subject using the pseudo-tags shown below.
        </p>

        <form action="%%SUCURI.URL.Settings%%#notifications" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <ul class="sucuriscan-subject-formats">
                %%%SUCURI.AlertSettings.Subject%%%

                <li>
                    <label>
                        <input type="radio" name="sucuriscan_email_subject" value="custom" %%SUCURI.AlertSettings.CustomChecked%% />
                        <span>Custom format</span>
                        <input type="text" name="sucuriscan_custom_email_subject" value="%%SUCURI.AlertSettings.CustomValue%%" />
                    </label>
                </li>
            </ul>

            <div class="sucuriscan-recipient-form">
                <button type="submit" class="button-primary">Save</button>
            </div>
        </form>
    </div>
</div>
