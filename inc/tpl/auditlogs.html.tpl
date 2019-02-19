
<script type="text/javascript">
/* global jQuery */
/* jshint camelcase:false */
jQuery(document).ready(function ($) {
    var writeQueueSize = function (queueSize) {
        if (queueSize === 0) {
            $('.sucuriscan-auditlogs-sendlogs-response').html('');
            $('.sucuriscan-sendlogs-panel').addClass('sucuriscan-hidden');
        } else {
            var msg = '\x20logs in the queue\x20&mdash;\x20';
            $('.sucuriscan-auditlogs-sendlogs-response').html((queueSize).toString() + msg);
            $('.sucuriscan-sendlogs-panel').removeClass('sucuriscan-hidden');
        }
    };

    var sucuriscanLoadAuditLogs = function (page) {
        var url = '%%SUCURI.AjaxURL.Dashboard%%';

        if (page !== undefined && page > 0) {
            url += '&paged=' + page;
        }

        $('.sucuriscan-auditlog-response').html('<em>{{Loading...}}</em>');
        $('.sucuriscan-auditlog-status').html('{{Loading...}}');
        $('.sucuriscan-pagination-loading').html('{{Loading...}}');
        $('.sucuriscan-pagination-panel').addClass('sucuriscan-hidden');
        $('.sucuriscan-auditlog-footer').addClass('sucuriscan-hidden');

        $.post(url, {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'get_audit_logs',
        }, function (data) {
            $('.sucuriscan-pagination-loading').html('');

            writeQueueSize(data.queueSize);

            $('.sucuriscan-auditlog-status').html(data.status);
            $('.sucuriscan-auditlog-footer').removeClass('sucuriscan-hidden');

            if (data.content !== undefined) {
                $('.sucuriscan-auditlog-response').html(data.content);

                if (data.pagination !== '') {
                    $('.sucuriscan-pagination-panel').removeClass('sucuriscan-hidden');
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
        sucuriscanLoadAuditLogs(0);
    }, 100);

    $('.sucuriscan-auditlog-table').on('click', '.sucuriscan-pagination-link', function (event) {
        event.preventDefault();
        window.scrollTo(0, $('#sucuriscan-integrity-response').height() + 100);
        sucuriscanLoadAuditLogs($(this).attr('data-page'));
    });

    $('.sucuriscan-auditlog-table').on('click', '.sucuriscan-auditlogs-sendlogs', function (event) {
        event.preventDefault();

        $('.sucuriscan-sendlogs-panel').attr('content', '');
        $('.sucuriscan-auditlogs-sendlogs-response').html('{{Loading...}}');

        $.post('%%SUCURI.AjaxURL.Dashboard%%', {
            action: 'sucuriscan_ajax',
            sucuriscan_page_nonce: '%%SUCURI.PageNonce%%',
            form_action: 'auditlogs_send_logs',
        }, function (data) {
            sucuriscanLoadAuditLogs(0);

            setTimeout(function (){
                var tooltipContent =
                    '{{Total logs in the queue:}} {TTLLOGS}<br>' +
                    '{{Maximum execution time:}} {MAXTIME}<br>' +
                    '{{Successfully sent to the API:}} {SUCCESS}<br>' +
                    '{{Total request timeouts (failures):}} {FAILURE}<br>' +
                    '{{Total execution time:}} {ELAPSED} secs';
                $('.sucuriscan-sendlogs-panel')
                    .attr('content', tooltipContent
                    .replace('{MAXTIME}', data.maxtime)
                    .replace('{TTLLOGS}', data.ttllogs)
                    .replace('{SUCCESS}', data.success)
                    .replace('{FAILURE}', data.failure)
                    .replace('{ELAPSED}', data.elapsed)
                );
            }, 200);
        });
    });
});
</script>

<div class="sucuriscan-auditlog-table">
    <div class="sucuriscan-auditlog-response">
        <em>{{Loading...}}</em>
    </div>

    <div class="sucuriscan-clearfix sucuriscan-pagination-panel">
        <ul class="sucuriscan-pull-left sucuriscan-pagination">
            <!-- Populated via JavaScript -->
        </ul>

        <div class="sucuriscan-pull-right sucuriscan-pagination-loading">
            <!-- Populated via JavaScript -->
        </div>
    </div>

    <div class="sucuriscan-clearfix sucuriscan-auditlog-footer">
        <div class="sucuriscan-pull-left sucuriscan-hidden sucuriscan-tooltip
            sucuriscan-sendlogs-panel" tooltip-width="250" tooltip-html="true">
            <small class="sucuriscan-auditlogs-sendlogs-response"></small>
            <small><a href="#" class="sucuriscan-auditlogs-sendlogs">{{Send Logs}}</a></small>
        </div>

        <div class="sucuriscan-pull-right">
            <small class="sucuriscan-auditlog-status"></small>
        </div>
    </div>
</div>
