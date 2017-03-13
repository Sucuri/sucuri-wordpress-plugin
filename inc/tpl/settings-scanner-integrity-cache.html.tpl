
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Core Integrity Checks - Marked As Fixed</h3>

    <div class="inside">
        <p>
            The scanner is prone to inconsistencies due to the diversity of configurations
            that a hosting provider may have in their servers, many of them add files in the
            document root of the websites with information associated to 3rd-party services
            that they offer or programs that they are running in their system. These files
            will be flagged by the plugin as <em>"added"</em> because they are not part of
            the official WordPress packages, but it is clear that they are false/positives.
            Some of these files are being ignored by the plugin to reduce the noise in the
            integrity checks, but there are many others that are not, you will have to
            select them and mark them as fixed if you believe they are harmless, this action
            will force the plugin to ignore them in future scans.
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
