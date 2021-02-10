
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Allow Blocked PHP Files}}</h3>

    <div class="inside">
        <p>{{After you apply the hardening in either the includes, content, and/or uploads directories, the plugin will add a rule in the access control file to deny access to any PHP file located in these folders. This is a good precaution in case an attacker is able to upload a shell script. With a few exceptions the <em>"index.php"</em> file is the only one that should be publicly accessible, however many theme/plugin developers decide to use these folders to process some operations. In this case applying the hardening <strong>may break</strong> their functionality.}}</p>

        <form action="%%SUCURI.URL.Settings%%#hardening" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>{{File Path:}}</label>
                <input type="text" name="sucuriscan_hardening_allowlist" placeholder="e.g. wp-tinymce.php" data-cy="sucuriscan_hardening_allowlist_input" />
                <select name="sucuriscan_hardening_folder" data-cy="sucuriscan_hardening_allowlist_select">
                    %%%SUCURI.HardeningAllowlist.AllowedFolders%%%
                </select>
                <button type="submit" class="button button-primary" data-cy="sucuriscan_hardening_allowlist_submit">{{Submit}}</button>
            </fieldset>
        </form>

        <hr>

        <form action="%%SUCURI.URL.Settings%%#hardening" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-hardening-allowlist-table">
                <thead>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">{{Select All}}</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th class="manage-column">{{File Path}}</th>
                    <th class="manage-column">{{Directory}}</th>
                    <th class="manage-column">{{Pattern}}</th>
                </thead>

                <tbody>
                    %%%SUCURI.HardeningAllowlist.List%%%

                    <tr class="sucuriscan-%%SUCURI.HardeningAllowlist.NoItemsVisibility%%">
                        <td colspan="4">
                            <em>{{no data available}}</em>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="submit" class="button button-primary">{{Delete}}</button>
        </form>
    </div>
</div>
