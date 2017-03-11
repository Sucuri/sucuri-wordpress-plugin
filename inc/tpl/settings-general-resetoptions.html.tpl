
<div class="postbox">
    <h3>Reset Security Logs, Hardening and Settings</h3>

    <div class="inside">
        <p>
            This action will trigger the deactivation / uninstallation process
            of the plugin. All local security logs, hardening and settings will
            be deleted. Notice that the security logs stored in the API service
            will not be deleted, this is to prevent tampering from a malicious
            user. You can request a new API key if you want to start from
            scratch.
        </p>

        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <p>
                <label>
                    <input type="hidden" name="sucuriscan_process_form" value="0" />
                    <input type="checkbox" name="sucuriscan_process_form" value="1" />
                    <span>I understand that this operation can not be reverted.</span>
                </label>
            </p>
            <button type="submit" name="sucuriscan_reset_options" class="button-primary button-danger">Reset Everything</button>
        </form>
    </div>
</div>
