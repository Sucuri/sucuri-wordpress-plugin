
<div class="postbox">
    <h3>Ignore Scanning for Folders</h3>

    <div class="inside">
        <p>
            Selecting one or more directories from the list will force the plugin to ignore
            the monitoring of the sub-folders and files inside these directories during the
            execution of any of the file system scanners. This will applies to all the scanners
            <em>(general scanner, modified files, integrity checks, error logs)</em>.
        </p>

        <script type="text/javascript">
        jQuery(function($){
            $('.sucuriscan-ignorescanning tbody').html(
                '<tr><td colspan="3"><span>Loading <em>(may take '
                + 'several seconds)</em>...</span></td></tr>'
            );
            $.post('%%SUCURI.AjaxURL.Settings%%', {
                action: 'sucuriscan_settings_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action: 'get_ignored_files',
            }, function(data){
                $('.sucuriscan-ignorescanning tbody').html(data);
            });
        });
        </script>

        <form action="%%SUCURI.URL.Settings%%#ignorescanning" method="post">
            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-ignorescanning">
                <thead>
                    <th class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </th>
                    <th class="manage-column">Directory or File Path</th>
                    <th class="manage-column">Status</th>
                </thead>

                <tbody>
                </tbody>
            </table>

            <div class="sucuriscan-recipient-form">
                <label>
                    <select name="sucuriscan_ignorescanning_action">
                        <option value="">Choose action</option>
                        <option value="ignore">Ignore items</option>
                        <option value="unignore">Un-ignore items</option>
                    </select>
                </label>

                <button type="submit" class="button button-primary">Proceed</button>
            </div>
        </form>
    </div>
</div>
