
<div id="poststuff" class="sucuriscan-hardening-whitelist">
    <div class="postbox sucuriscan-border sucuriscan-table-description">
        <h3>Whitelist Blocked PHP Files</h3>

        <div class="inside">
            <p>
                After you apply the hardening in either the includes, content, and/or upload
                directories the plugin will add a rule in the access control file to deny access
                to any PHP file located in these folders, this is a good precaution in case that
                an attacker is able to upload a shell script; with a few exceptions the
                <em>"index.php"</em> is the only one that should be publicly accessible, however
                many theme/plugin developers decide to use these folders to process some
                operations, in this case applying the hardening <strong>may break</strong> their
                functionality.
            </p>

            <div class="sucuriscan-inline-alert-info">
                <p>
                    Note that whitelisted files are global inside the selected directory, this means
                    that if you whitelist a file named <em>"thumbnail.php"</em> it will match every
                    file with the same name in all the sub-folders. If you want something more
                    specific read the <a href="https://httpd.apache.org/docs/2.4/mod/core.html"
                    target="_blank">official documentation</a>.
                </p>
            </div>

            <div class="sucuriscan-inline-alert-warning">
                <p>
                    Be warned that whitelisting a PHP file with vulnerabilities will open security
                    holes in your website that can be exploited by malicious users. If you do not
                    fully understand the purpose of this form and/or do not know what whitelisting
                    a PHP file means then ask for support in the <a target="_blank"
                    href="https://wordpress.org/support/plugin/sucuri-scanner">forums</a>.
                </p>
            </div>

            <form action="%%SUCURI.URL.Hardening%%#whitelist" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

                <div class="sucuriscan-clearfix">
                    <div class="sucuriscan-pull-left">
                        <label>Whitelist PHP file:</label>
                    </div>
                    <div class="sucuriscan-pull-left">
                        <input type="text" name="sucuriscan_hardening_whitelist" placeholder="e.g. wp-tinymce.php" />
                    </div>
                    <div class="sucuriscan-pull-left">
                        <select name="sucuriscan_hardening_folder">
                            <option value="wp-includes">wp-includes</option>
                            <option value="wp-content">wp-content</option>
                            <option value="wp-content/uploads">wp-content/uploads</option>
                        </select>
                    </div>
                    <div class="sucuriscan-pull-left">
                        <button type="submit" class="button button-primary">Proceed</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<form action="%%SUCURI.URL.Hardening%%#whitelist" method="post">
    <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

    <table class="wp-list-table widefat sucuriscan-table sucuriscan-hardening-whitelist-table">
        <thead>
            <th class="manage-column column-cb check-column">
                <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                <input id="cb-select-all-1" type="checkbox">
            </th>
            <th class="manage-column">Filename</th>
            <th class="manage-column">Base Directory</th>
            <th class="manage-column">Regular Expression</th>
        </thead>

        <tbody>
            %%%SUCURI.HardeningWhitelist.List%%%

            <tr class="sucuriscan-%%SUCURI.HardeningWhitelist.NoItemsVisibility%%">
                <td colspan="4">
                    <em>List is empty.</em>
                </td>
            </tr>
        </tbody>

        <tfoot>
            <tr>
                <td colspan="4">
                    <button type="submit" class="button button-primary">Delete</button>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
