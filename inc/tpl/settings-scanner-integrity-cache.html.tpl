
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{WordPress Integrity (False Positives)}}</h3>

    <div class="inside">
        <p>{{Since the scanner doesn’t read the files during the execution of the integrity check, it is possible to find false positives. Files listed here have been marked as false positives and will be ignored by the scanner in subsequent scans.}}</p>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_reset_integrity_cache" value="1" />

            <table class="wp-list-table widefat sucuriscan-table" data-cy="sucuriscan_integrity_diff_false_positive_table">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">{{Select All}}</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th>{{Reason}}</th>
                        <th>{{Ignored At}}</th>
                        <th>{{File Path}}</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.IgnoredFiles%%%

                    <tr class="sucuriscan-%%SUCURI.NoFilesVisibility%%">
                        <td colspan="4">
                            <em>{{no data available}}</em>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p>
                <button type="submit" class="button button-primary" data-cy="sucuriscan_integrity_diff_false_positive_submit">{{Stop Ignoring the Selected Files}}</button>
            </p>
        </form>
    </div>
</div>
