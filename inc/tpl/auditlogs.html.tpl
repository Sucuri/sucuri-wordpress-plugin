
<script type="text/javascript">
/* global jQuery */
/* jshint camelcase:false */
jQuery(function ($) {
    var sucuriscanLoadAuditLogs = function (page, reset) {
        var url = '%%SUCURI.AjaxURL.Dashboard%%';

        if (page !== undefined && page > 0) {
            url += '&paged=' + page;
        }

        if (reset === true) {
            $('.sucuriscan-auditlog-response').html('<em>Loading...</em>');
        }

        $('.sucuriscan-pagination-loading').html('Loading...');

        $.post(url, {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'get_audit_logs',
        }, function (data) {
            if (data.content !== undefined) {
                $('.sucuriscan-auditlog-response').html(data.content);
                $('.sucuriscan-pagination-loading').html('');

                if (data.pagination !== '') {
                    $('.sucuriscan-auditlog-table .sucuriscan-pagination').html(data.pagination);
                }
            } else if (typeof data === 'object') {
                $('.sucuriscan-auditlog-response').html(
                '<textarea class="sucuriscan-full-textarea">' +
                JSON.stringify(data) + '</textarea>');
            } else {
                $('.sucuriscan-auditlog-response').html(data);
            }
        });
    }

    setTimeout(function () {
        sucuriscanLoadAuditLogs(0, true);
    }, 100);

    $('.sucuriscan-auditlog-table').on('click', '.sucuriscan-pagination-link', function (event) {
        event.preventDefault();
        sucuriscanLoadAuditLogs($(this).attr('data-page'));
    });
});
</script>

<div class="sucuriscan-auditlog-table">
    <div class="sucuriscan-auditlog-response">
        <em>Loading...</em>
    </div>

    <div class="sucuriscan-clearfix">
        <ul class="sucuriscan-pull-left sucuriscan-pagination">
            <!-- Populated via JavaScript -->
        </ul>

        <div class="sucuriscan-pull-right sucuriscan-pagination-loading">
            <!-- Populated via JavaScript -->
        </div>
    </div>
</div>
