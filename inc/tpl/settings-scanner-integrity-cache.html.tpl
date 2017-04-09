
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">WordPress Integrity (False/Positives)</h3>

    <div class="inside">
        <p>
            Since the scanner doesn't reads the files during the execution of the
            integrity check, it is possible to find false/positives. The scanner
            compares a hash generated from the file content but not the content
            in itself. If you include, for example, a new empty line in any of
            the core WordPress files the scanner will flag that file even if the
            modification is harmless. If a file is marked as <em>"added"</em> and
            after a manual check of its content you verify that the file is legit,
            you can mark it as fixed and the scanner will skip that file the next
            time it runs.
        </p>

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
                        <th>Reason</th>
                        <th>Ignored At</th>
                        <th>Line</th>
                    </tr>
                </thead>

                <tbody>
                    %%%SUCURI.IgnoredFiles%%%

                    <tr class="sucuriscan-%%SUCURI.NoFilesVisibility%%">
                        <td colspan="4">
                            <em>No files are being ignored.</em>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p>
                <button type="submit" class="button button-primary">Stop Ignoring the Selected Files</button>
            </p>
        </form>
    </div>
</div>
