
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
                Note that the virtual protection added by the plugin to these files is not bullet
                proof, it may be bypassed and depending on the configuration of the server it may
                leak information, but this is better than to store the data in the database and
                wait for a SQL injection to be used to attack the rest of the site.
            </p>
        </div>

        <div class="sucuriscan-inline-alert-info">
            <p>
                There are some entries in the options table that will be moved to a plain text
                file during the development of the next version of the plugin, this is part of
                a plan to include a way to import and export the settings of this extension to
                other sites in an easy way. This is necessary as importing data into a database
                may open security holes <em>(depending on how the code is written)</em> to reduce
                the risk we will use plain text files which makes things a bit safer.
            </p>
        </div>

        <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
            <span class="sucuriscan-monospace">%%SUCURI.DatastorePath%%</span>
        </div>

        <p>
            Some people may prefer to use a folder that is not in the document root of the
            website to add another layer of protection to the data, feel free to change this
            path if you want, make sure to use absolute paths.
        </p>

        <form action="%%SUCURI.URL.Settings%%" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <span class="sucuriscan-input-group">
                <label>Data Storage Path:</label>
                <input type="text" name="sucuriscan_datastore_path" class="input-text" />
            </span>
            <button type="submit" class="button-primary">Proceed</button>
        </form>
    </div>
</div>
