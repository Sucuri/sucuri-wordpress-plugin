
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Data Storage Path</h3>

    <div class="inside">
        <p>
            This is the directory where the plugin will store the security logs, the list of
            files marked as fixed in the core integrity tool, the cache for the malware
            scanner and 3rd-party plugin metadata. The directory is blocked from public
            visibility if <strong>and only if</strong> the site is being hosted by the
            Apache web server. Additionally, every PHP file has an exit point in its header
            to prevent the content to be printed.
        </p>

        <div class="sucuriscan-inline-alert-info">
            <p>
                The plugin requires write permissions in this directory as well
                as the files contained in it. If you prefer to keep these files
                in a non-public directory <em>(one level up the document root)
                </em> please define a constant in the <em>"wp-config.php"</em>
                file named <em>"SUCURI_DATA_STORAGE"</em> with the absolute path
                to the new directory.
            </p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span class="sucuriscan-monospace">%%SUCURI.Storage.Path%%</span>
        </div>

        <p>
            As of version 1.7.18 the plugin started using a plain text file named
            <em>"sucuri-settings.php"</em> to store its settings instead of the
            database, this was both a security measure and a mechanism to simplify
            the management of the settings for multisite installations. Options
            created in the database by previous versions of the plugin will be
            migrated to the settings file if it is writable, otherwise they will
            remain in the database until the user grants write permissions.
        </p>

        <form action="%%SUCURI.URL.Settings%%#general" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_reset_storage" value="1" />

            <table class="wp-list-table widefat sucuriscan-table">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th class="manage-column">File</th>
                        <th class="manage-column">Size</th>
                        <th class="manage-column">Existence</th>
                        <th class="manage-column">Write Permission</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.Storage.Files%%%
                </tbody>
            </table>

            <p>
                <button type="submit" class="button button-primary">Reset Files</button>
            </p>
        </form>
    </div>
</div>
