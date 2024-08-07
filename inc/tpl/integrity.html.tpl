<script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */

    jQuery(document).ready(function ($) {
        var sucuriscanLoadIntegrityStatus = function (page, scroll) {
            var url = '%%SUCURI.AjaxURL.Dashboard%%';

            if (page !== undefined && page > 0) {
                url += '&paged=' + page;
            }

            $('[data-cy=sucuriscan_integrity_list_table]').html('<div class="sucuriscan-is-loading">{{Loading...}}</div>');

            if (!$('.sucuriscan-pagination-integrity').hasClass('sucuriscan-hidden')) {
                $('.sucuriscan-pagination-integrity').toggleClass('sucuriscan-hidden');
            }

            $.post(url, {
                action: 'sucuriscan_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                sucuriscan_sitecheck_refresh: '%%SUCURI.SiteCheck.Refresh%%',
                form_action: 'check_wordpress_integrity',
                files_per_page: $('#sucuriscan_integrity_files_per_page').val(),
            }, function (data) {
                $('#sucuriscan-integrity-response').html(data);

                if (!$('.sucuriscan-pagination-integrity').hasClass('sucuriscan-hidden')) {
                    $('.sucuriscan-pagination-integrity').toggleClass('sucuriscan-hidden', false);
                }

                if (scroll) {
                    $('#sucuriscan-integrity-response')[0].scrollIntoView({
                        behavior: 'smooth',
                    });
                }
            });
        }

        $('#sucuriscan-integrity-response').on('click', '.sucuriscan-pagination-link', function (event) {
            event.preventDefault();
            var filesPerPage = $(this).val();
            sucuriscanLoadIntegrityStatus($(this).attr('data-page'), true);
        });

        $(document).on('change', '#sucuriscan_integrity_files_per_page', function () {
            var filesPerPage = $(this).val();
            sucuriscanLoadIntegrityStatus(1, true);
        });

        sucuriscanLoadIntegrityStatus();
    });
</script>

<div id="sucuriscan-integrity-response">
    <!-- Populated by JavaScript -->

    <div class="sucuriscan-panel sucuriscan-integrity sucuriscan-integrity-loading">
        <div class="sucuriscan-clearfix">
            <div class="sucuriscan-pull-left sucuriscan-integrity-left">
                <h2 class="sucuriscan-title">{{WordPress Integrity}}</h2>

                <p>{{We inspect your WordPress installation and look for modifications on the core files as provided by WordPress.org. Files located in the root directory, wp-admin and wp-includes will be compared against the files distributed with v%%SUCURI.WordPressVersion%%; all files with inconsistencies will be listed here. Any changes might indicate a hack.}}</p>
            </div>

            <div class="sucuriscan-pull-right sucuriscan-integrity-right">
                <div class="sucuriscan-integrity-missing">
                    <!-- Missing data; display loading message -->
                </div>
            </div>
        </div>

        <p>{{Loading...}}</p>
    </div>
</div>
