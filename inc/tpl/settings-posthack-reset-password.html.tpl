
<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Reset User Password}}</h3>

    <script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */
    jQuery(document).ready(function ($) {
        $('#sucuriscan-reset-password-button').on('click', function (event) {
            event.preventDefault();
            $('.sucuriscan-reset-password-table :checkbox:checked').each(function (key, el) {
                var user_id = $(el).val();

                $('#sucuriscan-userid-' + user_id)
                .find('.sucuriscan-response')
                .html('({{Loading...}})');

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
        <p>{{You can generate a new random password for the user accounts that you select from the list. An email with the new password will be sent to the email address of each chosen user. If you choose to change the password of your own user, then your current session will expire immediately. You will need to log back into the admin panel with the new password that will be sent to your email.}}</p>

        <table class="wp-list-table widefat sucuriscan-table sucuriscan-reset-password-table">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">{{Select All}}</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th class="manage-column">{{Username}}</th>
                    <th class="manage-column">{{E-mail}}</th>
                    <th class="manage-column">{{Registered}}</th>
                    <th class="manage-column">{{Roles}}</th>
                </tr>
            </thead>

            <tbody>
                %%%SUCURI.ResetPassword.UserList%%%

                <tr class="sucuriscan-%%SUCURI.ResetPassword.PaginationVisibility%%">
                    <td colspan="5">
                        <ul class="sucuriscan-pagination">
                            %%%SUCURI.ResetPassword.PaginationLinks%%%
                        </ul>
                    </td>
                </tr>
            </tbody>
        </table>

        <button type="button" id="sucuriscan-reset-password-button"
        class="button button-primary">{{Submit}}</button>
    </div>
</div>
