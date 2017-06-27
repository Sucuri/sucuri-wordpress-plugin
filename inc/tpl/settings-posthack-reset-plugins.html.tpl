
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.PluginReinstall@@</h3>

    <script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase:false */
    jQuery(function ($) {
        $.post('%%SUCURI.AjaxURL.Dashboard%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'get_plugins_data',
        }, function (data) {
            $('.sucuriscan-reset-plugins-table tbody').html( data );
        });

        $('#sucuriscan_reset_plugins').on('click', function (event) {
            event.preventDefault();
            $('.sucuriscan-reset-plugins-table .check-column :checkbox:checked').each(function (key, el) {
                var pluginName = $(el).val();
                var unique = $(el).attr('data-unique');

                $('#sucuriscan-plugin-' + unique)
                .find('.sucuriscan-reset-plugin-response')
                .html('@@SUCURI.Loading@@');

                $.post('%%SUCURI.AjaxURL.Dashboard%%', {
                    action: 'sucuriscan_ajax',
                    sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                    sucuriscan_plugin_name: pluginName,
                    form_action: 'reset_plugin',
                }, function (data) {
                    $('#sucuriscan-plugin-' + unique)
                    .find('.sucuriscan-reset-plugin-response')
                    .html(data);
                });
            });
        });
    });
    </script>

    <div class="inside">
        <p>@@SUCURI.PluginReinstallInfo@@</p>

        <div class="sucuriscan-inline-alert-info">
            <p>@@SUCURI.PluginReinstallCache@@</p>
        </div>

        <div class="sucuriscan-inline-alert-error">
            <p>@@SUCURI.PluginReinstallWarning@@</p>
        </div>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-reset-plugins-table">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th class="manage-column">@@SUCURI.Name@@</th>
                    <th class="manage-column">@@SUCURI.Version@@</th>
                    <th class="manage-column">@@SUCURI.Type@@</th>
                    <th class="manage-column">@@SUCURI.Status@@</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td colspan="5">
                        <span>@@SUCURI.Loading@@</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <button type="button" id="sucuriscan_reset_plugins" class="button button-primary">@@SUCURI.Submit@@</button>
    </div>
</div>
