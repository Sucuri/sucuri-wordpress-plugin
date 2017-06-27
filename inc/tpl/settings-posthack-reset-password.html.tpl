
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">@@SUCURI.PasswordChange@@</h3>

    <script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */
    jQuery(function ($) {
        $('#sucuriscan-reset-password-button').on('click', function (event) {
            event.preventDefault();
            $('.sucuriscan-reset-password-table :checkbox:checked').each(function (key, el) {
                var user_id = $(el).val();

                $('#sucuriscan-userid-' + user_id)
                .find('.sucuriscan-response')
                .html('(@@SUCURI.Loading@@)');

                $.post('%%SUCURI.AjaxURL.Dashboard%%', {
                    action: 'sucuriscan_ajax',
                    sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                    form_action: 'reset_user_password',
                    user_id: user_id,
                }, function (data) {
                    $('#sucuriscan-userid-' + user_id)
                    .find('.sucuriscan-response')
                    .html('(' + data + ')');
                });
            });
        });
    });
    </script>

    <div class="inside">
        <p>@@SUCURI.PasswordChangeInfo@@</p>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-reset-password-table">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th class="manage-column">@@SUCURI.Username@@</th>
                    <th class="manage-column">@@SUCURI.Email@@</th>
                    <th class="manage-column">@@SUCURI.Registered@@</th>
                    <th class="manage-column">@@SUCURI.Roles@@</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.ResetPassword.UserList%%%

                <tr class="sucuriscan-%%SUCURI.ResetPassword.PaginationVisibility%%">
                    <td colspan="4">
                        <ul class="sucuriscan-pagination">
                            %%%SUCURI.ResetPassword.PaginationLinks%%%
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>

        <button type="button" id="sucuriscan-reset-password-button"
        class="button button-primary">@@SUCURI.Submit@@</button>
    </div>
</div>
