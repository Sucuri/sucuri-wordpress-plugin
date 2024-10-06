<script type="text/javascript">
    /* global jQuery */
    /* jshint camelcase:false */
    jQuery(document).ready(function ($) {
        function parseFilteredDataToQueryParams(filteredData) {
            const queryParams = new URLSearchParams();

            for (const key in filteredData) {
                if (filteredData.hasOwnProperty(key) && filteredData[key] !== null && filteredData[key] !== undefined) {
                    queryParams.append(key, filteredData[key]);
                }
            }

            return queryParams.toString();
        }

        var writeQueueSize = function (queueSize, issetApiUrl) {
            if (queueSize === 0 || !issetApiUrl) {
                $('.sucuriscan-auditlogs-sendlogs-response').html('');
                $('.sucuriscan-sendlogs-panel').addClass('sucuriscan-hidden');
            } else {
                var msg = '\x20logs in the queue\x20&mdash;\x20';
                $('.sucuriscan-auditlogs-sendlogs-response').html((queueSize).toString() + msg);
                $('.sucuriscan-sendlogs-panel').removeClass('sucuriscan-hidden');
            }
        };

        var sucuriscanLoadAuditLogs = function (page, filters = {}) {
            var url = '%%SUCURI.AjaxURL.Dashboard%%';

            if (page !== undefined && page > 0) {
                url += '&paged=' + page;
            }

            if (filters !== undefined && Object.keys(filters).length > 0) {
                url += '&' + parseFilteredDataToQueryParams(filters);
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

                writeQueueSize(data.queueSize, data.status != 'API is not available; using local queue');

                $('.sucuriscan-auditlog-status').html(data.status);
                $('.sucuriscan-auditlog-footer').removeClass('sucuriscan-hidden');

                if (data.filters !== undefined) {
                    $('#sucuriscan-filters').html(data.filters);

                    $('#startDateFilter').hide();
                    $('#endDateFilter').hide();

                    if (filters.time === 'custom') {
                        $('#startDateFilter').show();
                        $('#endDateFilter').show();
                    }
                }

                if (data.filtersStatus === 'active') {
                    $('#clear-filter-button').show();
                }

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

            // Get the filters from the URL
            var url = new URL(event.target.href);
            var page = url.searchParams.get('paged');
            var filters = {};
            // time, posts, logins, users, plugins
            var time = url.searchParams.get('time');
            var posts = url.searchParams.get('posts');
            var logins = url.searchParams.get('logins');
            var users = url.searchParams.get('users');
            var plugins = url.searchParams.get('plugins');

            if (time !== null) filters.time = time;
            // handle date pickers
            if (time === 'custom') {
                filters.startDate = url.searchParams.get('startDate');
                filters.endDate = url.searchParams.get('endDate');
            }

            if (posts !== null) filters.posts = posts;
            if (logins !== null) filters.logins = logins;
            if (users !== null) filters.users = users;
            if (plugins !== null) filters.plugins = plugins;

            sucuriscanLoadAuditLogs($(this).attr('data-page'), filters);
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

                setTimeout(function () {
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

        document.body.addEventListener('click', function (e) {
            if (e.target.id == 'filter-button') {
                var filters = {};

                var time = $('#time').val();

                if (time === 'custom') {
                    filters.startDate = $('#startDateFilter').val();
                    filters.endDate = $('#endDateFilter').val();
                }

                var posts = $('#posts').val();
                var logins = $('#logins').val();
                var users = $('#users').val();
                var plugins = $('#plugins').val();

                if (time !== 'all time') filters.time = time;
                if (posts !== 'all posts') filters.posts = posts;
                if (logins !== 'all logins') filters.logins = logins;
                if (users !== 'all users') filters.users = users;
                if (plugins !== 'all plugins') filters.plugins = plugins;

                sucuriscanLoadAuditLogs(0, filters);
            }

            document.body.addEventListener('click', function (e) {
                if (e.target.id == 'clear-filter-button') {
                    sucuriscanLoadAuditLogs(0);

                    // update dropdown values for filters
                    $('#time').val('all time');
                    $('#posts').val('all posts');
                    $('#logins').val('all logins');
                    $('#users').val('all users');
                    $('#plugins').val('all plugins');
                }
            });
        });

        document.body.addEventListener('change', function (e) {
            if (e.target.id === 'time') {

                if ($(e.target).val() === 'custom') {
                    $('#startDateFilter').show();
                    $('#endDateFilter').show();
                } else {
                    $('#startDateFilter').hide();
                    $('#endDateFilter').hide();
                }
            }
        });
    });
</script>

<div class="sucuriscan-auditlog-table">
    <div id="sucuriscan-filters"></div>

    <div class="sucuriscan-auditlog-response" data-cy="sucuriscan_auditlog_response_loading">
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

    <div class="sucuriscan-clearfix sucuriscan-auditlog-footer" data-cy="sucuriscan_audit_logs_footer">
        <div class="sucuriscan-pull-left sucuriscan-hidden sucuriscan-tooltip
            sucuriscan-sendlogs-panel" tooltip-width="250" tooltip-html="true">
            <small class="sucuriscan-auditlogs-sendlogs-response"></small>
            <small><a href="#" class="sucuriscan-auditlogs-sendlogs"
                      data-cy="sucuriscan_dashboard_send_audit_logs_submit">{{Send Logs}}</a></small>
        </div>

        <div class="sucuriscan-pull-right">
            <small class="sucuriscan-auditlog-status"></small>
        </div>
    </div>
</div>
