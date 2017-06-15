
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.FalsePositives@@</h3>

    <div class="inside">
        <p>@@SUCURI.FalsePositivesInfo@@</p>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_reset_integrity_cache" value="1" />

            <table class="wp-list-table widefat sucuriscan-table">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th>@@SUCURI.Reason@@</th>
                        <th>@@SUCURI.IgnoredAt@@</th>
                        <th>@@SUCURI.FilePath@@</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.IgnoredFiles%%%

                    <tr class="sucuriscan-%%SUCURI.NoFilesVisibility%%">
                        <td colspan="4">
                            <em>@@SUCURI.NoData@@</em>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p>
                <button type="submit" class="button button-primary">@@SUCURI.FalsePositivesUnignore@@</button>
            </p>
        </form>
    </div>
</div>
