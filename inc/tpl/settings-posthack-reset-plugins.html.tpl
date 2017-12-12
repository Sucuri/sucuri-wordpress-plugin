
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Reset Installed Plugins</h3>

    <script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase:false */
    jQuery(document).ready(function ($) {
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
                .html('Loading...');

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
        <p>In case that you suspect of an infection in your site, or even after you got rid of a malicious code, it would be better if you reinstall all the plugins installed in your site, including the ones you are not using. Notice that premium plugins will not be reinstalled to prevent backward compatibility issues and problems with licenses.</p>

        <div class="sucuriscan-inline-alert-info">
            <p>The information shown here is cache for %%SUCURI.ResetPlugin.CacheLifeTime%% seconds, this is necessary to reduce the quantity of HTTP requests sent to the WordPress servers and the bandwidth of your site. Currently there is no option to recreate this cache so you have to wait until it resets itself.</p>
        </div>

        <div class="sucuriscan-inline-alert-error">
            <p><b>WARNING!</b> This procedure can break your website. The reset will not affect the database nor the settings of each plugin but depending on how they were written the reset action might break them. Be sure to create a backup of the plugins directory before the execution of this tool.</p>
        </div>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-reset-plugins-table">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th class="manage-column">Name</th>
                    <th class="manage-column">Version</th>
                    <th class="manage-column">Type</th>
                    <th class="manage-column">Status</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td colspan="5">
                        <span>Loading...</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <button type="button" id="sucuriscan_reset_plugins" class="button button-primary">Submit</button>
    </div>
</div>
