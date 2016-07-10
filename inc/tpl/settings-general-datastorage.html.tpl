
<div class="postbox">
    <h3>Data Storage Path</h3>

    <div class="inside">
        <p>
            This is the directory where the plugin will store the security logs, the list of
            files marked as fixed in the core integrity tool, the cache for the malware
            scanner and 3rd-party plugin metadata. The directory is blocked from public
            visibility if <strong>and only if</strong> the site is being hosted by the
            Apache web server. Additionally, every PHP file has an exit point in its header
            to prevent the content to be printed.
        </p>

        <div class="sucuriscan-inline-alert-warning">
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
            <span class="sucuriscan-monospace">%%SUCURI.DatastorePath%%</span>
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

        <div class="sucuriscan-inline-alert-info">
            <p>
                Add this <code>define('SUCURI_SETTINGS_IN', 'database');</code>
                in the configuration file if you want to keep using the database.
                However, we encourage you to keep using the plain text files as
                this guarantees that the automated tests will cover all the code
                that powers the plugin.
            </p>
        </div>

        <table class="wp-list-table widefat sucuriscan-table">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </th>
                    <th class="manage-column">File</th>
                    <th width="70" class="manage-column">Exists?</th>
                    <th width="90" class="manage-column">Writable?</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.DataStorage.Files%%%
            </tbody>
        </table>
    </div>
</div>
