
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.WhitelistScript@@</h3>

    <div class="inside">
        <p>@@SUCURI.WhitelistScriptInfo@@</p>

        <form action="%%SUCURI.URL.Settings%%#hardening" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.FilePath@@:</label>
                <input type="text" name="sucuriscan_hardening_whitelist" placeholder="e.g. wp-tinymce.php" />
                <select name="sucuriscan_hardening_folder">
                    <option value="wp-includes">wp-includes</option>
                    <option value="wp-content">wp-content</option>
                    <option value="wp-content/uploads">wp-content/uploads</option>
                </select>
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>
        </form>

        <hr>

        <form action="%%SUCURI.URL.Settings%%#hardening" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-hardening-whitelist-table">
                <thead>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th class="manage-column">@@SUCURI.FilePath@@</th>
                    <th class="manage-column">@@SUCURI.Directory@@</th>
                    <th class="manage-column">@@SUCURI.Pattern@@</th>
                </thead>

                <tbody>
                    %%%SUCURI.HardeningWhitelist.List%%%

                    <tr class="sucuriscan-%%SUCURI.HardeningWhitelist.NoItemsVisibility%%">
                        <td colspan="4">
                            <em>@@SUCURI.NoData@@</em>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="submit" class="button button-primary">@@SUCURI.Delete@@</button>
        </form>
    </div>
</div>
