
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.IgnoreFiles@@</h3>

    <div class="inside">
        <p>@@SUCURI.IgnoreFilesInfo@@</p>

        <script type="text/javascript">
        /* global jQuery */
        /* jshint camelcase: false */
        jQuery(function ($) {
            $('.sucuriscan-ignorescanning tbody').html(
                '<tr><td colspan="3"><span>@@SUCURI.Loading@@</span></td></tr>'
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
                <label>@@SUCURI.IgnoreFilesSingle@@:</label>
                <input type="text" name="sucuriscan_ignorescanning_file" placeholder="e.g. /private/cert.crt" />
                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
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
                    <th class="manage-column">@@SUCURI.FilePath@@</th>
                    <th class="manage-column">@@SUCURI.Status@@</th>
                </thead>

                <tbody>
                </tbody>
            </table>

            <div class="sucuriscan-recipient-form">
                <label>
                    <select name="sucuriscan_ignorescanning_action">
                        <option value="">@@SUCURI.Action@@</option>
                        <option value="ignore">@@SUCURI.Ignore@@</option>
                        <option value="unignore">@@SUCURI.Unignore@@</option>
                    </select>
                </label>

                <button type="submit" class="button button-primary">@@SUCURI.Submit@@</button>
            </div>
        </form>
    </div>
</div>
