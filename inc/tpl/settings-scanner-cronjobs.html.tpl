
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.Cronjobs@@</h3>

    <div class="inside">
        <p>@@SUCURI.ScannerDescription@@</p>

        <div class="sucuriscan-inline-alert-error sucuriscan-%%SUCURI.NoSPL.Visibility%%">
            <p>@@SUCURI.ScannerWithoutSPL@@</p>
        </div>

        <p>@@SUCURI.CronjobsInfo@@</p>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-wpcron-list">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th>@@SUCURI.Name@@</th>
                        <th>@@SUCURI.Schedule@@</th>
                        <th>@@SUCURI.NextDue@@</th>
                        <th>@@SUCURI.Arguments@@</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.Cronjobs.List%%%
                </tbody>
            </table>

            <fieldset class="sucuriscan-clearfix">
                <label>@@SUCURI.Action@@:</label>
                <select name="sucuriscan_cronjob_action">
                    %%%SUCURI.Cronjob.Schedules%%%
                </select>
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </fieldset>
        </form>
    </div>
</div>
