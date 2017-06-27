
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.DataStorage@@</h3>

    <div class="inside">
        <p>@@SUCURI.DataStorageInfo@@</p>
    </div>

    <div class="sucuriscan-hstatus sucuriscan-hstatus-2">
        <span class="sucuriscan-monospace">%%SUCURI.Storage.Path%%</span>
    </div>

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
                    <th class="manage-column">@@SUCURI.FilePath@@</th>
                    <th class="manage-column">@@SUCURI.FileSize@@</th>
                    <th class="manage-column">@@SUCURI.Status@@</th>
                    <th class="manage-column">@@SUCURI.Writable@@</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.Storage.Files%%%
            </tbody>
        </table>

        <p>
            <button type="submit" class="button button-primary">@@SUCURI.Delete@@</button>
        </p>
    </form>
</div>
