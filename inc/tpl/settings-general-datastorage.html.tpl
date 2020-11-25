
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Data Storage}}</h3>

    <div class="inside">
        <p>{{This is the directory where the plugin will store the security logs, the list of files marked as fixed in the core integrity tool, the cache for the malware scanner and 3rd-party plugin metadata. The plugin requires write permissions in this directory as well as the files contained in it. If you prefer to keep these files in a non-public directory <em>(one level up the document root)</em> please define a constant in the <em>"wp-config.php"</em> file named <em>"SUCURI_DATA_STORAGE"</em> with the absolute path to the new directory.}}</p>
    </div>

    <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
        <span class="sucuriscan-monospace">%%SUCURI.Storage.Path%%</span>
    </div>

    <form action="%%SUCURI.URL.Settings%%#general" method="post">
        <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
        <input type="hidden" name="sucuriscan_reset_storage" value="1" />

        <table class="wp-list-table widefat sucuriscan-table" data-cy="sucuriscan_general_datastore_table">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">{{Select All}}</label>
                        <input id="cb-select-all-1" type="checkbox" data-cy="sucuriscan_general_datastore_delete_checkbox">
                    </td>
                    <th class="manage-column">{{File Path}}</th>
                    <th class="manage-column">{{File Size}}</th>
                    <th class="manage-column">{{Status}}</th>
                    <th class="manage-column">{{Writable}}</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.Storage.Files%%%
            </tbody>
        </table>

        <p>
            <button type="submit" class="button button-primary" data-cy="sucuriscan_general_datastore_delete_button">{{Delete}}</button>
        </p>
    </form>
</div>
