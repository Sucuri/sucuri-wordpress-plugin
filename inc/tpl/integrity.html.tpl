
<script type="text/javascript">
/* global jQuery */
/* jshint camelcase: false */
jQuery(function ($) {
    $.post('%%SUCURI.AjaxURL.Dashboard%%', {
        action: 'sucuriscan_ajax',
        sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
        form_action: 'check_wordpress_integrity',
    }, function (data) {
        $('#sucuriscan-integrity-response').html(data);
    });
});
</script>

<div id="sucuriscan-integrity-response">
    <!-- Populated by JavaScript -->

    <div class="sucuriscan-panel sucuriscan-integrity sucuriscan-integrity-loading">
        <div class="sucuriscan-clearfix">
            <div class="sucuriscan-pull-left sucuriscan-integrity-left">
                <h2 class="sucuriscan-title">@@SUCURI.IntegrityTitle@@</h2>

                <p>@@SUCURI.IntegrityDescription@@</p>
            </div>

            <div class="sucuriscan-pull-right sucuriscan-integrity-right">
                <div class="sucuriscan-integrity-missing">
                    <!-- Missing data; display loading message -->
                </div>
            </div>
        </div>

        <p>@@SUCURI.Loading@@</p>
    </div>
</div>
