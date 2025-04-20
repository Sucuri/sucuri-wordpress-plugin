<script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */

    jQuery(document).ready(function ($) {
        function toggleCORSDirectiveFields($checkbox) {
            var $row = $checkbox.closest('tr');
            var enforced = $checkbox.is(':checked');

            // Find the directive inputs (text or checkboxes)
            var $inputsContainer = $row.find('.cors-directive-inputs');
            var $inputs = $inputsContainer.find('input[type="text"], input[type="checkbox"]');

            if (enforced) {
                $inputs.prop('disabled', false);
            } else {
                $inputs.prop('disabled', true);
            }
        }

        $('table.cors-table tbody tr').each(function () {
            var $row = $(this);
            var $enforceCheckbox = $row.find('input[name^="sucuriscan_enforced_"]');
            if ($enforceCheckbox.length > 0) {
                toggleCORSDirectiveFields($enforceCheckbox);
            }
        });

        $('input[name^="sucuriscan_enforced_"]').on('click', function () {
            toggleCORSDirectiveFields($(this));
        });
    });
</script>

<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{CORS Options}}</h3>

    <form action="%%SUCURI.URL.Headers%%" method="post">
        <div class="inside">
            <p>{{Cross-Origin Resource Sharing (CORS) is a security feature that allows web applications to control the resources that can be requested from another domain.}}</p>
            <p>{{Here you can see all the CORS options available.}}</p>

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-table-fixed-layout sucuriscan-table-double-title cors-table">
                <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">

                    </td>
                    <th class="manage-column">{{Header}}</th>
                    <th class="manage-column">{{Header Value}}</th>

                </tr>
                </thead>

                <tbody data-cy="sucuriscan_cors_options_table">
                %%%SUCURI.CORSOptions.Options%%%
                </tbody>
            </table>
        </div>

        <div class="sucuriscan-double-box sucuriscan-hstatus sucuriscan-double-box-update sucuriscan-hstatus-%%SUCURI.CORSOptions.CORSControl%%"
             data-cy="sucuriscan_headers_cors_control">
            <p>
                <strong>{{CORS}}</strong> &mdash; <span>%%SUCURI.CORSOptions.Status%%</span><br/>
                {{Cross-Origin Resource Sharing (CORS) is a security feature that allows web applications to control the resources that can be requested from another domain.}}
            </p>

            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%"/>
            <input type="hidden" name="sucuriscan_update_cors_options" value="1"/>

            <div>
                <label><strong>{{Mode:}}</strong></label>

                <select name="sucuriscan_cors_options_mode" data-cy="sucuriscan_cors_options_mode_button">
                    %%%SUCURI.CORSOptions.Modes%%%
                </select>

                <input type="submit" value="{{Submit}}" class="button button-primary"
                       data-cy="sucuriscan_headers_cors_control_submit_btn"/>
            </div>
        </div>
    </form>
</div>
