
<div class="sucuriscan-panelstuff sucuriscan-updates">
    <div class="postbox">
        <div class="inside">
            <p>
                WordPress has a big user base in the public Internet, this brings interest to
                malicious people to find vulnerabilities in the code code, 3rd-party extensions,
                and themes that other companies develop. You should keep every piece of code
                installed in your website update to prevent attacks as soon as disclosed
                vulnerabilities are patched.
            </p>

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-updates-table">
                <thead>
                    <tr>
                        <th class="manage-column">Extension</th>
                        <th class="manage-column">Installed</th>
                        <th class="manage-column">Available</th>
                        <th class="manage-column">Tested With</th>
                        <th class="manage-column">&nbsp;</th>
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

            <script type="text/javascript">
            jQuery(function($){
                $.post('%%SUCURI.AjaxURL.Posthack%%', {
                    action: 'sucuriscan_posthack_ajax',
                    sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                    form_action: 'get_available_updates',
                }, function(data){
                    $('.sucuriscan-updates-table tbody').html(data);
                });
            });
            </script>
        </div>
    </div>
</div>
