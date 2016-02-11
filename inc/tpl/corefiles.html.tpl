
<div class="sucuriscan-inline-alert-updated sucuriscan-%%SUCURI.CoreFiles.GoodVisibility%%">
    <p>
        Your WordPress core files are clean and were not modified.
    </p>
</div>

<div class="sucuriscan-inline-alert-warning sucuriscan-%%SUCURI.CoreFiles.NotFixableVisibility%%">
    <p>
        Files marked with the text <em>"not fixable"</em> are files without write
        permissions, you have to fix them manually.
    </p>
</div>

<div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.CoreFiles.DisabledVisibility%%">
    <p>
        The file scanner to check the integrity of the project is disabled.
    </p>
</div>

<div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.CoreFiles.FailureVisibility%%">
    <p>
        Error retrieving the <a href="%%SUCURI.CoreFiles.RemoteChecksumsURL%%" target="_blank">
        WordPress core hashes</a>. The information used by the plugin to determine the
        integrity of the core files is retrieved and controlled by WordPress. Any error
        message related with this tool is likely related with a modification in their
        API service that is not supported yet. It is also possible that your website is
        not able to communicate with this server due to a missing HTTP transport tool.
    </p>
</div>

<form action="%%SUCURI.URL.Home%%" method="post">
    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

    <table class="wp-list-table widefat sucuriscan-table sucuriscan-corefiles sucuriscan-%%SUCURI.CoreFiles.BadVisibility%%">
        <thead>
            <tr>
                <th colspan="5">Core Integrity (%%SUCURI.CoreFiles.ListCount%% files)</th>
            </tr>

            <tr>
                <th class="manage-column column-cb check-column">
                    <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                    <input id="cb-select-all-1" type="checkbox">
                </th>
                <th width="80" class="manage-column">Status</th>
                <th width="100" class="manage-column">File Size</th>
                <th width="200" class="manage-column">Modified At</th>
                <th class="manage-column">File Path</th>
            </tr>
        </thead>

        <tbody>
            %%%SUCURI.CoreFiles.List%%%
        </tbody>
    </table>

    <div class="sucuriscan-%%SUCURI.CoreFiles.BadVisibility%%">
        <p>
            Marking one or more files as fixed will force the plugin to ignore them during
            the next scan, very useful when you find false positives. Additionally you can
            restore the original content of the core files that appear as modified or deleted,
            this will tell the plugin to download a copy of the original files from the official
            <a href="https://core.svn.wordpress.org/tags/" target="_blank">WordPress repository</a>.
            Deleting a file is an irreversible action, be careful.
        </p>

        <div class="sucuriscan-recipient-form">
            <p>
                <label>
                    <input type="hidden" name="sucuriscan_process_form" value="0" />
                    <input type="checkbox" name="sucuriscan_process_form" value="1" />
                    <span>I understand that this operation can not be reverted.</span>
                </label>
            </p>

            <span class="sucuriscan-input-group">
                <label>Choose Action:</label>
                <select name="sucuriscan_integrity_action">
                    <option value="fixed">Mark as fixed</option>
                    <option value="restore">Restore source</option>
                    <option value="delete">Delete file</option>
                </select>
            </span>
            <button type="submit" class="button button-primary">Proceed</button>
        </div>
    </div>
</form>
