
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Ignore Files And Folders During The Scans</h3>

    <div class="inside">
        <p>
            Use this tool to select the files and/or folders that are too heavy
            for the scanner to process. These are usually folders with images,
            media files like videos and audios, backups and &mdash; in general
            &mdash; anything that is not code-related. Ignoring these files or
            folders will reduce the memory consumption of the PHP script.
        </p>

        <script type="text/javascript">
        /* global jQuery */
        /* jshint camelcase: false */
        jQuery(function ($) {
            $('.sucuriscan-ignorescanning tbody').html(
                '<tr><td colspan="3"><span>Loading <em>(may take' +
                ' several seconds)</em>...</span></td></tr>'
            );
            $.post('%%SUCURI.AjaxURL.Dashboard%%', {
                action: 'sucuriscan_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action: 'get_ignored_files',
            }, function (data) {
                $('.sucuriscan-ignorescanning tbody').html(data);
            });
        });
        </script>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
            <input type="hidden" name="sucuriscan_ignorescanning_action" value="ignore" />

            <fieldset class="sucuriscan-clearfix">
                <label>Ignore One Single File:</label>
                <input type="text" name="sucuriscan_ignorescanning_file" placeholder="e.g. /private/cert.crt" />
                <button type="submit" class="button button-primary">Proceed</button>
            </fieldset>
        </form>

        <hr>

        <form action="%%SUCURI.URL.Settings%%#scanner" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-ignorescanning">
                <thead>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th class="manage-column">Directory or File Path</th>
                    <th class="manage-column">Status</th>
                </thead>

                <tbody>
                </tbody>
            </table>

            <div class="sucuriscan-recipient-form">
                <label>
                    <select name="sucuriscan_ignorescanning_action">
                        <option value="">Choose Action</option>
                        <option value="ignore">Ignore Selected Files And Folders</option>
                        <option value="unignore">Scan Selected Files And Folders</option>
                    </select>
                </label>

                <button type="submit" class="button button-primary">Proceed</button>
            </div>
        </form>
    </div>
</div>
