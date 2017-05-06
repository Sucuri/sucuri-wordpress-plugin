
<div class="sucuriscan-integrity-diff-utility">
    <div class="sucuriscan-inline-alert-info">
        <p>
            The Unix Diff Utility is enabled. You can click the files marked
            as modified <em>(the ones with the purple flag)</em> to see the
            differences detected by the scanner. If you consider the differences
            to be harmless you can mark the file as fixed, otherwise it is adviced
            to restore the original content immediately.
        </p>
    </div>

    <div class="sucuriscan-hidden sucuriscan-diff-instructions">
        <p>
            Lines with a <b>minus</b> sign as the prefix <em>(here in red)</em>
            show the original code. Lines with a <b>plus</b> sign as the prefix
            <em>(here in green)</em> show the modified code. You can read more
            about the DIFF format from the WikiPedia article about the <a target="_blank"
            href="https://en.wikipedia.org/wiki/Diff_utility">Unix Diff Utility</a>.
        </p>
    </div>

    %%%SUCURI.DiffUtility.Modal%%%

    <script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase: false */
    jQuery(function ($) {
        $('.sucuriscan-integrity-table .sucuriscan-integrity-filepath').on('click', function (event) {
            event.preventDefault();

            window.scrollTo(0, 0);
            var filepath = $(this).attr('data-filepath');
            $('.sucuriscan-diff-utility-modal').removeClass('sucuriscan-hidden');
            $('.sucuriscan-diff-utility-modal .sucuriscan-modal-inside').html('Loading...');

            $.post('%%SUCURI.AjaxURL.Dashboard%%', {
                action: 'sucuriscan_ajax',
                sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
                form_action: 'integrity_diff_utility',
                filepath: filepath,
            }, function (data) {
                var instructions = $('.sucuriscan-diff-instructions').html();
                $('.sucuriscan-diff-utility-modal .sucuriscan-modal-inside').html(data);
                $('.sucuriscan-diff-content').before(instructions);
            });
        });
    });
    </script>
</div>
