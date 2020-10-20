
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Import &amp; Export Settings}}</h3>

    <div class="inside">
        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <p>{{Copy the JSON-encoded data from the box below, go to your other websites and click the <em>"Import"</em> button in the settings page. The plugin will start using the same settings from this website. Notice that some options are omitted as they contain values specific to this website. To import the settings from another website into this one, replace the JSON-encoded data in the box below with the JSON-encoded data exported from the other website, then click the button <em>"Import"</em>. Notice that some options will not be imported to reduce the security risk of writing arbitrary data into the disk.}}</p>

            <textarea name="sucuriscan_settings" class="sucuriscan-full-textarea sucuriscan-monospace" data-cy="sucuriscan_import_export_settings_textarea">%%SUCURI.Export%%</textarea>

            <p>
                <label>
                    <input type="hidden" name="sucuriscan_process_form" value="0" />
                    <input type="checkbox" name="sucuriscan_process_form" value="1" data-cy="sucuriscan_import_export_settings_checkbox" />
                    <span>{{I understand that this operation cannot be reverted.}}</span>
                </label>
            </p>

            <button type="submit" name="sucuriscan_import" class="button button-primary" data-cy="sucuriscan_import_export_settings_submit">{{Submit}}</button>
        </form>
    </div>
</div>
