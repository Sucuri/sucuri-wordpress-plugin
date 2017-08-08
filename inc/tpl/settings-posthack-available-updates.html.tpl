
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">Available Plugin and Theme Updates</h3>

    <script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */
    jQuery(document).ready(function ($) {
        $.post('%%SUCURI.AjaxURL.Dashboard%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'get_available_updates',
        }, function (data) {
            $('.sucuriscan-available-updates-table tbody').html(data);
        });
    });
    </script>

    <div class="inside">
        <p>WordPress has a big user base in the public Internet, this brings interest to malicious people to find vulnerabilities in the code, 3rd-party extensions, and themes that other companies develop. You should keep every piece of code installed in your website update to prevent attacks as soon as disclosed vulnerabilities are patched.</p>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-available-updates-table">
            <thead>
                <tr>
                    <th class="manage-column">Name</th>
                    <th class="manage-column">Version</th>
                    <th class="manage-column">Update</th>
                    <th class="manage-column">Tested With</th>
                    <th class="manage-column">&nbsp;</th>
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
    </div>
</div>
