
<div class="sucuriscan-panel sucuriscan-integrity sucuriscan-integrity-incorrect">
    <div class="sucuriscan-clearfix">
        <div class="sucuriscan-pull-left sucuriscan-integrity-left">
            <h2 class="sucuriscan-title">WordPress Integrity</h2>

            <p>
                We inspect your WordPress installation and look for modifications
                on the core files as provided by WordPress.org. Files located in
                the root directory, wp-admin and wp-includes will be compared against
                the files distributed with v%%SUCURI.WordPressVersion%%; all files with
                inconsistencies will be listed here. Any changes might indicate a hack.
            </p>
        </div>

        <div class="sucuriscan-pull-right sucuriscan-integrity-right">
            <h2 class="sucuriscan-subtitle">All Core WordPress Files Are Correct</h2>

            <p>
                We identified that some of your WordPress core files were modified.
                That might indicate a hack or a broken file on your installation.
                If you are experiencing other malware issues, please use a
                <a href="https://sucuri.net/website-security/malware-removal"
                target="_blank">Server Side Scanner</a>.
            </p>

            <p><a href="%%SUCURI.URL.Settings%%#scanner">Review False/Positives</a></p>
        </div>
    </div>

    %%%SUCURI.SiteCheck.Details%%%

    <form action="%%SUCURI.URL.Dashboard%%" method="post" class="sucuriscan-%%SUCURI.Integrity.BadVisibility%%">
        <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

        <table class="wp-list-table widefat sucuriscan-table">
            <thead>
                <tr>
                    <th colspan="5">WordPress Integrity (%%SUCURI.Integrity.ListCount%% files)</th>
                </tr>

                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th width="20" class="manage-column">&nbsp;</th>
                    <th width="100" class="manage-column">File Size</th>
                    <th width="200" class="manage-column">Modified At</th>
                    <th class="manage-column">File Path</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.Integrity.List%%%
            </tbody>
        </table>

        <div class="sucuriscan-inline-alert-info">
            <p>
                Marking one or more files as fixed will force the plugin to ignore them during
                the next scan, very useful when you find false positives. Additionally you can
                restore the original content of the core files that appear as modified or deleted,
                this will tell the plugin to download a copy of the original files from the official
                <a href="https://core.svn.wordpress.org/tags/" target="_blank">WordPress repository</a>.
                Deleting a file is an irreversible action, be careful.
            </p>
        </div>

        <p>
            <label>
                <input type="hidden" name="sucuriscan_process_form" value="0" />
                <input type="checkbox" name="sucuriscan_process_form" value="1" />
                <span>I understand that this operation can not be reverted.</span>
            </label>
        </p>

        <fieldset class="sucuriscan-clearfix">
            <label>Integrity Action:</label>
            <select name="sucuriscan_integrity_action">
                <option value="fixed">Mark as Fixed</option>
                <option value="restore">Restore File</option>
                <option value="delete">Delete File</option>
            </select>
            <button type="submit" class="button button-primary">Proceed</button>
        </fieldset>
    </form>
</div>
