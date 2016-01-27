
<div class="postbox">
    <h3>Reset Options</h3>

    <div class="inside">
        <p>
            This action will delete all the entries inserted by the plugin in the options
            table of the current database, including the API key. Make sure that you
            understand the consequences of this operation before you proceed, this can not
            be reverted and you may lose access to the data that was already collected by
            the plugin. This will also revert the hardening applied in the WordPress core
            directories, but not the hardening applied to other parts of the site as they
            can not be easily reverted, refer to the hardening page for more information.
        </p>

        <p>
            The information stored in the security logs will be deleted as well, but the
            information that was previously sent to the API service will remain untouched
            as there is no easy way to guarantee that this action is not being requested
            by a malicious user looking for a way to hide his fingerprints after an attack.
            If you are the real owner of this website and want to delete the information
            stored in the Sucuri servers then send an email to our support team and we may
            consider the case.
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
