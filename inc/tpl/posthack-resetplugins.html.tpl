
<div class="sucuriscan-panelstuff sucuriscan-reset-plugins">
    <div class="postbox">
        <div class="inside">
            <p>
                In case that you suspect of an infection in your site, or even after you got rid
                of a malicious code, it would be better if you <strong>re-install</strong> all
                the plugins installed in your site, including the ones you are not using
                <em>(aka. deactivated)</em>. Select from the list bellow the plugins you want to
                reset <em>(it is recommended to select them all)</em>, be aware that
                <strong>premium plugins will not be re-installed</strong>.
            </p>

            <div class="sucuriscan-inline-alert-info">
                <p>
                    The information shown here is cache for %%SUCURI.ResetPlugin.CacheLifeTime%%
                    seconds, this is necessary to reduce the quantity of HTTP requests sent to the
                    WordPress servers and the bandwidth of your site. Currently there is no option
                    to recreate this cache so you have to wait until it resets itself.
                </p>
            </div>

            <div class="sucuriscan-inline-alert-error">
                <p>
                    <b>WARNING!</b> This procedure can break your website. The reset will not
                    affect the database nor the settings of each plugin but depending on how they
                    were written the reset action might break them. Be sure to create a backup of
                    the plugins directory before the execution of this tool.
                </p>
            </div>

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-reset-plugins-table">
                <thead>
                    <tr>
                        <th class="manage-column column-cb check-column">
                            <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                            <input id="cb-select-all-1" type="checkbox">
                        </th>
                        <th class="manage-column">Plugin</th>
                        <th class="manage-column">Version</th>
                        <th class="manage-column">Type</th>
                        <th class="manage-column">Status</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td colspan="5">
                            <span>Loading <em>(may take several seconds)</em>...</span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p>
                <button type="button" id="sucuriscan_reset_plugins" class="button button-primary">Proceed</button>
            </p>

            <script type="text/javascript">
            /* jshint camelcase:false */
            jQuery(function($){
                $.post('%%SUCURI.AjaxURL.Posthack%%', {
                    action: 'sucuriscan_posthack_ajax',
                    sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                    form_action: 'get_plugins_data',
                }, function(data){
                    $('.sucuriscan-reset-plugins-table tbody').html( data );
                });

                $('#sucuriscan_reset_plugins').on('click', function (event) {
                    event.preventDefault();
                    $('.check-column :checkbox:checked').each(function (key, el) {
                        var pluginName = $(el).val();
                        var unique = $(el).attr('data-unique');

                        $('#sucuriscan-plugin-' + unique)
                        .find('.sucuriscan-reset-plugin-response')
                        .html('Loading...');

                        $.post('%%SUCURI.AjaxURL.Posthack%%', {
                            action: 'sucuriscan_posthack_ajax',
                            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                            sucuriscan_plugin_name: pluginName,
                            form_action: 'reset_plugin',
                        }, function(data){
                            $('#sucuriscan-plugin-' + unique)
                            .find('.sucuriscan-reset-plugin-response')
                            .html(data);
                        });
                    });
                });
            });
            </script>
        </div>
    </div>
</div>
