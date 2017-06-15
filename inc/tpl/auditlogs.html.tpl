
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
            $('.sucuriscan-auditlog-response').html('<em>@@SUCURI.Loading@@</em>');
        }

        $('.sucuriscan-pagination-loading').html('@@SUCURI.Loading@@');

        $.post(url, {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'get_audit_logs',
        }, function (data) {
            $('.sucuriscan-pagination-loading').html('');

            if (data.content !== undefined) {
                $('.sucuriscan-auditlog-response').html(data.content);

                if (data.selfhosting) {
                    $('#sucuriscan-auditlog-selfhosting').removeClass('sucuriscan-hidden');
                }

                if (data.pagination !== '') {
                    $('.sucuriscan-auditlog-table .sucuriscan-pagination').html(data.pagination);
                }
            } else if (typeof data === 'object') {
                $('.sucuriscan-auditlog-response').html(
                '<textarea class="sucuriscan-full-textarea">' +
                JSON.stringify(data) + '</textarea>');
                $('.sucuriscan-auditlog-table .sucuriscan-pagination').html('');
            } else {
                $('.sucuriscan-auditlog-response').html(data);
                $('.sucuriscan-auditlog-table .sucuriscan-pagination').html('');
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

    $('.sucuriscan-auditlog-table').on('click', '.sucuriscan-reset-auditlogs', function (event) {
        event.preventDefault();
        $.post('%%SUCURI.AjaxURL.Dashboard%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'reset_auditlogs_cache',
        }, function (data) {
            console.log(data);
            sucuriscanLoadAuditLogs(0, true);
        });
    });
});
</script>

<div class="sucuriscan-auditlog-table">
    <div id="sucuriscan-auditlog-selfhosting" class="sucuriscan-inline-alert-info sucuriscan-hidden">
        <p>@@SUCURI.SelfHostingFallback@@</p>
    </div>

    <div class="sucuriscan-auditlog-response">
        <em>@@SUCURI.Loading@@</em>
    </div>

    <div>
        <small>@@SUCURI.AuditLogsCache@@ &mdash; <a href="#" class="sucuriscan-reset-auditlogs">@@SUCURI.Refresh@@</a></small>
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
