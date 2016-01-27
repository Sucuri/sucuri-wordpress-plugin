
<div id="poststuff" class="sucuriscan-reset-plugins">
    <div class="postbox">
        <div class="inside">
            <form action="%%SUCURI.URL.Posthack%%#reset-plugins" method="post">
                <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%" />
                <input type="hidden" name="sucuriscan_reset_plugins" value="1" />

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
                    <label>
                        <input type="hidden" name="sucuriscan_process_form" value="0" />
                        <input type="checkbox" name="sucuriscan_process_form" value="1" />
                        <span>I understand that this operation can not be reverted.</span>
                    </label>
                </p>

                <input type="submit" value="Process selected items" class="button button-primary" />
            </form>

            <script type="text/javascript">
            jQuery(function($){
                $.post('%%SUCURI.AjaxURL.Posthack%%', {
                    action: 'sucuriscan_posthack_ajax',
                    sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                    form_action: 'get_plugins_data',
                }, function(data){
                    $('.sucuriscan-reset-plugins-table tbody').html( data );
                });
            });
            </script>
        </div>
    </div>
</div>
