
<div class="postbox">
    <h3>Error Logs - File Reader</h3>

    <div class="inside">
        <p>
            Note that if the log file is not empty but the table is, it means that the
            format of the logs used by the web server is not supported by the scanner,
            you can try to increase the number of lines processed or ask your hosting
            provider to change the format of the PHP error log generator.
        </p>

        <div class="sucuriscan-inline-alert-warning">
            <p>
                Note that only the main error log file <em>(usually located in the document
                root)</em> will be read, parsed, and listed below, if there are more log files
                in sub-directories they will be ignored.
            </p>
        </div>

        <script type="text/javascript">
        jQuery(function($){
            $('.sucuriscan-errorlogs-list tbody').html(
                '<tr><td colspan="5"><span>Loading <em>(may take '
                + 'several seconds)</em>...</span></td></tr>'
            );
            $.post('%%SUCURI.AjaxURL.Settings%%', {
                action: 'sucuriscan_infosys_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action: 'get_error_logs',
            }, function(data){
                $('.sucuriscan-errorlogs-list tbody').html(data);
            });
        });
        </script>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-errorlogs-list">
            <thead>
                <tr>
                    <th width="100">Date Time</th>
                    <th width="50">Type</th>
                    <th>Error Message</th>
                    <th width="300">File</th>
                    <th width="50">Line</th>
                </tr>
            </thead>

            <tbody>
            </tbody>
        </table>

        <div class="sucuriscan-recipient-form">
            <form action="%%SUCURI.URL.Hardening%%" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_run_hardening" value="1" />
                <input type="hidden" name="sucuriscan_harden_errorlog" value="Harden" />
                <button type="submit" class="button-primary">Delete Logs</button>
            </form>
        </div>
    </div>
</div>
