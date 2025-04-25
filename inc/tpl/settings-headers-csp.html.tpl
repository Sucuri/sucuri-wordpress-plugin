<script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */

    jQuery(document).ready(function ($) {
        function toggleCSPDirectiveFields($checkbox) {
            var $row = $checkbox.closest('tr');
            var enforced = $checkbox.is(':checked');

            // Find the directive inputs (text or checkboxes)
            var $inputsContainer = $row.find('.csp-directive-inputs');
            var $inputs = $inputsContainer.find('input[type="text"], input[type="checkbox"]');

            if (enforced) {
                $inputs.prop('disabled', false);
            } else {
                $inputs.prop('disabled', true);
            }
        }

        $('table.csp-table tbody tr').each(function () {
            var $row = $(this);
            var $enforceCheckbox = $row.find('input[name^="sucuriscan_enforced_"]');
            if ($enforceCheckbox.length > 0) {
                toggleCSPDirectiveFields($enforceCheckbox);
            }
        });

        $('input[name^="sucuriscan_enforced_"]').on('click', function () {
            toggleCSPDirectiveFields($(this));
        });
    });
</script>

<div class="sucuriscan-panel">
    <h3 class="sucuriscan-title">{{Content Security Policy (CSP) Options}}</h3>

    <form action="%%SUCURI.URL.Headers%%" method="post">
        <div class="inside">
            <p>{{Content Security Policy (CSP) is a security feature that helps prevent various types of attacks, such as Cross-Site Scripting (XSS) and data injection attacks.}}</p>
            <p>{{Here you can see all the CSP options available.}}</p>

            <table class="wp-list-table widefat sucuriscan-table sucuriscan-table-fixed-layout sucuriscan-table-double-title csp-table">
                <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">

                    </td>
                    <th class="manage-column">{{Directive}}</th>
                    <th class="manage-column">{{Directive Value}}</th>
                </tr>
                </thead>

                <tbody data-cy="sucuriscan_csp_options_table">
                %%%SUCURI.CSPOptions.Options%%%
                </tbody>
            </table>
        </div>

        <div class="sucuriscan-double-box sucuriscan-hstatus sucuriscan-double-box-update sucuriscan-hstatus-%%SUCURI.CSPOptions.CSPControl%%"
             data-cy="sucuriscan_headers_csp_control">
            <p>
                <strong>{{Content Security Policy}}</strong> &mdash; <span>%%SUCURI.CSPOptions.Status%%</span><br/>
                {{Content Security Policy (CSP) is a security feature that helps prevent various types of attacks, such as Cross-Site Scripting (XSS) and data injection attacks.}}
            </p>

            <input type="hidden" name="sucuriscan_page_nonce" value="%%SUCURI.PageNonce%%"/>
            <input type="hidden" name="sucuriscan_update_csp_options" value="1"/>

            <div>
                <label><strong>{{Mode:}}</strong></label>

                <select name="sucuriscan_csp_options_mode" data-cy="sucuriscan_csp_options_mode_button">
                    %%%SUCURI.CSPOptions.Modes%%%
                </select>

                <input type="submit" value="{{Submit}}" class="button button-primary"
                       data-cy="sucuriscan_headers_csp_control_submit_btn"/>
            </div>
        </div>
    </form>
</div>
