
<script type="text/javascript">
/* global jQuery */
/* jshint camelcase: false */
jQuery(document).ready(function ($) {
    $.post('%%SUCURI.AjaxURL.Dashboard%%', {
        action: 'sucuriscan_ajax',
        sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
        sucuriscan_sitecheck_refresh: '%%SUCURI.SiteCheck.Refresh%%',
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
                <h2 class="sucuriscan-title">WordPress Integrity</h2>

                <p>We inspect your WordPress installation and look for modifications on the core files as provided by WordPress.org. Files located in the root directory, wp-admin and wp-includes will be compared against the files distributed with v%%SUCURI.WordPressVersion%%; all files with inconsistencies will be listed here. Any changes might indicate a hack.</p>
            </div>

            <div class="sucuriscan-pull-right sucuriscan-integrity-right">
                <div class="sucuriscan-integrity-missing">
                    <!-- Missing data; display loading message -->
                </div>
            </div>
        </div>

        <p>Loading...</p>
    </div>
</div>
