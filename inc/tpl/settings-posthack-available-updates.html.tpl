
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.AvailableUpdates@@</h3>

    <script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */
    jQuery(function ($) {
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
        <p>@@SUCURI.AvailableUpdatesInfo@@</p>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-available-updates-table">
            <thead>
                <tr>
                    <th class="manage-column">@@SUCURI.Name@@</th>
                    <th class="manage-column">@@SUCURI.Version@@</th>
                    <th class="manage-column">@@SUCURI.Update@@</th>
                    <th class="manage-column">@@SUCURI.TestedWith@@</th>
                    <th class="manage-column">&nbsp;</th>
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
    </div>
</div>
