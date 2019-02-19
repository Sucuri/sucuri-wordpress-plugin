
<div class="sucuriscan-integrity-diff-utility">
    %%%SUCURI.DiffUtility.Modal%%%

    <style type="text/css">.sucuriscan-integrity-filepath {cursor: pointer}</style>

    <script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */
    jQuery(document).ready(function ($) {
        $('.sucuriscan-integrity-table th .sucuriscan-tooltip').removeClass('sucuriscan-hidden');

        $('.sucuriscan-integrity-table .sucuriscan-integrity-filepath').on('click', function (event) {
            event.preventDefault();

            window.scrollTo(0, 0);
            var filepath = $(this).attr('data-filepath');
            $('.sucuriscan-diff-utility-modal').removeClass('sucuriscan-hidden');
            $('.sucuriscan-diff-utility-modal .sucuriscan-modal-inside').html('{{Loading...}}');

            $.post('%%SUCURI.AjaxURL.Dashboard%%', {
                action: 'sucuriscan_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action: 'integrity_diff_utility',
                filepath: filepath,
            }, function (data) {
                $('.sucuriscan-diff-utility-modal .sucuriscan-modal-inside').html(data);
                $('.sucuriscan-diff-content').before('<p>{{Lines with a <b>minus</b> sign as the prefix <em>(here in red)</em> show the original code. Lines with a <b>plus</b> sign as the prefix <em>(here in green)</em> show the modified code. You can read more about the DIFF format from the WikiPedia article about the <a target="_blank" href="https://en.wikipedia.org/wiki/Diff_utility" rel="noopener">Unix Diff Utility</a>.}}</p>');
            });
        });
    });
    </script>
</div>
